<?php
	class database {
		public static $connection = null;
		// private static $strHost = 'us-cdbr-iron-east-04.cleardb.net';
  //       private static $strDatabase = 'heroku_8ef6be2faed7191';
  //       private static $strUser = 'b7ee178da1109c';
		// private static $strPass = '536a92db';
		private static $strHost = 'localhost';
        private static $strDatabase = 'db_webrtc';
        private static $strUser = 'root';
		private static $strPass = '';

		public function __construct($strHost, $strDatabase, $strUser, $strPass) {
			database::$strHost = $strHost;
			database::$strDatabase = $strDatabase;
			database::$strUser =$strUser;
			database::$strPass = $strPass;
		}
		
		public static function ConnectDB(){

            try {
                $strLink = mysqli_connect(database::$strHost, database::$strUser, database::$strPass,database::$strDatabase);
                if(!$strLink)
                    die("Connection could not be made");
                else{
                    database::$connection = $strLink;
                }
                return database::$connection;
            } catch (Exception $e) {

            }
	    }		
		
		public static function closeConnection(){
			mysqli_close(database::$connection);	
		}
			
	}
	
   //Defining Site Url Globally
   define("SITEURL","http://localhost/foodapis/");
   define("ACCESSTOKEN","AB^>AhG93b0qyJfIxfs2guVoUubW5-niR2G00:!C9miABC");
?>
