<?php

function send_PushNotification($device_token_array, $push_msg, $locktime=null){
    foreach ($device_token_array as $key=>$value){
        $platform = $value['platform'];
        $token = $value['token'];
        switch ($platform){
            case "1": // iOS
                send_iOS_PushNotification($token, $push_msg, 1, 'default', $locktime);
            break;
            case "2": // Android
                $pushMessage = array(
                    'vibrate'   => 1,
                    'sound'     => 'default',
                    'type' => 1,
                    'msg'   => $push_msg,
                );
                $registration_ids = array();
                $registration_ids[0] = $token;
                send_Android_PushNotification($registration_ids, $pushMessage);
            break;
            
        }
    }
}

function send_iOS_PushNotification( $deviceToken, $message, $badge=1, $sound='default', $locktime=null){
    //$apnsHost = 'gateway.sandbox.push.apple.com';
    //$apnsCert = 'aps_dev.pem';//aps_development.pem
    
    //
    $apnsHost = 'gateway.push.apple.com';
    $apnsCert = 'push-development.pem';//push-production.pem
    
    $apnsPort = 2195;
    
    $streamContext = stream_context_create();
    stream_context_set_option($streamContext, 'ssl', 'local_cert', $apnsCert);

    $apns = stream_socket_client('ssl://'.$apnsHost.':'.$apnsPort, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);

    if($apns)
    {
        if ($locktime !== null){
            $payload = array('aps' => array('alert' => $message, 'badge' => $badge, 'sound' => $sound, 'locktime' => $locktime));    
        }
        else {
            $payload = array('aps' => array('alert' => $message, 'badge' => $badge, 'sound' => $sound));
        }
        
        $payload = json_encode($payload);

        $apnsMessage = chr(0).chr(0).chr(32).pack('H*', str_replace(' ', '', $deviceToken)).chr(0).chr(strlen($payload)).$payload;
        fwrite($apns, $apnsMessage);
        fclose($apns);
        return "success";
    }else{
        return "Failed to connect $error $errorString \n";
    }
}
    
function send_Android_PushNotification($registatoin_ids, $message) {
    $google_api_key = "AIzaSyCuN2bDUkjDmGc6RZh4vPo5S6QWhp8qGBM";
    // Set POST variables
    $url = 'https://android.googleapis.com/gcm/send';
    
    $fields = array(
        'registration_ids' => $registatoin_ids,
        'data' => $message,
    );
    
    $headers = array(
        'Authorization: key=' . $google_api_key,
        'Content-Type: application/json'
    );
    //print_r($headers);
    // Open connection
    $ch = curl_init();
    
    // Set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Disabling SSL Certificate support temporarly
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

    // Execute post
    $result = curl_exec($ch);
    if ($result === FALSE) {
        die('Curl failed: ' . curl_error($ch));
    }

    // Close connection
    curl_close($ch);
    return $result;
}

?>