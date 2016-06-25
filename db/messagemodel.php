<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

include_once 'push.php'; //push notification module
include_once 'db/usermodel.php';

class messages extends database{

	public $db_connect = null;
	public $db_table = null;
	
	public function __construct() {

	 	$this->db_connect = parent::ConnectDB();
		$this->db_table = "messages";
	}

	public function message_insert($dat){	
		
		$data = array();
        $requiredParams = array("senderID", "receiverID", "message", "timeWhen");

        $paramsValid = $this->validateRequiredFields($requiredParams, $dat);

		if($paramsValid === true) {
			$data['senderID'] = $dat["senderID"];
			$data['receiverID'] = $dat["receiverID"];
			$data['message'] = $dat["message"];
			$data['timeWhen'] = $dat["timeWhen"];
			$data['status'] = "0";//1: old message, 0: new message
            
			$sender_exists = $this->userExists("users","id","id='".$data["senderID"]."'");
			$receiver_exists = $this->userExists("users","id","id='".$data["receiverID"]."'");

            if(!$sender_exists || !$receiver_exists) {
			        $res = array();
					$res["error"] = "0022";
					$res["message"] = "There are some error(The users does not exist.)";
					return $res;
			}
			else {
				$add_message = $this->addMessage($this->db_table, $data);

				if($add_message) {

					$user = new users();
                    $receiver = $user->selectUser("users","id, username, devicetoken", "id = '".$data['receiverID']."'");

                    $message = "You received the new message from ".$receiver->username;

                    //insert the invitation column in <invitation> table
                    $success = send_iOS_PushNotification($receiver->devicetoken, $message, $badge=1, $sound='default', $locktime=null);

					$res = array();
                    if ($success == "success") {
						$res["error"] = "0000";
                    	$res["message"] = "Your message was sent to ".$receiver->username." successfully.";
                    }else{
                    	$res["error"] = "0001";
                    	$res["message"] = $success;
                    }

					return $res;	 
				}
				else {
					$res = array();
					$res["error"] = "0023";
					$res["message"] = "There are some errors. Please try again later";
					return $res;
				 }
		    } 
		 }else{
            return $paramsValid;
        }
    }
	
	public function check_new_message($dat){	//edit the invitation status like accept or decline
		
		$data = array();

        $requiredParams = array("myID", "senderID");

        $paramsValid = $this->validateRequiredFields($requiredParams,$dat);

		if($paramsValid === true) {//checking parameter valid

			$data['senderID'] = $dat['senderID'];
			$data['receiverID'] = $dat['myID'];

			$me_exists = $this->userExists("users","id","id = '".$dat["myID"]."'");
			$sender_exists = $this->userExists("users","id","id = '".$dat["senderID"]."'");

			if ($me_exists && $sender_exists) {//checking sender and receiver existance
				$new_messages = $this->getMessages($this->db_table, "id, message, timeWhen", "senderID = '".$data['senderID']."' AND receiverID = '".$data['receiverID']."' AND status = '0'");

				if(empty($new_messages)) {
					$res = array();
            		$res["error"] = "0024";
            		$res["message"] = "There is no new messages!";
            		return $res;
				}
				else {

					foreach ($new_messages as $message) {
						// var_dump($message["id"]);
						$data = array();
						// $data["id"] = $message->id;
						$data["status"] = "1";
						$updateStatus = $this->updateStatus($this->db_table, $data, "id = '".$message["id"]."'");
					}

	            	$res = array();
	            	$res["error"] = "0000";
	            	$res["data"] = $new_messages;
	            	return $res;
	        	}

			}else{
			    $res = array();
				$res["error"] = "0025";
				$res["message"] = "There are some errors(The sender does not exist on DB)";	
				return $res;	
			}
		}
		else{
		    $res = array();
			$res["error"] = "0026";
			$res["message"] = "There are some error(All fields are required)";	
			return $res;
		}
    }	

