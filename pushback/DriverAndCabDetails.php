<?php

// Endpoint for DriverAndCabDetails...
require '../db_config.php';
require 'classes.php';
$response = file_get_contents('php://input');
$data = json_decode($response);
$bookingId = $data->data->bookingId; 

$reassignment = false;
if(orixPushback::getAssignmentDetails($bookingId)) {
    $callVoidDuty = orixPushback::callVoidDuty($data);
    $DriverAndCabDetails = orixPushback::driverReassign($data);
    $reassignment = true;
} else {
    $DriverAndCabDetails = orixPushback::DriverAndCabDetails($data);
}

$result = [];
if($DriverAndCabDetails['status']) {
    /* 1. Driver Assignment*/
    /*Call curl request start*/
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, CURL_URL.'assigned');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($DriverAndCabDetails['data']));
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'rqid: '.orixPushback::Rqid($DriverAndCabDetails['data']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    // print_r($DriverAndCabDetails['data']);
    $inputString = trim($result);
    $inputString = $CFG->real_escape_string($inputString);
    if($reassignment){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, CURL_URL.'void_duty');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($callVoidDuty['data']));
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'rqid: '.orixPushback::Rqid($callVoidDuty['data']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $inputString = trim($result);
        $inputString = $CFG->real_escape_string($inputString);
        orixPushback::InsertPushbackLog($bookingId, json_encode($callVoidDuty['data']), $inputString, 'void_duty', json_encode($data));
    }
    orixPushback::InsertPushbackLog($bookingId, json_encode($DriverAndCabDetails['data']), $inputString, 'assigned', json_encode($data));
    $result = json_decode($result);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    /*Call curl request end*/
} else {
    $result['status']  = "failed";
    $result['msg']  = $DriverAndCabDetails['msg'];
    $result['requestTime'] = date("Y-m-d h:i:s");
    $result['data'] = null;
    orixPushback::InsertPushbackLog($bookingId, json_encode($DriverAndCabDetails['data']), json_encode($result), 'assigned', json_encode($data));
}

header('Content-Type: application/json');
echo $final_response = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

