<?php

// Endpoint for BookingTripEndDetails...
require '../db_config.php';
require 'classes.php';
$response = file_get_contents('php://input');
$data = json_decode($response); 
$bookingId = $data->data->bookingId;
$BookingTripEndDetails = orixPushback::BookingTripEndDetails($data);
$result = [];
// $CloseDuty = orixPushback::CloseDuty($data);
if($BookingTripEndDetails['status']) {
    
    /*Call curl request start*/
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, CURL_URL.'end');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($BookingTripEndDetails['data']));

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'rqid: '.orixPushback::Rqid($BookingTripEndDetails['data']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    $inputString = trim($result);
    $inputString = $CFG->real_escape_string($inputString);
    orixPushback::InsertPushbackLog($bookingId, json_encode($BookingTripEndDetails['data']), $inputString, 'end', json_encode($data));
    $result = json_decode($result);
    if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    /*Call curl request end*/

    if ($result->status == "success") {
        $CloseDuty = orixPushback::CloseDuty($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, CURL_URL.'closed');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($CloseDuty['data']));
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'rqid: '.orixPushback::Rqid($CloseDuty['data']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        orixPushback::InsertPushbackLog($bookingId, json_encode($CloseDuty['data']), $result, 'closed', json_encode($data));
        $result = json_decode($result);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
    }

} else {
    $result['status']  = "failed";
    $result['msg']  = $BookingTripEndDetails['msg'];
    $result['requestTime'] = date("Y-m-d h:i:s");
    $result['data'] = null;
    orixPushback::InsertPushbackLog($bookingId, json_encode($BookingTripEndDetails['data']), json_encode($result), 'end', json_encode($data));
}

header('Content-Type: application/json');
echo $final_response = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

