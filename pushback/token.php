<?php
error_reporting(E_ALL);

// echo 1;
// Include the file
require 'classes.php';
// Generate token
$token = orixPushback::Sign($payload, KEY, 60*60);
echo json_encode($token, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

