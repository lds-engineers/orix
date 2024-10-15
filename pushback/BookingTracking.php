<?php

// Endpoint for BookingTracking...
require '../db_config.php';
require 'classes.php';
$response = file_get_contents('php://input');
$data = json_decode($response);

$bookingId = $data->data->bookingId;
$BookingTracking = orixPushback::BookingTracking($data);
$dutyStatus = $BookingTracking['data'] ? $BookingTracking['data']->locations[0]['current_trip_status'] : "";
$result = [];
if($BookingTracking['status']) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, CURL_URL.'driver_location');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($BookingTracking['data']));
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'rqid: '.orixPushback::Rqid($BookingTracking['data']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    $inputString = trim($result);
    $inputString = $CFG->real_escape_string($inputString);
    orixPushback::InsertPushbackLog($bookingId, json_encode($BookingTracking['data']), $inputString, 'driver_location', json_encode($data));
    $result = json_decode($result);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    $event = orixPushback::getEventDetails($bookingId, $dutyStatus);
    if($event) {
        if($dutyStatus == 'dispatch') {
            $callDispatch = orixPushback::callDispatch($data, $bookingId);
        }
        if($dutyStatus == 'arrived') {
            $callArrived = orixPushback::callArrived($data, $bookingId);
        }
    }
} else {
    $result['status']  = "failed";
    $result['msg']  = $BookingTracking['msg'];
    $result['requestTime'] = date("Y-m-d h:i:s");
    $result['data'] = null;
    orixPushback::InsertPushbackLog($bookingId, json_encode($BookingTracking['data']), json_encode($result), 'driver_location', json_encode($data));
}

header('Content-Type: application/json');
echo $final_response = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

