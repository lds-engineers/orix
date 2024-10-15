<?php

// Endpoint for BookingInvoice...
require '../db_config.php';
require 'classes.php';
$response = file_get_contents('php://input');
$data = json_decode($response); 
$bookingId = $data->data->bookingId;
$BookingInvoice = orixPushback::BookingInvoice($data);
$result = [];
if($BookingInvoice['status']) {
    /* 1.13. Generate Invoice */
    /* Call curl request start */
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, CURL_URL.'generate_bill');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($BookingInvoice['data']));
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'rqid: '.orixPushback::Rqid($BookingInvoice['data']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    orixPushback::InsertPushbackLog($bookingId, json_encode($BookingInvoice['data']), $result, 'generate_bill', json_encode($data));
    $result = json_decode($result);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    /* Call curl request end */
} else {
    $result['status']  = "failed";
    $result['msg']  = $BookingInvoice['msg'];
    $result['requestTime'] = date("Y-m-d h:i:s");
    $result['data'] = null;
    orixPushback::InsertPushbackLog($bookingId, json_encode($BookingInvoice['data']), json_encode($result), 'generate_bill', json_encode($data));
}

header('Content-Type: application/json');
echo $final_response = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