    public function get_all_messages($dat){	//edit the invitation status like accept or decline
		
		$data = array();

        $requiredParams = array("myID", "senderID");

        $paramsValid = $this->validateRequiredFields($requiredParams,$dat);

		if($paramsValid === true) {//checking parameter valid

			$data['senderID'] = $dat['senderID'];
			$data['receiverID'] = $dat['myID'];

			$me_exists = $this->userExists("users","id","id = '".$dat["myID"]."'");
			$sender_exists = $this->userExists("users","id","id = '".$dat["senderID"]."'");

			if ($me_exists && $sender_exists) {//checking sender and receiver existance
				$all_messages = $this->getMessages($this->db_table, "id, message, timeWhen, status", "senderID = '".$data['senderID']."' AND receiverID = '".$data['receiverID']."'");

				if(empty($all_messages)) {
					$res = array();
            		$res["error"] = "0015";
            		$res["message"] = "There is no new messages!";
            		return $res;
				}
				else {

					foreach ($all_messages as $message) {
						if ($message["status"] == "0") {
							$data = array();
							// $data["id"] = $message->id;
							$data["status"] = "1";
							$updateStatus = $this->updateStatus($this->db_table, $data, "id = '".$message["id"]."'");
						}
					}

	            	$res = array();
	            	$res["error"] = "0000";
	            	$res["data"] = $all_messages;
	            	return $res;
	        	}

			}else{
			    $res = array();
				$res["error"] = "0004";
				$res["message"] = "There are some errors(The sender does not exist on DB)";	
				return $res;	
			}
		}
		else{
		    $res = array();
			$res["error"] = "0001";
			$res["message"] = "There are some error(All fields are required)";	
			return $res;
		}
    }	

    public function getMessages($table, $coloumn, $where){
				
		$strSql = "SELECT ".$coloumn." FROM ".$table;

		if($where)
			$strSql = $strSql ." where ".$where;	

		// return $strSql;exit;
		$rsSql = mysqli_query($this->db_connect, $strSql);

		$data = array();
		if(mysqli_num_rows($rsSql)>0){
		    while($row = mysqli_fetch_assoc($rsSql)){
			 	$data[] = $row;
			}
			return $data;		
	    }
		else{
			return FALSE;	
		}
	}
	
	// public function edit_password($dat){	
		
	// 	$data = array();

 //        $requiredParams = array("email","current_password","new_password");

 //        $paramsValid = $this->validateRequiredFields($requiredParams,$dat);

	// 	if($paramsValid === true) {

 //            $dat["current_password"] = md5($dat["current_password"]);
	// 		$user_exists=$this->userExists($this->db_table,"email","email='".$dat["email"]."' AND password='".$dat["current_password"]."'");

 //            if(!$user_exists) {
	// 		        $res=array();
	// 				$res["error"]="0005";
	// 				$res["message"]="There are some error(Email or Password given is wrong)";	
	// 				return $res;
	// 		}
	// 		else {
 //                $data['password'] = md5($dat["new_password"]);
	// 		    $update_data=$this->updateUser($this->db_table,$data,"email='".$dat["email"]."'");
	// 			if($update_data) {							
	// 				$res = array();
	// 				$res["error"] = "0000";
 //                    $res["message"] = "Password updated";
	// 				return $res;	 
	// 			}
	// 			else {
	// 				$res = array();
	// 				$res["error"] = "0005";
	// 				$res["message"] = "There are some error(Something went wrong)";
	// 				return $res;
	// 			}
	// 	    } 
	// 	}
	// 	else{
	// 			return $paramsValid;
	// 	}
 //    }	
		
	  
    public function addMessage($table,$data) {	
		
		$strSql	='INSERT INTO `'.$table.'` SET ';
		$arrdata = array();
		foreach ($data as $col=>$value) {
		 $arrdata[] = $col . " = '". $value."'";	
		}
		$strSql .= implode(', ', $arrdata);//echo $strSql; exit;
		$intCheck = mysqli_query($this->db_connect, $strSql);
		if(mysqli_affected_rows($this->db_connect)>0) 
			return true;
		else
			return false;
	}
	
	public function userExists($table,$selectval,$where){		
	
		$strSql = "SELECT ".$selectval." FROM `".$table."` where ".$where;
        $rsSql = mysqli_query($this->db_connect,$strSql);

		if($rsSql && mysqli_num_rows($rsSql)>0){	
		   $row = mysqli_fetch_object($rsSql);
			return $row;
		}
		else
			return FALSE;			
	}

	public function updateStatus($table,$data,$where) {
		
		$strSql="UPDATE `".$table."` SET ";
		$arrdata = array();
		foreach($data as $col=>$value){
		 $arrdata[] = $col . " = '". $value."'";	
		}
		$strSql .= implode(', ', $arrdata);
		$strSql .= ' WHERE '.$where;//echo $strSql;exit;
		$intCheck=mysqli_query($this->db_connect,$strSql);
		if($intCheck) 
			return $intCheck;
		else
			return FALSE;
	}	

    private function validateRequiredFields($fields,$data) {

        $errors = array();

        foreach ($fields as $field) {
            if(!isset($data[$field])) {
                array_push($errors,$field);
            }
        }

        if(!empty($errors)) {
            $res=array();
            $res["error"]="0001";
            $res["message"]="All fields are required";
            $res["following fields are missing"]=$errors;
            return $res;
        } else {
            return true;
        }

    }

}

?>