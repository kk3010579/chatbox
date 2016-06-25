<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

phpinfo();

ini_set("display_errors",1);
include 'config.php';

$serverURL = curServerURL();
$pageURL = curPageURL();

$serverURL = substr($pageURL, 0, strpos($pageURL, '/services'));


$id = substr($pageURL, strrpos($pageURL, '/') + 1);
$apitype = $id ? $id : "";

$data = $_POST;
if (!isset($_POST) || !$_POST) {
    # code...
    $data = (array)json_decode(file_get_contents('php://input'), true);
}
//(array)json_decode(file_get_contents('php://input'), true);//$_POST;//
//echo $data["access_token"];exit;

if($apitype == 'user_image'){
    $data['access_token']=$_POST['access_token'];
    $user_name = $_POST['user_name'];
}


//system allowed apis
$allowedApis = array('user_signup', 'login_user', 'edit_user_profile', 'edit_user_password',
                        "user_image", "get_user_info", 'find_users', 'send_invitation', 'invite_accept_status', 
                        "check_sender_invite", "check_receiver_invite", "get_followers", "get_followings", 
                        "send_message", "check_new_message", "get_all_messages");
                        
//validate if api is allowed in the system
validateApi($apitype,$allowedApis);
//validate access token
validateAccessToken($data);
//as all apis request method will be post so make so validate it here for all of them
validateRequestType('POST');

//validate apis request method
function validateRequestType($requestType) {
    if($_SERVER['REQUEST_METHOD'] != $requestType) {
        $data = array();
        $data["error"] = "0052";
        $data["message"] = "Request Method not allowed";
        $data = json_encode($data);
        echo $data;
        exit;
    }
}

//validate access token
function validateAccessToken($data) {

    //verify access token
    if(!isset($data['access_token'])) {
        $data = array();
        $data["error"] = "0051";
        $data["message"] = "Access token is required";
        $data = json_encode($data);
        echo $data;
        exit;
    }

    if($data['access_token'] != ACCESSTOKEN) {
        $data=array();
        $data["success"]="0051";
        $data["message"]="Invalid access token";
        $data = json_encode($data);
        echo $data;
        exit;
    }       
}

//validate if api is allowed in the system
function validateApi($apitype,$allowedApis){
    if(!in_array($apitype,$allowedApis)) 
    {      
        $data=array();
        $data["error"]="0050";
        $data["message"]="Invalid API Type";
        $data = json_encode($data);
        echo $data;
        exit;
       
    }
}


/*utilities function */

$response=array();

switch ($apitype) {
    case 'user_image':{
        
        if(isset($user_name) && !($user_name)){
            $response['error'] = "0002";
            $response['message'] =  'no post values';
            break;
        }
        $photo_url = '';
        if(isset($_FILES['photo']) && ($_FILES['photo'])){
            $photo_dir = 'Profilepix/';//.'consumer/'.'thumbnail/';
            $upload_dir = './'.$photo_dir;
            $dirCreated = (!is_dir($upload_dir)) ? @mkdir($upload_dir, 0777):TRUE;
            
            $file_name = $_FILES['photo']['name'];
            //echo $file_name;exit;
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            
            $Profile_pix = $user_name.'_'.date('Ymdhis').'.'.$ext;
            $photo_path = $upload_dir.$Profile_pix;
            
            // upload photo
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
            
            $photo_url= $serverURL.$photo_dir.$Profile_pix;
        }
        
        if($photo_url) {
            $response['error'] = "0000";
            $response['message'] =  'image uploaded';
            $response['photo_url'] = $photo_url;
            $response['photo_name'] = $Profile_pix;
        }
        else {
            $response['error'] = "0007";
            $response['message'] =  'image upload failed';
        }       
        
        break;
    }
    // case 'get_consumer_info':{
    //     $consumer = new consumer();
    //     $response = $consumer->consumer_info($data);
    //     if($response['error']=="0000"){
    //         $Profile_pix = $response['data']->Profile_pix;
    //         $photo_dir = '/Profilepix/'.'consumer/'.'thumbnail/';
    //         $photo_path= $photo_dir.$Profile_pix;
    //         if (!file_exists('../'.$photo_path) || empty($Profile_pix)) {
    //             $photo_url = '';
    //         }else{
    //             $photo_url= $serverURL.$photo_path;
    //         }
            
    //         $response['data']->Profile_pix = $photo_url;
    //     }
    //     break;
    // }
    case 'get_user_info':{
        $user = new users();
        $response = $user->user_info($data);//var_dump($response);exit;
        // if($response['error']=="0000"){
        //     $Profile_pix = $response['data']->avatar;
        //     $photo_dir = '/Profilepix/';//.'staff/'.'thumbnail/';
        //     $photo_path = $photo_dir.$Profile_pix;//var_dump($photo_path);exit;
        //     if (!file_exists('./'.$photo_path) || empty($Profile_pix)) {
        //         $photo_url = '';
        //     }else{
        //         $photo_url = $serverURL.$photo_path;
        //     }
            
        //     $response['data']->avatar = $photo_url;
        // }
        break;
    }
    case 'user_signup':{
        $user = new users();
        $response = $user->user_registration($data);
        break;
    }
    case 'login_user':{
        $user = new users();
        $response = $user->user_authentication($data);
        break;
    }
    case 'edit_user_profile':{
        $user = new users();
        $response = $user->edit_profile($data);
        break;
    }
    case 'edit_user_password':{
        $user = new users();
        $response = $user->edit_password($data);
        break;
    }
    case 'find_users': {
        $user = new users();
        $response = $user->find_users($data);
        break;
    }
    case 'send_invitation': {
        $invitation = new invitation();
        $response = $invitation->invitation_register($data);
        break;
    }
    case 'invite_accept_status': {
        $invitation = new invitation();
        $response = $invitation->set_acceptStatus($data);
        break;
    }
    // case 'check_sender_invite': {//check the invitation status in sender side
    //     $invitation = new invitation();
    //     $response = $invitation->set_acceptStatus($data);
    //     break;
    // }
    // case 'check_receiver_invite': {//check the invitation status in receiver side
    //     $invitation = new invitation();
    //     $response = $invitation->set_acceptStatus($data);
    //     break;
    // }
    case 'get_followers': {
        $invitation = new invitation();
        $response = $invitation->get_followers($data);
        break;
    }
    case 'get_followings': {
        $invitation = new invitation();
        $response = $invitation->get_followings($data);
        break;
    }
    case 'send_message': {
        $messages = new messages();
        $response = $messages->message_insert($data);
        break;
    }
    case 'check_new_message': {
        $messages = new messages();
        $response = $messages->check_new_message($data);
        break;
    }
    case 'get_all_messages': {
        $messages = new messages();
        $response = $messages->get_all_messages($data);
        break;
    }
    default:{
        $response["error"]="0050";
        $response["message"]="Invalid API Type";
        break;
    }
}

$response = json_encode($response);
echo $response;
exit;
