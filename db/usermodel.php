<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');




class users extends database{
	  
	public $db_connect = null;
	public $db_table = null;
	
	public function __construct() {

	 	$this->db_connect = parent::ConnectDB();
		$this->db_table = "users";
	}
	
	public function user_registration($dat){	
		
		$data = array();
        $requiredParams = array("email", "username", "password", "avatar", "devicetoken");

        $paramsValid = $this->validateRequiredFields($requiredParams,$dat);

		if($paramsValid === true) {
			$data['email'] = $dat["email"];
			$data['username'] = $dat["username"];
			$data['password'] = md5($dat["password"]);
			$data['avatar'] = $dat['avatar'];
			$data['devicetoken'] = $dat['devicetoken'];

			//uploading avatar image to server
			//
            
			$user_exists=$this->userExists($this->db_table,"email","email='".$data["email"]."'");

            if($user_exists) {
			        $res=array();
					$res["error"]="0001";
					$res["message"]="There are some error(User already exists)";
					return $res;
			}
			else {
			    $insert_data=$this->insertUser($this->db_table,$data);
				if($insert_data) {							
					$res=array();
					$res["error"]="0000";
                    $res["message"]="User registered successfully";
					return $res;	 
				}
				else {
					$res=array();
					$res["error"]="0002";
					$res["message"]="User could not be registered, please try again later";
					return $res;
				 }
		    } 
		 }else{
            return $paramsValid;
        }
    }
	
	public function user_authentication($dat){

        $requiredParams = array("email", "password", "devicetoken");
        $paramsValid = $this->validateRequiredFields($requiredParams,$dat);

        if($paramsValid === true) {

			$data['email'] = $dat["email"];
			$data['password'] = $dat["password"];			
			if($data['email'] && $data['password']) {
				$data['password'] = md5($dat["password"]);
				$where = "email='".$data['email']."' AND password='".$data['password']."'";
				//echo $where;
				$responce = $this->selectUser($this->db_table,"email",$where);
				if($responce){
					$res = array();
					$res["error"] = "0000";
                    $res["message"] = "User authenticated";

                    //update the device token for specific user
                    $deviceToken = $dat['devicetoken'];
                    $email = $dat['email'];
                    if ($deviceToken) {
                    	$updateSql = "UPDATE users SET devicetoken = '".$deviceToken."' WHERE email = '".$email."'";
                    	// echo $updateSql;exit;
						$updateStatus = mysqli_query($this->db_connect, $updateSql);
                    }
					return $res;
				}else{
					$res = array();
					$res["error"] = "0003";
					$res["message"] = "There are some error(Invalid Credentials)";	
					return $res;
				}
		    }		  
		}
		else{
            return $paramsValid;
		}
	}
	
	public function edit_profile($dat){	
		
		$data = array();

        $requiredParams = array("email", "username","avatar");

        $paramsValid = $this->validateRequiredFields($requiredParams,$dat);

		if($paramsValid === true) {

            $data["username"] = $dat["username"];
            //uploading avatar image to server
            $data["avatar"] = $dat["avatar"];

			$user_exists = $this->userExists($this->db_table,"email","email='".$dat["email"]."'");
			if(!$user_exists) {
			        $res = array();
					$res["error"] = "0004";
					$res["message"] = "There are some error(No user exists with given email)";	
					return $res;
			}
			else{				
			    $update_data=$this->updateUser($this->db_table,$data,"email='".$dat["email"]."'");
				if($update_data) {							
					$res = array();
					$res["error"] = "0000";
                    $res["message"] = "Profile updated";
					return $res;	 
				}
				else {
					return $requiredParams;
				}
		    } 
		}
		else{
		        $res = array();
				$res["error"] = "0005";
				$res["message"] = "There are some error(All fields are required)";	
				return $res;
		}
    }	
	
