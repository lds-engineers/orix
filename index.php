<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 'On');
require 'db_config.php';
require 'apicall.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');
header("HTTP/1.0 200 Successfull operation");
$getpatameter=json_decode(file_get_contents('php://input',True),true);
$reqdata = json_encode($getpatameter);
$createdtime = time();

try{
$qry_addlog = "insert into myfcall(reqdata,createdtime) values('$reqdata',$createdtime)";
mysqli_query($CFG, $qry_addlog);
	if($getpatameter['event_name'] == "booking_creation"){
		
		$orixapi->booking($getpatameter);	
	} else if($getpatameter['event_name'] == "cancel_booking"){
		$orixapi->cancelbooknig($getpatameter);	
		// $qry_addlog = "insert into myfcall(reqdata,createdtime) values('$reqdata',$createdtime)";
		// mysqli_query($CFG, $qry_addlog);
	} else if($getpatameter['event_name'] == "booking_modify" || $getpatameter['event_name'] == "ModifyCabManBookingAssignment" || $getpatameter['event_name'] == "ModifyDispatchedCabManBooking"){
		 
		$orixapi->modifybooknig($getpatameter);	
		// $qry_addlog = "insert into myfcall(reqdata,createdtime) values('$reqdata',$createdtime)";
		// mysqli_query($CFG, $qry_addlog);
	}else if ($getpatameter['event_name'] == "bill_approval"){
		$orixapi->billApproval($getpatameter);
		$qry_addlog = "insert into myfcall(reqdata,createdtime) values('$reqdata',$createdtime)";
		mysqli_query($CFG, $qry_addlog);
	} else if($getpatameter['event_name'] == "mis"){
		$orixapi->misbookingdetails($getpatameter);

	} else {
			$finalresopnse = new StdClass();
			$finalresopnse->status="error";
			$finalresopnse->errorcode="1207";
			$finalresopnse->requestTime=date("d m Y h:i:s A");
			$finalresopnse->message="Please pass event name";
			$resdata = json_encode($finalresopnse);

		$qry_addlog = "insert into myfcall(reqdata,resdata,createdtime) values('$reqdata','$resdata',$createdtime)";
		mysqli_query($CFG, $qry_addlog);
		echo json_encode($finalresopnse);
	}

 } 
catch(Exception $e) {
	print_r($e);
	    $cancelfinalresopnse = new StdClass();
		$cancelfinalresopnse->status="error1";
		$cancelfinalresopnse->cdata="";
		$cancelfinalresopnse->requestTime=date("Y-m-d H:s:i");
		$cancelfinalresopnse->message=$e->getMessage();
	
}

?>
