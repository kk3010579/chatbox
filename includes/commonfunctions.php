<?php
	
	function rand_string( $length ) {
		$chars = time()."0123456789";
		return substr(str_shuffle($chars),0,$length);
	}
	
	function sendemail($to,$subject,$message){
		$headers = "From: webmaster@example.com\r\nReply-To: webmaster@example.com";	
		$mail_sent = @mail( $to, $subject, $message, $headers );
		if($mail_sent)
		 return true;
		else
		 return "Mail failed"; 
	}
	
	function timestamp($date=""){
		$date=date("Y-m-d H:i:s");
		if($date)
			return $date;
	    $timestamp = strtotime($date);
	    return $timestamp;
	}	
	
	function convert_time_stamp($timestamp){
	    return date('Y-m-d H:i:s', $timestamp);
	}
	
	function escape_qoutes($Input){
	    $remove = array("'",'"');
        $value = str_replace($remove,"",$Input);
	    return $value;
	}
	
	function generate_qr_code(){
		$d=date ("d");
		$m=date ("m");
		$y=date ("Y");
		$t=time();
		$dmt=$d+$m+$y+$t;    
		$ran= rand(0,10000000);
		$dmtran= $dmt+$ran;
		$un=  uniqid();
		$dmtun = $dmt.$un;
		$mdun = md5($dmtran.$un);
		$sort=substr($mdun, 16); // if you want sort length code.
		return $sort;
	}
	
	function generate_auth_token(){
		$d=date ("d");
		$m=date ("m");
		$y=date ("Y");
		$t=time();
		$dmt=$d+$m+$y+$t;    
		$ran= rand(0,10000000);
		$dmtran= $dmt+$ran;
		$un=  uniqid();
		$dmtun = $dmt.$un;
		$mdun = md5($dmtran.$dmtun);
		$sort=substr($mdun, 0,9); // if you want sort length code.
		return $sort;
	}
	
	function curPageURL() {
	 $pageURL = 'http';
	 if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	 $pageURL .= "://";
	 if ($_SERVER["SERVER_PORT"] != "80") {
	  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	 } else {
	  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	 }
	 return $pageURL;
	}
	function curServerURL() {
	 $curServerURL = 'http';
	 if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$curServerURL .= "s";}
	 $curServerURL .= "://";
	 if ($_SERVER["SERVER_PORT"] != "80") {
	  $curServerURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
	 } else {
	  $curServerURL .= $_SERVER["SERVER_NAME"];
	 }
	 return $curServerURL;
	}
	
	  
?>