	public function edit_password($dat){	
		
		$data = array();

        $requiredParams = array("email","current_password","new_password");

        $paramsValid = $this->validateRequiredFields($requiredParams,$dat);

		if($paramsValid === true) {

            $dat["current_password"] = md5($dat["current_password"]);
			$user_exists=$this->userExists($this->db_table,"email","email='".$dat["email"]."' AND password='".$dat["current_password"]."'");

            if(!$user_exists) {
			        $res=array();
					$res["error"]="0006";
					$res["message"]="There are some error(Email or Password given is wrong)";	
					return $res;
			}
			else {
                $data['password'] = md5($dat["new_password"]);
			    $update_data=$this->updateUser($this->db_table,$data,"email='".$dat["email"]."'");
				if($update_data) {							
					$res = array();
					$res["error"] = "0000";
                    $res["message"] = "Password updated";
					return $res;	 
				}
				else {
					$res = array();
					$res["error"] = "0007";
					$res["message"] = "There are some error(Something went wrong)";
					return $res;
				}
		    } 
		}
		else{
				return $paramsValid;
		}
    }	
		
	  
    public function insertUser($table,$data) {	
		
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

    public function user_info($dat) {

        if(isset($dat["email"]) && $dat["email"]) {

            $email = $dat["email"];
            $user = $this->selectUser("users","*","email='".$email."'");
            if(empty($user)) {
				$res = array();
            	$res["error"] = "0008";
            	$res["message"] = "Email not found";
            	return $res;
			}
			else {
            	$res = array();
            	$res["error"] = "0000";
            	$res["data"] = $user;
            	return $res;
        	}
		}
        else {
            $res = array();
            $res["error"] = "0009";
            $res["message"] = "There are some error(Email id is missing or invalid)";
            return $res;
        }
    }
	
    public function find_users($dat) {
    	if (isset($dat["find_str"]) && $dat["find_str"] && isset($dat["my_id"]) && $dat["my_id"]) {
    		$find_str = $dat["find_str"];
    		$my_id = $dat["my_id"];
    		$where = "(username like '%$find_str' OR username like '%$find_str%' OR username like '$find_str%' OR username='$find_str') AND id != $my_id";
    		$users = $this->getUserInfoFor("users", "`id`, `username`, `email`, `avatar`", $where);
    		// return json_encode($users);
    		if(empty($users)) {
				$res = array();
            	$res["error"] = "0010";
            	$res["message"] = "There is no search results!";
            	return $res;
			}
			else {
            	$res = array();
            	$res["error"] = "0000";
            	$res["data"] = $users;
            	return $res;
        	}
    	}else{
    		$res = array();
            $res["error"] = "0011";
            $res["message"] = "There are some error(The search character is null)";
            return $res;
    	}
    }

	public function getUserInfoFor($table, $coloumn, $where){
				
		$strSql = "SELECT ".$coloumn." FROM ".$table;

		if($where)
			$strSql = $strSql ." where ".$where;	

		// if($orderby)
		 	// $strSql = $strSql ." order by ".$orderby;
	 
	// return $strSql;exit;
		$rsSql = mysqli_query($this->db_connect, $strSql);
		// if($fetchall){
			$data = array();
			if(mysqli_num_rows($rsSql) > 0){
			    while($row = mysqli_fetch_assoc($rsSql)){
				 	$data[] = $row;
				}
				return $data;		
		    }
			else{
				return FALSE;	
			}
	 //    }
		// else{
		// 	$row = mysqli_fetch_object($rsSql);
		// 	if(mysqli_num_rows($rsSql)>0)	
		// 		return $row;
		// 	else
		// 		return FALSE;			
	 //    }	
	}

	public function selectUser($table, $coloumn, $where){//,$fetchall="",$orderby=""
				
		$strSql = "SELECT ".$coloumn." FROM `".$table."`";
		if($where)
		 	$strSql = $strSql ." where ".$where;	
		// if($orderby)
		//  	$strSql = $strSql ." order by ".$orderby;
	 
	 // echo $strSql;exit;
		$rsSql = mysqli_query($this->db_connect,$strSql);
		// if($fetchall){
		// 	$data = array();
		// 	if(mysqli_num_rows($rsSql) > 0){
		// 	    while($row = mysqli_fetch_object($rsSql)){
		// 	    	array_push($data, $row);
		// 		 // $data[] = $row;
		// 		}
		// 		return $data;		
		//     }
		// 	else{
		// 		return FALSE;	
		// 	}
	 //    }
		// else{
			$row = mysqli_fetch_object($rsSql);
			if(mysqli_num_rows($rsSql)>0)	
				return $row;
			else
				return FALSE;			
	    // }	
	}
	
	public function updateUser($table, $data, $where) {
		
		$strSql = "UPDATE `".$table."` SET ";
		$arrdata = array();
		foreach($data as $col=>$value){
		 $arrdata[] = $col . " = '". $value."'";	
		}
		$strSql .= implode(', ', $arrdata);
		$strSql .= ' WHERE '.$where;
		$intCheck=mysqli_query($this->db_connect,$strSql);
		if($intCheck) 
			return $intCheck;
		else
			return FALSE;
	}	
	
	public function deleteUser($table,$where) {
		
		$strSql="DELETE FROM `".$table."` WHERE ".$where;
		$iCheck=mysqli_query($this->db_connect,$strSql);
		if(mysqli_affected_rows($this->db_connect)>0)
		 return TRUE;
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