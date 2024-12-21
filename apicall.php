<?php
require 'filexml.php';
class orixapi{
	public $error=0;
	public $response="";
	public $XMLCALL;
	public $headerdata;
	public $headerxml;
	public $bodyxml;
	public $CFG;
	private $customername;
	private $customerpassword;
	private $uniqueid;

	public function orixapi(){
		$this->XMLCALL = new myxml();
		$this->headerdata = array('tem:AuthHeader'=> array('tem:InterfaceUserName' => $this->customername,'tem:InterfacePassword' => $this->customerpassword,'tem:InterfaceUniqueId' => $this->unicustomernamequeid));
		$this->headerxml = $this->XMLCALL->listRolesRecursive($this->headerdata);
	}
	public function mapheader(){
		$this->XMLCALL = new myxml();
		$this->headerdata = array('tem:AuthHeader'=> array('tem:InterfaceUserName' => $this->customername,'tem:InterfacePassword' => $this->customerpassword,'tem:InterfaceUniqueId' => $this->uniqueid));
		$this->headerxml = $this->XMLCALL->listRolesRecursive($this->headerdata);
	}
	public function booking($getpatameter) {
		global $CFG;
		$this->erroravailable($getpatameter, "booking");
		$errres = new stdClass();
		$errres->errorcode = "";
		$errres->status = "error";
		$errres->requestTime=date("d m Y h:i:s A");
		$errres->message = "";
		$city = "";
		$qry="SELECT * from city_info WHERE city_id = ". $getpatameter['city_id'];
		//$qrydata = mysqli_query($CFG, $qry);
		$result = mysqli_fetch_array(mysqli_query($CFG, $qry));
		if(empty($result)){
			$errres->errorcode = "1001";
			$errres->message = "Invalid city id";
		} else {
			if(!empty($result['city_synonyms'])){
				$city=$result['city_synonyms'];
			} else {
				$errres->errorcode = "1001";
				$errres->message = "city id not mapped";
			}
		}
		$VehicleTypeCode = "";
		$qry_category="SELECT * FROM `vehiclemap` WHERE myfcategoryid = ".$getpatameter['category_id']." order  by myfvehicleid desc";
		$result_category = mysqli_fetch_array(mysqli_query($CFG, $qry_category));
		if(empty($result_category)){
			$errres->errorcode = "1001";
			$errres->message = "Invalid Category";
		} else {
			if($getpatameter['model_id'] != 0){
				$qry_model="SELECT * FROM `vehiclemap` WHERE myfcategoryid = ".$getpatameter['category_id']." and myfvehicleid = ". $getpatameter['model_id'];
				$result_model = mysqli_fetch_array(mysqli_query($CFG, $qry_model));
				if(!empty($result_model)){
					$VehicleTypeCode = $result_model['vehiclecode'];
				} else {
					$errres->errorcode = "1001";
					$errres->message = "Invalid Model";
				}
			} else {
				$VehicleTypeCode = $result_category['vehiclecode'];
			}
		}
		$packagedata = $this->getpackage($getpatameter);
		if($packagedata->errorcode){
			$errres->errorcode = $packagedata->errorcode;
			$errres->message = $packagedata->message;
		}
		$packagename = $packagedata->packagename;
		$packagecategory = $packagedata->packagecategory;
		$qry1="SELECT * from accounts WHERE customername = '". $getpatameter['corporate_code']."'";
		$result1 = mysqli_fetch_array(mysqli_query($CFG, $qry1));
		if(empty($result1)){
			$errres->errorcode = "1001";
			$errres->message = "Invalid User name";
		} else {
			$this->customername = $result1['customername'];
			$this->customerpassword = $result1['password'];
			$this->uniqueid = $result1['uniqueid'];
			$this->mapheader();
		}
		if(!$this->customername || !$this->customerpassword){
			$errres->errorcode = "1001";
			$errres->status = "error";
			$errres->requestTime=date("d m Y h:i:s A");
			$errres->message = "Invalid User name";
		}
		if(!empty($errres->errorcode)){
			echo json_encode($errres);
			die;
		}
		// print_r($packagedata);
		// die;
		$params = new stdClass();
		$params->event_name= "booking";
		$params->booking_ref_number=$getpatameter['booking_ref_number'];
		$params->traveler_type=$getpatameter['traveler_type'];
		// $params->corporate_code=$getpatameter['corporate_code'];
		$params->service_type=$packagename;
		$params->city_id=$city;
		$params->start_trip_passcode=$getpatameter['start_trip_passcode'];
		$params->end_trip_passcode=$getpatameter['end_trip_passcode'];
		$params->model_id=$VehicleTypeCode;
		$params->trip_type=$getpatameter['trip_type'];
		if(!empty($getpatameter['no_of_days'])){
			$params->no_of_days=$getpatameter['no_of_days'];
		}else{
			$params->no_of_days= "1";
		}

		$params->airport_id=$getpatameter['airport_id'];
		$params->pickup_datetime=$getpatameter['pickup_datetime'];
		$params->pickup_area=$getpatameter['pickup_area'];
		$params->pickup_address=$getpatameter['pickup_address'];
		$params->pickup_area_latitude=$getpatameter['pickup_area_latitude'];
		$params->pickup_area_longitude=$getpatameter['pickup_area_longitude'];
		$params->drop_area=$getpatameter['drop_area'];
		$params->drop_address=$getpatameter['drop_address'];
		$params->drop_area_latitude=$getpatameter['drop_area_latitude'];
		$params->drop_area_longitude=$getpatameter['drop_area_longitude'];
		$params->traveler_name=$getpatameter['traveler_name'];
		$params->traveler_email_id=$getpatameter['traveler_email_id'];
		$params->traveler_mobile_no=$getpatameter['traveler_mobile_no'];
		$params->dispatch_instruction=$getpatameter['dispatch_instruction'];
		$params->booking_customer_type=$getpatameter['booking_customer_type'];
		$params->category_id=$packagecategory;
		$params->engagement_code="Test";
		// $params->corporate_code="C024726";
		$params->corporate_code="C033831";
		// $selercode='se274';
		$selercode='se1009';
		//json_encode($params);
		// print_r($params);

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://cabmanapi.orixindia.com:52816/myf/seller/'.$selercode.'/booking/create',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>json_encode($params),
		  CURLOPT_HTTPHEADER => array(
		    'Content-Type: application/json',
		    'Authorization: Basic ZGV2LVRlc3QxOmRoZWVyYWpAMTIzNA=='
		  ),
		));

		$response = curl_exec($curl);
		curl_close($curl);
		$responsedata = json_decode($response);
		if($responsedata->status) {
				$finalresopnse = [
				   "data" => [
				         "ext_booking_number" => $responsedata->data->ext_booking_number, 
				         "error_msg" => $responsedata->data->error_msg
				      ], 
				   "statuscode" => $responsedata->statuscode, 
				   "requestTime" => $responsedata->requestTime,
				   "status" => $responsedata->status
				]; 
				
				if($responsedata->status=='sucess'){
					$data = new stdClass();
					$data->ext_booking_number = $responsedata->data->ext_booking_number;
					$data->OrderUID = "";
					$data->ClientReferanceNumber = $params->booking_ref_number;
					$createdtime = time();

					/*Original param to b store for pushback api*/
					$original_param = isset($getpatameter) ? json_encode($getpatameter) : null;

					$qry_addlog = "insert into booking_creation(seller_code, ext_booking_number, client_referance_number, orderuid, status, createdtime, customerid, original_param) values('$selercode', '$data->ext_booking_number','$data->ClientReferanceNumber', '$data->OrderUID', '1','$createdtime','$this->customername', '$original_param')";

					mysqli_query($CFG, $qry_addlog);
				} 
			}
		
		$reqdata = json_encode($getpatameter);
		// $reqxmldata = json_encode($this->bodyxml);
		// $resxmldata = json_encode($this->XMLCALL->response);		
		$reqxmldata = json_encode($params);
		$resxmldata = json_encode($finalresopnse);
		$resdata = json_encode($finalresopnse);
		$createdtime = time();
		echo $resdata;
		$qry_addlog = "insert into myfcall(reqdata,reqxmldata,resxmldata,resdata,createdtime) values('$reqdata', '$reqxmldata', '$resxmldata', '$resdata',$createdtime)";
		mysqli_query($CFG, $qry_addlog);
	}
	public function modifybooknig($getpatameter) {
		global $CFG;
		$this->erroravailable($getpatameter, "booking");
		$errres = new stdClass();
		$errres->errorcode = "";
		$errres->status = "error";
		$errres->requestTime=date("d m Y h:i:s A");
		$errres->message = "";
		$city = "";
		$qry="SELECT * from city_info WHERE city_id = ". $getpatameter['city_id'];
		$result = mysqli_fetch_array(mysqli_query($CFG, $qry));
		if(empty($result)){
			$errres->errorcode = "1001";
			$errres->message = "Invalid city id";

		} else {
			if(!empty($result['city_synonyms'])){
				$city=$result['city_synonyms'];
			} else {
				$errres->errorcode = "1001";
				$errres->message = "city id not mapped";
			}
		}
		$VehicleTypeCode = "";
		$qry_category="SELECT * FROM `vehiclemap` WHERE myfcategoryid = ".$getpatameter['category_id']." order  by myfvehicleid desc";
		$result_category = mysqli_fetch_array(mysqli_query($CFG, $qry_category));
		if(empty($result_category)){
			$errres->errorcode = "1001";
			$errres->message = "Invalid Category";
		} else {
			if($getpatameter['model_id'] != 0){
				$qry_model="SELECT * FROM `vehiclemap` WHERE myfcategoryid = ".$getpatameter['category_id']." and myfvehicleid = ". $getpatameter['model_id'];
				$result_model = mysqli_fetch_array(mysqli_query($CFG, $qry_model));
				if(!empty($result_model)){
					$VehicleTypeCode = $result_model['vehiclecode'];
				} else {
					$errres->errorcode = "1001";
					$errres->message = "Invalid Model";
				}
			} else {
				$VehicleTypeCode = $result_category['vehiclecode'];
			}
		}
		$packagedata = $this->getpackage($getpatameter);
		if($packagedata->errorcode){
			$errres->errorcode = $packagedata->errorcode;
			$errres->message = $packagedata->message;
		}
		$packagename = $packagedata->packagename;
		$packagecategory = $packagedata->packagecategory;

		$qry1="SELECT * from accounts WHERE customername = '". $getpatameter['corporate_code']."'";
		$result1 = mysqli_fetch_array(mysqli_query($CFG, $qry1));

		if(empty($result1)){
			$errres->errorcode = "1001";
			$errres->message = "Invalid User name";
		} else {
			$this->customername = $result1['customername'];
			$this->customerpassword = $result1['password'];
			$this->uniqueid = $result1['uniqueid'];
			$this->mapheader();
		}
		$params = new stdClass();
		$params->ClientReferanceNumber=$getpatameter['booking_ref_number'];
		$qry2="SELECT * from booking_creation WHERE client_referance_number = '". $params->ClientReferanceNumber."'";
		$result2 = mysqli_fetch_array(mysqli_query($CFG, $qry2));

		if(empty($result2)){
			$errres->errorcode = "1001";
			$errres->message = "Invalid Reference Number";
		} else {
			$params->ClientReferanceNumber = $result2['ext_booking_number'];
		}
		if(!$this->customername || !$this->customerpassword){
			$errres->errorcode = "1001";
			$errres->status = "error";
			$errres->requestTime=date("d m Y h:i:s A");
			$errres->message = "Invalid User name";
		}
		if(!empty($errres->errorcode)){
			echo json_encode($errres);
			die;
		}

		$params->event_name= "booking";
		$params->booking_ref_number= $getpatameter['booking_ref_number'];
		$params->traveler_type= $getpatameter['traveler_type'];
		$params->corporate_code= $getpatameter['corporate_code'];
		$params->service_type= $packagename;
		$params->city_id= $city;
		$params->start_trip_passcode= $getpatameter['start_trip_passcode'];
		$params->end_trip_passcode= $getpatameter['end_trip_passcode'];
		$params->model_id= $VehicleTypeCode;
		$params->trip_type= $getpatameter['trip_type'];
		$params->no_of_days= $getpatameter['no_of_days'];
		$params->airport_id= $getpatameter['airport_id'];
		$params->pickup_datetime= $getpatameter['pickup_datetime'];
		$params->pickup_area= $getpatameter['pickup_area'];
		$params->pickup_address= $getpatameter['pickup_address'];
		$params->pickup_area_latitude= $getpatameter['pickup_area_latitude'];
		$params->pickup_area_longitude= $getpatameter['pickup_area_longitude'];
		$params->drop_area= $getpatameter['drop_area'];
		$params->drop_address= $getpatameter['drop_address'];
		$params->drop_area_latitude= $getpatameter['drop_area_latitude'];
		$params->drop_area_longitude= $getpatameter['drop_area_longitude'];
		$params->traveler_name= $getpatameter['traveler_name'];
		$params->traveler_email_id= $getpatameter['traveler_email_id'];
		$params->traveler_mobile_no= $getpatameter['traveler_mobile_no'];
		$params->dispatch_instruction= $getpatameter['dispatch_instruction'];
		$params->booking_customer_type= $getpatameter['booking_customer_type'];
		$params->category_id=$packagecategory;
		$params->corporate_code="C033831";
		$selercode='se1009';
		// print_r($params);
		// die;

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://cabmanapi.orixindia.com:52816/myf/seller/'.$selercode.'/booking/modify',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>json_encode($params),
		  CURLOPT_HTTPHEADER => array(
		    'Content-Type: application/json',
		    'Authorization: Basic ZGV2LVRlc3QxOmRoZWVyYWpAMTIzNA=='
		  ),
		));

		$response = curl_exec($curl);
		curl_close($curl);
		$responsedata = json_decode($response);
		//print_r($response);

		if($responsedata->status) {
			$finalresopnse = [
			   "data" => [
			         "ext_booking_number" => $responsedata->data->ext_booking_number, 
			         "error_msg" => $responsedata->data->error_msg
			      ], 
			   "statuscode" => $responsedata->statuscode, 
			   "requestTime" => $responsedata->requestTime, 
			   "status" => $responsedata->status 
			]; 
			
			if($responsedata->status=='sucess'){
				$data = new stdClass();
				$data->ext_booking_number = $responsedata->data->ext_booking_number;
				$data->OrderUID = "";
				$data->ClientReferanceNumber = $params->booking_ref_number;
				$createdtime = time();

				/*Original param to b store for pushback api*/
				$original_param = isset($getpatameter) ? json_encode($getpatameter) : null;

				$qry_addlog = "insert into booking_creation(seller_code, ext_booking_number, orderuid, client_referance_number, status, createdtime, customerid, original_param) values('$selercode', '$data->ext_booking_number', '$data->OrderUID', '$data->ClientReferanceNumber','1','$createdtime','$this->customername', '$original_param')";
				mysqli_query($CFG, $qry_addlog);
			} 
		}

		$reqdata = json_encode($getpatameter);
		// $reqxmldata = json_encode($this->bodyxml);
		// $resxmldata = json_encode($this->XMLCALL->response);		
		$reqxmldata = json_encode($params);
		$resxmldata = json_encode($finalresopnse);
		$resdata = json_encode($finalresopnse);
		$createdtime = time();
		echo $resdata;
		$qry_addlog = "insert into myfcall(reqdata,reqxmldata,resxmldata,resdata,createdtime) values('$reqdata', '$reqxmldata', '$resxmldata', '$resdata',$createdtime)";
		mysqli_query($CFG, $qry_addlog);
	}
	function cancelbooknig($getpatameter){
		global $CFG;
		$this->erroravailable($getpatameter, "cancelbooknig");
		$errres = new stdClass();
		$errres->errorcode = "";
		$errres->status = "error";
		$errres->requestTime=date("d m Y h:i:s A");
		$errres->message = "";
		$qry="SELECT * from booking_creation WHERE client_referance_number = '". $getpatameter['booking_ref_number']."'";
		$result = mysqli_fetch_array(mysqli_query($CFG, $qry));
		if(!empty($result)){
			$qry1="SELECT * from accounts WHERE customername = '". $result['customerid'] ."'";
			$result1 = mysqli_fetch_array(mysqli_query($CFG, $qry1));
			if(empty($result1)){
				$errres->errorcode = "1001";
				$errres->message = "Invalid User name";
			} else {
				$this->customername = $result1['customername'];
				$this->customerpassword = $result1['password'];
				$this->uniqueid = $result1['uniqueid'];
				$this->mapheader();
			}
		} else {
			$errres->errorcode = "1001";
			$errres->message = "Invalid booking number";
		}
		if(!empty($errres->errorcode)){
			echo json_encode($errres);
			die;
		}
		
		$params = new stdClass();
		$params->event_name= "cancel";
		$params->booking_ref_number=$getpatameter['booking_ref_number'];
		// $params->ext_booking_number="DR/DEL/24-25/26000238";
		if(!empty($getpatameter['cancellation_fee'])){
			$params->cancellation_fee=$getpatameter['cancellation_fee'];
		}else{
			$params->cancellation_fee= "0";
		}
		$selercode='se1009';


		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://cabmanapi.orixindia.com:52816/myf/seller/'.$selercode.'/booking/cancel',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>json_encode($params),
		  CURLOPT_HTTPHEADER => array(
		    'Content-Type: application/json',
		    'Authorization: Basic ZGV2LVRlc3QxOmRoZWVyYWpAMTIzNA=='
		  ),
		));

		$response = curl_exec($curl);
		curl_close($curl);
		$responsedata = json_decode($response);
		// print_r($responsedata);
		// die;
		if($responsedata->status=="sucess"){
			$cancelfinalresopnse = [
			   "data" => [
			         "ext_booking_number" => $responsedata->data->ext_booking_number, 
			         "error_msg" => $responsedata->data->error_msg
			      ], 
			   "statuscode" => $responsedata->statuscode, 
			   "status" => $responsedata->status,
			   "requestTime" => $responsedata->requestTime
			]; 

		}else{
			$cancelfinalresopnse = [
			   "statuscode" => $responsedata->statuscode,
			   "data" => [
			         "Reason" => $responsedata->data->Reason, 
			         "error_msg" => $responsedata->data->error_msg
			      ], 
			   "requestTime" => $responsedata->requestTime, 
			   "status" => $responsedata->status
			];

		}
		$reqdata = json_encode($getpatameter);
		// $reqxmldata = json_encode($this->bodyxml);
		// $resxmldata = json_encode($this->XMLCALL->response);
		$reqxmldata = json_encode($params);
		$resxmldata = json_encode($cancelfinalresopnse);
		$resdata = json_encode($cancelfinalresopnse);
		$createdtime = time();
		$qry_addlog = "insert into myfcall(reqdata,reqxmldata,resxmldata,resdata,createdtime) values('$reqdata', '$reqxmldata', '$resxmldata', '$resdata',$createdtime)";
		mysqli_query($CFG, $qry_addlog);
		echo json_encode($cancelfinalresopnse);

	}
	function misbookingdetails($getpatameter){
		global $CFG;
		$this->erroravailable($getpatameter, "cancelbooknig");
		$errres = new stdClass();
		$errres->errorcode = "";
		$errres->status = "error";
		$errres->requestTime=date("d m Y h:i:s A");
		$errres->message = "";
		$qry="SELECT * from booking_creation WHERE client_referance_number = '". $getpatameter['booking_ref_number']."'";
		$result = mysqli_fetch_array(mysqli_query($CFG, $qry));
		if(!empty($result)){
			$qry1="SELECT * from accounts WHERE customername = '". $result['customerid'] ."'";
			$result1 = mysqli_fetch_array(mysqli_query($CFG, $qry1));
			if(empty($result1)){
				$errres->errorcode = "1001";
				$errres->message = "Invalid User name";
			} else {
				$this->customername = $result1['customername'];
				$this->customerpassword = $result1['password'];
				$this->uniqueid = $result1['uniqueid'];
				//$this->mapheader();
			}
		} else {
			$errres->errorcode = "1001";
			$errres->message = "Invalid booking number";
		}
		if(!empty($errres->errorcode)){
			echo json_encode($errres);
			die;
		}

		
		$params = new stdClass();
		$params->event_name= "mis";
		$params->booking_ref_number=$getpatameter['booking_ref_number'];
		// $params->ext_booking_number="DR/DEL/24-25/26000238";
		if(!empty($getpatameter['external_booking_id'])){
			$params->external_booking_id=$getpatameter['external_booking_id'];
		}else{
			$params->external_booking_id="";
		}
		$params->event_time=$getpatameter['event_time'];
		$selercode='se1009';


		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://cabmanapi.orixindia.com:52816/myf/seller/'.$selercode.'/booking/booking/mis',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>json_encode($params),
		  CURLOPT_HTTPHEADER => array(
		    'Content-Type: application/json',
		    'Authorization: Basic ZGV2LVRlc3QxOmRoZWVyYWpAMTIzNA=='
		  ),
		));

		$response = curl_exec($curl);
		curl_close($curl);
		// echo $responsedata = json_decode($response);
		print_r($response);
		die;

		// if($responsedata->status=="sucess"){
		// 	$cancelfinalresopnse = [
		// 	   "data" => [
		// 	         "ext_booking_number" => $responsedata->data->ext_booking_number, 
		// 	         "error_msg" => $responsedata->data->error_msg
		// 	      ], 
		// 	   "statuscode" => $responsedata->statuscode, 
		// 	   "status" => $responsedata->status,
		// 	   "requestTime" => $responsedata->requestTime
		// 	]; 

		// }else{
		// 	$cancelfinalresopnse = [
		// 	   "statuscode" => $responsedata->statuscode,
		// 	   "data" => [
		// 	         "Reason" => $responsedata->data->Reason, 
		// 	         "error_msg" => $responsedata->data->error_msg
		// 	      ], 
		// 	   "requestTime" => $responsedata->requestTime, 
		// 	   "status" => $responsedata->status
		// 	];

		// }
		$reqdata = json_encode($getpatameter);
		// $reqxmldata = json_encode($this->bodyxml);
		// $resxmldata = json_encode($this->XMLCALL->response);
		$reqxmldata = json_encode($params);
		$resxmldata = json_encode($cancelfinalresopnse);
		$resdata = json_encode($cancelfinalresopnse);
		$createdtime = time();
		$qry_addlog = "insert into myfcall(reqdata,reqxmldata,resxmldata,resdata,createdtime) values('$reqdata', '$reqxmldata', '$resxmldata', '$resdata',$createdtime)";
		mysqli_query($CFG, $qry_addlog);
		echo json_encode($cancelfinalresopnse);

	}
	function billApproval($getpatameter){
		global $CFG;
		//var_dump($getpatameter);exit;
		$this->erroravailable($getpatameter, "cancelbooknig");
		// print_r($getpatameter);
		// echo "	";
		if(empty($getpatameter['reason'])){
			$getpatameter['reason'] = "no reason";
		}
		if(empty($getpatameter['bill_approval_remarks'])){
			$getpatameter['bill_approval_remarks'] = "ok";
		}


		$qry="SELECT * from booking_creation WHERE ext_booking_number = '". $getpatameter['ext_booking_number']."'";
		$result = mysqli_fetch_array(mysqli_query($CFG, $qry));
		if(!empty($result)){
			$qry1="SELECT * from accounts WHERE customername = '". $result['customerid'] ."'";
			$result1 = mysqli_fetch_array(mysqli_query($CFG, $qry1));
			if(empty($result1)){
				$errres->errorcode = "1001";
				$errres->message = "Invalid User name";
			} else {
				$this->customername = $result1['customername'];
				$this->customerpassword = $result1['password'];
				$this->uniqueid = $result1['uniqueid'];
				$this->mapheader();
			}
		} else {
			$errres->errorcode = "1001";
			$errres->message = "Invalid booking number";
		}
		if(!empty($errres->errorcode)){
			echo json_encode($errres);
			die;
		}
		// print_r($getpatameter);
		// echo "	";
		if(empty($getpatameter['reason'])){
			$getpatameter['reason'] = "no reason";
		}

		$requestbody = array(
			"tem:UpdateBillingApprovalStatus"=>array(
				"tem:listBookingData"=>array(
					"tem:UpdateBillingApprovalStatusData"=> array(
						"tem:OrderNumber"=>stripslashes($getpatameter['ext_booking_number']),
						"tem:Status"=>$getpatameter['bill_approval_status'],
						"tem:Reason"=>$getpatameter['bill_approval_remarks'],
					)
				)
			)
		);
		  $this->bodyxml = $this->XMLCALL->listRolesRecursive($requestbody);
		// print_r($this->bodyxml);
		$this->XMLCALL->callAPI("",$this->headerxml, $this->bodyxml);
		// print_r($this->XMLCALL->response);
		// die;
		@$xml=simplexml_load_string($this->XMLCALL->response);
		// print_r($xml);
		$xml = json_encode($xml);
		$xml = json_decode($xml, true);
		// print_r($xml);


		$cancelfinalresopnse = new StdClass();
		$cancelfinalresopnse->status="";
		$cancelfinalresopnse->data="";
		$cancelfinalresopnse->requestTime="";
		$cancelfinalresopnse->message="";

		$cdata = new stdClass();
		$cdata->booking_ref_number = "";
		// $cdata->OrderNumber = "";	
		$cdata->requestTime = date('Y-m-d H:i:s');
		//var_dump($xml);exit;
		if(isset($xml['UpdateBillingApprovalStatusResponse'])){
			if(isset($xml['UpdateBillingApprovalStatusResponse']['UpdateBillingApprovalStatusResult']['UpdateBillingApprovalStatusDataResult']))
			{
				$cancelres = $xml['UpdateBillingApprovalStatusResponse']['UpdateBillingApprovalStatusResult']['UpdateBillingApprovalStatusDataResult'];
				if(isset($cancelres['ErrorMessage']) && empty($cancelres['ErrorMessage'])){

					$cdata->booking_ref_number = stripslashes(str_replace("//","",$cancelres['OrderNumber']));
					// $cdata->OrderNumber = $cancelres['OrderNumber'];

					$cancelfinalresopnse->status="success";
					$cancelfinalresopnse->requestTime=$cdata->requestTime;
					$cancelfinalresopnse->data=$cdata;
					$cancelfinalresopnse->message="";
				} else {
					$cancelfinalresopnse->status="error";
					$cancelfinalresopnse->data=$cdata;
					$cancelfinalresopnse->message=$cancelres['ErrorMessage'];
				}
			}else{
				$cancelfinalresopnse->status="error";
				$cancelfinalresopnse->data=$cdata;
				$cancelfinalresopnse->message="Invalid response from server";
			}
		} else {
			$cancelfinalresopnse->status="error";
			$cancelfinalresopnse->data=$data;
			$cancelfinalresopnse->message="Failed to connect to server";

		}

		$reqdata = json_encode($getpatameter);
		$reqxmldata = json_encode($this->bodyxml);
		$resxmldata = json_encode($this->XMLCALL->response);
		$resdata = json_encode($cancelfinalresopnse);
		$createdtime = time();
		$qry_addlog = "insert into myfcall(reqdata,reqxmldata,resxmldata,resdata,createdtime) values('$reqdata', '$reqxmldata', '$resxmldata', '$resdata',$createdtime)";
		mysqli_query($CFG, $qry_addlog);

		echo json_encode($cancelfinalresopnse);

	}
	function erroravailable($getpatameter, $functionname){
		global $CFG;
		$errres = new stdClass();
		if($functionname == "booking"){
			// if(empty($getpatameter['seller_code'])){
			// 	$errres->errorcode = "1208";
			// 	$errres->status = "error";
			// 	$errres->requestTime=date("d m Y h:i:s A");
			// 	$errres->message = "Please provide seller code";
			// } else if($getpatameter['seller_code'] != "se1009"){
			// 	$errres->errorcode = "1208";
			// 	$errres->status = "error";
			// 	$errres->requestTime=date("d m Y h:i:s A");
			// 	$errres->message = "Invalid  seller code";
			// } else 
			if(empty($getpatameter['pickup_area_latitude'])){
				$errres->errorcode = "1217";
				$errres->status = "error";
				$errres->requestTime=date("d m Y h:i:s A");
				$errres->message = "Please pass the latitude";
			} else if(!$this->validateLatitude($getpatameter['pickup_area_latitude'])){
				$errres->errorcode = "1218";
				$errres->status = "error";
				$errres->requestTime=date("d m Y h:i:s A");
				$errres->message = "Invalid latitude";
			} else if(empty($getpatameter['pickup_area_longitude'])){
				$errres->errorcode = "1215";
				$errres->status = "error";
				$errres->requestTime=date("d m Y h:i:s A");
				$errres->message = "Please pass the longitude";
			} else if(!$this->validateLongitude($getpatameter['pickup_area_longitude'])){
				$errres->errorcode = "1216";
				$errres->status = "error";
				$errres->requestTime=date("d m Y h:i:s A");
				$errres->message = "Invalid longitude";
			} 
			// else if($this->validateLongitude($getpatameter['drop_area_longitude'])){
			// 	$errres->errorcode = "1215";
			// 	$errres->status = "error";
			// 	$errres->requestTime=date("d m Y h:i:s A");
			// 	$errres->message = "Please pass drop area the longitude";
			// } 
			else {

			}
		} else {

		}	



		if($functionname == "cancelbooknig"){
			if(empty($getpatameter['booking_ref_number'])){
				$errres->errorcode = "1113";
				$errres->status = "error";
				$errres->requestTime=date("d m Y h:i:s A");
				$errres->message = "Invalid Booking Number";
			}else {

			}
		} else {

		}



		if(isset($errres->errorcode)){
			echo json_encode($errres);
			die;
		}

		return $getpatameter;
 	}
	function validateLatitude($val) {
		// return true;
	    $lat = intval($lat);
	  return preg_match('/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/', $lat);
	     // return preg_match( "#^([\+\-]|)([0-9]*)(\.([0-9]*?)|)(0*)$#", trim($val));
    // return $o[1].sprintf('%d',$o[2]).($o[3]!='.'?$o[3]:'');
	}
	function validateLongitude($long) {
	    $long = intval($long);
		// return true;
	    // $long = intval($long);
	  return preg_match('/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/', $long);
	}
	function getpackage($getpatameter){
		global $CFG;
		$errres = new stdClass();
		$errres->errorcode = "";
		$errres->message = "";
		$errres->packagename = "";
		$packagename = "4/40";
		$packagecategory = "G/G";
		$extracondition = "";
		if($getpatameter['service_type'] == 'local-package' || $getpatameter['service_type'] == 'on-demand'){
			if(empty($getpatameter['included_distance']) || empty($getpatameter['included_time']))
			{
				$getpatameter['included_time'] = 14400;
				$getpatameter['included_distance'] = 40000;
				// $errres->errorcode = "1001";
				// $errres->message = "Missing included_distance or included_time";
			}
			// print_r($getpatameter);
			// die;
			$packagename = ($getpatameter['included_time']/3600)."/".($getpatameter['included_distance']/1000);

		} else if($getpatameter['service_type'] == 'outstation'){
			$typpecategory  = ($getpatameter['service_type'] == 'outstation')?"ostn":"APT";

			if($getpatameter['included_distance']){
				$extracondition = " and default_kilometer = ".($getpatameter['included_distance']/1000)." ";
			}
			$qry_addlog = "select * from ordertypelist where order_type_category_name like '$typpecategory% P/P' and order_type_name not like'%Guest%' $extracondition order by default_kilometer asc";
			$qry_res = mysqli_query($CFG, $qry_addlog);
			if($qry_res->num_rows >0 ){
				$qry_row = mysqli_fetch_assoc($qry_res);
				$packagename = "1 Day/250 Kms";
				// $packagename = $qry_row['order_type_name'];
			} else {
				$packagename = "1 Day/250 Kms";
			}
			// $packagename = "1 Day/250 Kms";
			// $packagename = "AIRPORT TRANSFER";
			$packagecategory = "OSTN G/G";
		} else if($getpatameter['service_type'] == 'transfer'){
			$typpecategory  = "APT";
			if($getpatameter['included_distance']){
				$extracondition = " and default_kilometer = ".($getpatameter['included_distance']/1000)." ";
			}
			$qry_addlog = "select * from ordertypelist where order_type_category_name like '$typpecategory% P/P' and order_type_name not like'%Guest%' $extracondition order by default_kilometer asc";
			$qry_res = mysqli_query($CFG, $qry_addlog);
			if($qry_res->num_rows >0 ){
				$qry_row = mysqli_fetch_assoc($qry_res);
				// $packagename = "A/T/3/40";
				$packagename = "1 Day/300 Kms";
				// $packagename = $qry_row['order_type_name'];
			} else {
				// $packagename = "A/T/3/40";
				$packagename = "1 Day/300 Kms";
			}
			$packagecategory = "OSTN G/G";
			// $packagename = "1 Day/250 Kms";
			// $packagename = "AIRPORT TRANSFER";
		} else if($getpatameter['service_type'] == 'outstation-package'){
			$typpecategory  = "ostn";
			if($getpatameter['included_distance']){
				$extracondition = " and default_kilometer = ".($getpatameter['included_distance']/1000)." ";
			}
			$qry_addlog = "select * from ordertypelist where order_type_category_name like '$typpecategory% G/G' and order_type_name not like'%Guest%' $extracondition order by default_kilometer asc";
			$qry_res = mysqli_query($CFG, $qry_addlog);
			if($qry_res->num_rows >0 ){
				$qry_row = mysqli_fetch_assoc($qry_res);
				$packagename = "1 Day/250 Kms";
				// $packagename = $qry_row['order_type_name'];
			} else {
				$errres->errorcode = "1001";
				$errres->message = "Order type not available";
			}
			// $packagename = "1 Day/250 Kms";
			// $packagename = "AIRPORT TRANSFER";
			$packagecategory = "OSTN G/G";
		} else {
			$errres->errorcode = "1001";
			$errres->message = "Invalid Order type";
		}
		$errres->packagename = $packagename;
		$errres->packagecategory = $packagecategory;
		return $errres;
	}

	
}
$orixapi = new orixapi();




// print_r($myobj);	
?>
