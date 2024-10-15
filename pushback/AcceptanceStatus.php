<?php

// Endpoint for AcceptanceStatus...
require '../db_config.php';
require 'classes.php';
$client = isset($_GET['client']) ? $_GET['client'] : ''; 
$serviceProviderResponse = isset($_GET['serviceProviderResponse']) ? $_GET['serviceProviderResponse'] : ''; 
$bookingId = isset($_GET['bookingId']) ? $_GET['bookingId'] : '';
$data = new stdClass(); 
$data->client = $client; 
$data->serviceProviderResponse = $serviceProviderResponse; 
$data->bookingId = $bookingId; 
$AcceptanceStatus = orixPushback::AcceptanceStatus($data);
$result = [];
// echo "<pre>";
// echo "Requested paramt for myf (booking_confirmation)";
// print_r($data);
// echo "</pre>";
// echo "<br>";
if($AcceptanceStatus['status']) {
    /* 1. Booking Confirmation*/
    /*Call curl request start*/
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, CURL_URL.'booking_confirmation');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($AcceptanceStatus['data']));
    $headers = [];
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'rqid: '.orixPushback::Rqid($AcceptanceStatus['data']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    $inputString = trim($result);
    $inputString = $CFG->real_escape_string($inputString);
    orixPushback::InsertPushbackLog($bookingId, json_encode($AcceptanceStatus['data']), $inputString, 'booking_confirmation', json_encode($data));
    $result = json_decode($result);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    $dataNew = new stdClass();
    $dataNew->ext_booking_number = $bookingId;
    $dataNew->trn = isset($result) ? $result->trn : ""; 
    $dataNew->status = isset($result) ? $result->status : "";
    orixPushback::InsertBookingStatus($dataNew);
    /*Call curl request end*/
} else {
    $result['status']  = "failed";
    $result['msg']  = $AcceptanceStatus['msg'];
    $result['requestTime'] = date("Y-m-d h:i:s");
    $result['data'] = null;
    orixPushback::InsertPushbackLog($bookingId, json_encode($AcceptanceStatus['data']), json_encode($result), 'booking_confirmation', json_encode($data));
}

header('Content-Type: application/json');
echo $final_response = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

