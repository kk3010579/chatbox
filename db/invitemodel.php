<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

include_once 'push.php'; //push notification module
include_once 'db/usermodel.php';

class invitation extends database{

	public $db_connect = null;
	public $db_table = null;
	
	public function __construct() {

	 	$this->db_connect = parent::ConnectDB();
		$this->db_table = "invitation";
	}

	public function invitation_register($dat){	
		
		$data = array();
        $requiredParams = array("senderID", "receiverID", "senderDeviceToken", "receiverDeviceToken", "receiverUserName");

        $paramsValid = $this->validateRequiredFields($requiredParams, $dat);

		if($paramsValid === true) {
			$data['senderID'] = $dat["senderID"];
			$data['receiverID'] = $dat["receiverID"];
			// $data['senderDeviceToken'] = $dat["senderDeviceToken"];
			// $data['receiverDeviceToken'] = $dat['receiverDeviceToken'];
			$data['status'] = "0";//1: accept, 0: none, -1: decline
            
			$sender_exists = $this->userExists("users","id","id='".$data["senderID"]."'");
			$receiver_exists = $this->userExists("users","id","id='".$data["receiverID"]."'");

            if(!$sender_exists || !$receiver_exists) {
			        $res = array();
					$res["error"] = "0012";
					$res["message"] = "The users does not exist. Please try again!";
					return $res;
			}
			else {

				if ($this->userExists("invitation", "id", "senderID = '".$data['senderID']."' AND receiverID = '".$data['receiverID']."'")) {//The invitation between sender and receiver was already existed.

					$insert_data = $this->updateStatus($this->db_table, $data, "senderID = '".$data['senderID']."' AND receiverID = '".$data['receiverID']."'");

				}else{//insert new invitation

					$insert_data = $this->addInvitation($this->db_table, $data);
				}

				if($insert_data) {

                    $message = "You received the invitation: ".$dat["receiverUserName"]." would like to add you as a contact.";

                    //insert the invitation column in <invitation> table
                    $success = send_iOS_PushNotification($dat["receiverDeviceToken"], $message, $badge=1, $sound='default', $locktime=null);

					$res = array();
                    if ($success == "success") {
						$res["error"] = "0000";
                    	$res["message"] = "Your invitation was sent to ".$dat["receiverUserName"]." successfully.";
                    }else{
                    	$res["error"] = "0013";
                    	$res["message"] = $success;
                    }

					return $res;	 
				}
				else {
					$res = array();
					$res["error"] = "0014";
					$res["message"] = "There are some errors. Please try again later";
					return $res;
				 }
		    } 
		 }else{
            return $paramsValid;
        }
    }
	
	// public function user_authentication($dat){

 //        $requiredParams = array("email", "password", "devicetoken");
 //        $paramsValid = $this->validateRequiredFields($requiredParams,$dat);

 //        if($paramsValid === true) {

	// 		$data['email'] = $dat["email"];
	// 		$data['password'] = $dat["password"];			
	// 		if($data['email'] && $data['password']) {
	// 			$data['password'] = md5($dat["password"]);
	// 			$where = "email='".$data['email']."' AND password='".$data['password']."'";
	// 			//echo $where;
	// 			$responce = $this->selectUser($this->db_table,"email",$where);
	// 			if($responce){
	// 				$res = array();
	// 				$res["error"] = "0000";
 //                    $res["message"] = "User authenticated";

 //                    //update the device token for specific user
 //                    $deviceToken = $dat['devicetoken'];
 //                    $email = $dat['email'];
 //                    if ($deviceToken) {
 //                    	$updateSql = "UPDATE users SET devicetoken = '".$deviceToken."' WHERE email = '".$email."'";
 //                    	// echo $updateSql;exit;
	// 					$updateStatus = mysqli_query($this->db_connect, $updateSql);
 //                    }
	// 				return $res;
	// 			}else{
	// 				$res = array();
	// 				$res["error"] = "0002";
	// 				$res["message"] = "There are some error(Invalid Credentials)";	
	// 				return $res;
	// 			}
	// 	    }		  
	// 	}
	// 	else{
 //            return $paramsValid;
	// 	}
	// }
	
	public function set_acceptStatus($dat){	//edit the invitation status like accept or decline
		
		$data = array();

        $requiredParams = array("my_id", "senderID", "isAccepted");

        $paramsValid = $this->validateRequiredFields($requiredParams,$dat);

		if($paramsValid === true) {//checking parameter valid

			$data['senderID'] = $dat['senderID'];
			$data['receiverID'] = $dat['my_id'];
			$data['status'] = $dat['isAccepted'];//1: accepted, -1: decline, 0: none

			$me_exists = $this->userExists("users","id","id = '".$dat["my_id"]."'");
			$sender_exists = $this->userExists("users","id","id = '".$dat["senderID"]."'");


			if ($me_exists && $sender_exists) {//checking sender and receiver existance
				$update_status = $this->updateStatus($this->db_table, $data, "senderID = '".$data['senderID']."' AND receiverID = '".$data['receiverID']."'");

				if($update_status) {							
					$res = array();
					$res["error"] = "0000";
                    $res["message"] = "Invitation Status updated";

                    //get the user info corresponding sender ID and receiver ID
                    $user = new users();
                    $receiver = $user->selectUser("users","id, username, devicetoken", "id = '".$data['receiverID']."'");
                    $sender = $user->selectUser("users","id, username, devicetoken", "id = '".$data['senderID']."'");

                    // var_dump($receiver->username);
                    // var_dump($sender);exit;

                    if($data['status'] == "1"){//accepted
                    	$message = "Your invitation was accepted by ".$receiver->username;
                    }else{
                    	$message = "Your invitation was declined by ".$receiver->username;
                    }

                    $success = send_iOS_PushNotification($sender->devicetoken, $message, $badge=1, $sound='default', $locktime=null);

					return $res;	 
				}
				else {
					$res = array();
					$res["error"] = "0015";
					$res["message"] = "There are some errors. Please try again later!";	
					return $res;	
				}

			}else{
			    $res = array();
				$res["error"] = "0016";
				$res["message"] = "There are some errors(The sender does not exist on DB)";	
				return $res;	
			}
		}
		else{
		    $res = array();
			$res["error"] = "0017";
			$res["message"] = "There are some error(All fields are required)";	
			return $res;
		}
    }	

    public function get_followers($dat) {
    	$data = array();

        if (isset($dat['my_id']) && $dat['my_id']) {
        	$data['receiverID'] = $dat['my_id'];

        	$followers = $this->getInvitionsFor("invitation, users", "users.id, username, avatar, email, devicetoken", "invitation.receiverID = '".$data['receiverID']."' AND invitation.senderID = users.id AND invitation.status != -1");

        	if(empty($followers)) {
				$res = array();
            	$res["error"] = "0018";
            	$res["message"] = "There is no search results!";
            	return $res;
			}
			else {
            	$res = array();
            	$res["error"] = "0000";
            	$res["data"] = $followers;
            	return $res;
        	}
        }else{
        	$res = array();
			$res["error"] = "0019";
			$res["message"] = "There are some error(All fields are required)";	
			return $res;
        }
    }

    public function get_followings($dat) {
    	$data = array();

     	if (isset($dat['my_id']) && $dat['my_id']) {
        	$data['senderID'] = $dat['my_id'];//SELECT users.id, username, email, avatar, devicetoken FROM invitation, users WHERE invitation.senderID = 1 AND invitation.receiverID = users.id

        	$followings = $this->getInvitionsFor("invitation, users", "users.id, username, avatar, email, devicetoken", "invitation.senderID = '".$data['senderID']."' AND invitation.receiverID = users.id AND invitation.status != -1");

        	if(empty($followings)) {
				$res = array();
            	$res["error"] = "0020";
            	$res["message"] = "There is no results!";
            	return $res;
			}
			else {
            	$res = array();
            	$res["error"] = "0000";
            	$res["data"] = $followings;
            	return $res;
        	}

        }else{
        	$res = array();
			$res["error"] = "0021";
			$res["message"] = "There are some error(All fields are required)";	
			return $res;
        }
    }

    public function getInvitionsFor($table, $coloumn, $where){
				
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
		
	  
    public function addInvitation($table,$data) {	
		
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
		$intCheck=mysqli_query($this->db_connect, $strSql);
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