<?php

/**
 * orix pushback api
 */

include_once 'index.php';

class orixPushback {
	protected $token = null;
	protected $expire_at = null;
	protected $functionCalled = null;
	protected $data = [];
	public $CFG;
	public function __construct() {
		return 'Syncing with orixPushback api to myf reciever api';
	}
	public static function Rqid($payloaddata) {
		$payload = array();
		if(is_array($payloaddata) || is_object($payloaddata)){
			foreach ($payloaddata as $key => $value) {
				array_push($payload, $key."=".$value);
			}
		}
		// print_r($payload);
		$finalpayload = implode("&", $payload);
		$combined_string = $finalpayload . '||' . SECURITY_SALT;
		// echo "\nFinalstrnig: {$combined_string}\n";
		$hash_value = hash('sha256', $combined_string);
		$rqid = strtolower($hash_value);
		// echo "\nFinalstrnig: {$rqid}\n";
		return $rqid;
	}
	public static function Sign($payload, $key, $expire = null) {
        // Header
        $headers = ['algo'=>'HS256', 'type'=>'JWT', 'expire' => time()+$expire];
        if($expire){
            $headers['expire'] = time()+$expire;
        }
        $headers_encoded = base64_encode(json_encode($headers));

        // Payload
        $payload['time'] = time();
        $payload_encoded = base64_encode(json_encode($payload));

        // Signature
        $signature = hash_hmac('SHA256',$headers_encoded.$payload_encoded,$key);
        $signature_encoded = base64_encode($signature);

        // Token
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
	        if($_SERVER['PHP_AUTH_USER'] == USER) {
	        	if($_SERVER['PHP_AUTH_PW'] == SECRET) {
	        		$token = $headers_encoded . '.' . $payload_encoded .'.'. $signature_encoded;
			        $expire_at = date("Y-m-d H:i:s",$headers['expire']);
			        if($token) {
			        	$status = 1;
			        	$msg = 'Token generated';
			        	$data = array('token'=>$token,'expire_at'=>$expire_at);
			        }
	        	} else {
	        		$status = 0;
		        	$msg = 'Password is wrong';
		        	$data = array('token'=>null,'expire_at'=>null);
	        	}
	        } else {
	        	$status = 0;
	        	$msg = 'Username is wrong';
	        	$data = array('token'=>null,'expire_at'=>null);
	        }
        } else {
        	$status = 0;
        	$msg = 'Method is not allowed';
        	$data = array('token'=>null,'expire_at'=>null);
        }
        return self::handleReturn($data, $status, $msg);
    }
    public static function Verify($token, $key) {

        // Break token parts
        $token_parts = explode('.', $token);

        // Verigy Signature
        $signature = base64_encode(hash_hmac('SHA256',$token_parts[0].$token_parts[1],$key));
        if($signature != $token_parts[2]){
            return false;
        }

        // Decode headers & payload
        $headers = json_decode(base64_decode($token_parts[0]), true);
        $payload = json_decode(base64_decode($token_parts[1]), true);

        // Verify validity
        if(isset($headers['expire']) && $headers['expire'] < time()){
            return false;
        }

        // If token successfully verified
        return $payload;
    }
    public static function InsertBookingStatus($requestdata) {
		global $CFG;
		$ext_booking_number = $requestdata->ext_booking_number;
    	$trn = $requestdata->trn; 
    	$status = $requestdata->status;
		$time = time();
		$query = "SELECT `client_referance_number` FROM booking_creation WHERE ext_booking_number = '$ext_booking_number'";
		if($queryData = mysqli_fetch_assoc(mysqli_query($CFG, $query))) {
			$client_referance_number = $queryData['client_referance_number'];
		}
		$sqlIns = "INSERT INTO booking_status (ext_booking_number,client_referance_number,trn,status,created_at,modified_at) VALUES ('$ext_booking_number','$client_referance_number','$trn','$status','$time','$time')";
		if($mysqli_query = mysqli_query($CFG, $sqlIns)) {
			return 1;
		} else {
			return 0;
		}
    }    
    public static function InsertPushbackLog($ext_booking_number, $requestdata, $responsedata, $api_type, $orixpayload) {
		global $CFG;
		$modified_date = time();
		$sqlIns = "INSERT INTO pushback_log (ext_booking_number,orixpayload,requestdata,responsedata,api_type,modified_date) VALUES ('$ext_booking_number','$orixpayload','$requestdata','$responsedata','$api_type','$modified_date')";
		if($mysqli_query = mysqli_query($CFG, $sqlIns)) {
			return 1;
		} else {
			return 0;
		}
    }
    public static function AcceptanceStatus($requestdata) {
		global $CFG;
		$requestDataNew = new stdClass();
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$bookingId = $requestdata->bookingId;
			$query = "SELECT `original_param`, `ext_booking_number`, `client_referance_number` FROM booking_creation WHERE ext_booking_number = '$bookingId'";
			if($queryData = mysqli_fetch_assoc(mysqli_query($CFG, $query))) {
				
				if($requestdata->serviceProviderResponse == 'ACCEPT') {
					$requestDataNew->event_name = "booking_confirmation";
					$requestDataNew->event_datetime = date("Y-m-d H:i:s",time());
					$requestDataNew->seller_code = SELLER_CODE;
					$requestDataNew->booking_id = $queryData['client_referance_number'];
					$requestDataNew->ext_booking_number = $bookingId;
					$requestDataNew->accept = "yes";
				}elseif($requestdata->serviceProviderResponse == 'DENY') {
					$requestDataNew->event_name = "booking_confirmation";
					$requestDataNew->event_datetime = date("Y-m-d H:i:s",time());
					$requestDataNew->seller_code = SELLER_CODE;
					$requestDataNew->booking_id = $queryData['client_referance_number'];
					$requestDataNew->ext_booking_number = $bookingId;
					$requestDataNew->accept = "no";
				}else{
					$status = 1;
					$msg = "Invalid request";
				}
				if($getBearerToken = self::getBearerToken()) {
					if($payload = self::Verify($getBearerToken, KEY)) {
						if($payload['id'] == "]OwHd&I;@*fwkc/") {
							$status = 1;
							$msg = "Token validated";
						} else {
							$status = 0;
							$msg = "Token validatation failed";
						}
					}else{
						$status = 0;
						$msg = "Missing payload";
					}
				}else{
					$status = 0;
					$msg = "Missing bearer token";
				}
			} else {
				$status = 0;
				$msg = "Invalid booking details";
			}
		} else {
        	$status = 0;
        	$msg = 'Method is not allowed';
        }
		return self::handleReturn($requestDataNew, $status, $msg);
	}
	public static function DriverAndCabDetails($requestdata) {
		global $CFG;
		$requestDataNew = new stdClass();
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$bookingId = $requestdata->data->bookingId;
			$driverName = $requestdata->data->driverName;
			$driverMobile = $requestdata->data->driverMobile;
			$plateNo = $requestdata->data->plateNo;
			$query = "SELECT `original_param`, `ext_booking_number`, `client_referance_number` FROM booking_creation WHERE ext_booking_number ='$bookingId'";
			if($queryData = mysqli_fetch_assoc(mysqli_query($CFG, $query))) {
				$original_param = json_decode($queryData['original_param']);
				if($bookingId) {
					$requestDataNew->event_name = "assigned";
					$requestDataNew->event_datetime = date("Y-m-d H:i:s",time());
					$requestDataNew->seller_code = SELLER_CODE;
					$requestDataNew->booking_id = $queryData['client_referance_number'];
					$requestDataNew->supplier_id = SUPPLIER_ID;
					$requestDataNew->driver_type = "oncall";
					$requestDataNew->driver_name = $driverName;
					$requestDataNew->driver_phone = $driverMobile;
					$requestDataNew->driving_license = "N/A";
					$requestDataNew->car_number = $plateNo;
					$requestDataNew->model_id = $original_param->model_id;
					$requestDataNew->car_model = "N/A";
					$requestDataNew->car_fuel_type = "hybrid";
					$requestDataNew->dispatch_datetime = date("Y-m-d H:i:s",time());
					$requestDataNew->car_changed = "no_change";
					$requestDataNew->reassign = "no";
					$requestDataNew->reassign_reason_id = "N/A";
					$requestDataNew->reassign_reason = "N/A";
				}
				if($getBearerToken = self::getBearerToken()) {
					if($payload = self::Verify($getBearerToken, KEY)) {
						if($payload['id'] == "]OwHd&I;@*fwkc/") {
							$status = 1;
							$msg = "Token validated";
						} else {
							$status = 0;
							$msg = "Token validatation failed";
						}
					}else{
						$status = 0;
						$msg = "Missing payload";
					}
				}else{
					$status = 0;
					$msg = "Missing bearer token";
				}
			}else {
				$status = 0;
				$msg = "Invalid booking details";
			}
		} else {
        	$status = 0;
        	$msg = 'Method is not allowed';
        }
		return self::handleReturn($requestDataNew, $status, $msg);
	}
	public static function DriverDispatch($requestdata) {
		global $CFG;
		$requestDataNew = new stdClass();
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$bookingId = $requestdata->data->bookingId;
			$dutyStatus = $requestdata->data->dutyStatus;
			$lat = $requestdata->data->lat;
			$lng = $requestdata->data->lng;

			$gmtDatetime = $requestdata->data->gpsTime;
			$date = new DateTime($gmtDatetime, new DateTimeZone('GMT'));
			$date->setTimezone(new DateTimeZone('Asia/Kolkata'));
			$gpsTime =  $date->format('Y-m-d H:i:s');

			$query = "SELECT `original_param`, `ext_booking_number`, `client_referance_number` FROM booking_creation WHERE ext_booking_number ='$bookingId'";
			if($queryData = mysqli_fetch_assoc(mysqli_query($CFG, $query))) {
				$original_param = json_decode($queryData['original_param']);
				if($bookingId) {
					$requestDataNew->event_name = "dispatch";
					$requestDataNew->event_datetime = date("Y-m-d H:i:s",time());
					$requestDataNew->seller_code = SELLER_CODE;
					$requestDataNew->booking_id = $queryData['client_referance_number'];
					$requestDataNew->auto_driver_confirm = 1;
					$requestDataNew->current_lat = $lat;
					$requestDataNew->current_lng = $lng;
					$requestDataNew->dispatch_center_lat = $lat;
					$requestDataNew->dispatch_center_lng = $lng;
					// $requestDataNew->qc_parameter = array("list"=>array(array("parameter_id"=>1,"parameter_value"=>"no"), array("parameter_id"=>2,"parameter_value"=>"yes")));
				}
				if($getBearerToken = self::getBearerToken()) {
					if($payload = self::Verify($getBearerToken, KEY)) {
						if($payload['id'] == "]OwHd&I;@*fwkc/") {
							$status = 1;
							$msg = "Token validated";
						} else {
							$status = 0;
							$msg = "Token validatation failed";
						}
					}else{
						$status = 0;
						$msg = "Missing payload";
					}
				}else{
					$status = 0;
					$msg = "Missing bearer token";
				}
			}else {
				$status = 0;
				$msg = "Invalid booking details";
			}
		} else {
        	$status = 0;
        	$msg = 'Method is not allowed';
        }
		return self::handleReturn($requestDataNew, $status, $msg);
	}
	public static function DriverArrived($requestdata) {
		global $CFG;
		$requestDataNew = new stdClass();
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$bookingId = $requestdata->data->bookingId;
			$dutyStatus = $requestdata->data->dutyStatus;
			$lat = $requestdata->data->lat;
			$lng = $requestdata->data->lng;

			$gmtDatetime = $requestdata->data->gpsTime;
			$date = new DateTime($gmtDatetime, new DateTimeZone('GMT'));
			$date->setTimezone(new DateTimeZone('Asia/Kolkata'));
			$gpsTime =  $date->format('Y-m-d H:i:s');
			
			$query = "SELECT `original_param`, `ext_booking_number`, `client_referance_number` FROM booking_creation WHERE ext_booking_number ='$bookingId'";
			if($queryData = mysqli_fetch_assoc(mysqli_query($CFG, $query))) {
				$original_param = json_decode($queryData['original_param']);
				if($bookingId) {
					$requestDataNew->event_name = "arrived";
					$requestDataNew->event_datetime = date("Y-m-d H:i:s",time());
					$requestDataNew->seller_code = SELLER_CODE;
					$requestDataNew->booking_id = $queryData['client_referance_number'];
					$requestDataNew->current_address = "N/A";
					$requestDataNew->current_lat = $lat;
					$requestDataNew->current_lng = $lng;
				}
				if($getBearerToken = self::getBearerToken()) {
					if($payload = self::Verify($getBearerToken, KEY)) {
						if($payload['id'] == "]OwHd&I;@*fwkc/") {
							$status = 1;
							$msg = "Token validated";
						} else {
							$status = 0;
							$msg = "Token validatation failed";
						}
					}else{
						$status = 0;
						$msg = "Missing payload";
					}
				}else{
					$status = 0;
					$msg = "Missing bearer token";
				}
			}else {
				$status = 0;
				$msg = "Invalid booking details";
			}
		} else {
        	$status = 0;
        	$msg = 'Method is not allowed';
        }
		return self::handleReturn($requestDataNew, $status, $msg);
	}
	public static function BookingTripStartDetails($requestdata) {
		global $CFG;
		$requestDataNew = new stdClass();
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$bookingId = $requestdata->data->bookingId;
			$currentLat = $requestdata->data->currentLat;
			$currentLng = $requestdata->data->currentLng;
			$eventDatetime = ((int)($requestdata->data->eventDatetime)/1000)+19800;

			$date = new DateTime();
			$date->setTimestamp($eventDatetime);
			$date->setTimezone(new DateTimeZone('Asia/Kolkata'));
			$eventDatetime = $date->format('Y-m-d H:i:s');

			$query = "SELECT `original_param`, `ext_booking_number`, `client_referance_number` FROM booking_creation WHERE ext_booking_number = '$bookingId'";
			if($queryData = mysqli_fetch_assoc(mysqli_query($CFG, $query))) {
				$original_param = json_decode($queryData['original_param']);
				$requestDataNew->event_name = 'start';
				$requestDataNew->event_datetime = $eventDatetime;
				$requestDataNew->seller_code = SELLER_CODE;
				$requestDataNew->booking_id = $queryData['client_referance_number'];
				$requestDataNew->garage_pickup_distance = 0;
				$requestDataNew->garage_pickup_time = 0;
				$requestDataNew->current_address = "N/A";
				$requestDataNew->current_lat = $currentLat;
				$requestDataNew->current_lng = $currentLng;
				$requestDataNew->meter_reading = 100;
				$requestDataNew->passcode = $original_param->start_trip_passcode;
				if($getBearerToken = self::getBearerToken()) {
					if($payload = self::Verify($getBearerToken, KEY)) {
						if($payload['id'] == "]OwHd&I;@*fwkc/") {
							$status = 1;
							$msg = "Token validated";
						} else {
							$status = 0;
							$msg = "Token validatation failed";
						}
					} else {
						$status = 0;
						$msg = "Missing payload";
					}
				} else {
					$status = 0;
					$msg = "Missing bearer token";
				}
			} else {
				$status = 0;
				$msg = "Invalid booking details";
			}
		} else {
        	$status = 0;
        	$msg = 'Method is not allowed';
        }
		return self::handleReturn($requestDataNew, $status, $msg);
	}
	public static function BookingTripEndDetails($requestdata) {
		global $CFG;
		$requestDataNew = new stdClass();
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$bookingId = $requestdata->data->bookingId;
			$currentLat = $requestdata->data->currentLat;
			$currentLng = $requestdata->data->currentLng;
			$eventDatetime = ((int)($requestdata->data->eventDatetime)/1000)+19800;

			$date = new DateTime();
			$date->setTimestamp($eventDatetime);
			$date->setTimezone(new DateTimeZone('Asia/Kolkata'));
			$eventDatetime = $date->format('Y-m-d H:i:s');


			$query = "SELECT `original_param`, `ext_booking_number`, `client_referance_number` FROM booking_creation WHERE ext_booking_number = '$bookingId'";
			if($queryData = mysqli_fetch_assoc(mysqli_query($CFG, $query))) {
				$original_param = json_decode($queryData['original_param']);
				$requestDataNew->event_name = 'end';
				$requestDataNew->event_datetime = $eventDatetime;
				$requestDataNew->seller_code = SELLER_CODE;
				$requestDataNew->booking_id = $queryData['client_referance_number'];
				$requestDataNew->current_address = "N/A";
				$requestDataNew->current_lat = $currentLat;
				$requestDataNew->current_lng = $currentLng;
				$requestDataNew->meter_reading = 200;
				$requestDataNew->drop_garage_distance = 0;
				$requestDataNew->drop_garage_time = 0;
				$requestDataNew->waiting_time = 0;
				$requestDataNew->pickup_drop_distance = 0;
				$requestDataNew->passcode = $original_param->end_trip_passcode;
				if($getBearerToken = self::getBearerToken()) {
					if($payload = self::Verify($getBearerToken, KEY)) {
						if($payload['id'] == "]OwHd&I;@*fwkc/") {
							$status = 1;
							$msg = "Token validated";
						} else {
							$status = 0;
							$msg = "Token validatation failed";
						}
					}else{
						$status = 0;
						$msg = "Missing payload";
					}
				}else{
					$status = 0;
					$msg = "Missing bearer token";
				}
			} else {
				$status = 0;
				$msg = "Invalid booking details";
			}
		} else {
        	$status = 0;
        	$msg = 'Method is not allowed';
        }
		return self::handleReturn($requestDataNew, $status, $msg);
	}
	public static function CloseDuty($requestdata) {
		global $CFG;
		$requestDataNew = new stdClass();
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$bookingId = $requestdata->data->bookingId;
			$currentLat = $requestdata->data->currentLat;
			$currentLng = $requestdata->data->currentLng;
			$eventDatetime = $eventDatetime = ((int)($requestdata->data->eventDatetime)/1000)+19800;

			$date = new DateTime();
			$date->setTimestamp($eventDatetime);
			$date->setTimezone(new DateTimeZone('Asia/Kolkata'));
			$eventDatetime = $date->format('Y-m-d H:i:s');

			$query = "SELECT `original_param`, `ext_booking_number`, `client_referance_number` FROM booking_creation WHERE ext_booking_number = '$bookingId'";
			if($queryData = mysqli_fetch_assoc(mysqli_query($CFG, $query))) {
				$original_param = json_decode($queryData['original_param']);
				$client_referance_number = $queryData['client_referance_number'];
				$requestDataNew->event_name = 'closed';
				$requestDataNew->event_datetime = $eventDatetime;
				$requestDataNew->seller_code = SELLER_CODE;
				$requestDataNew->booking_id = $client_referance_number;
				$queryChk = "SELECT * FROM booking_tracking WHERE ext_booking_number = '$bookingId' AND client_referance_number = '$client_referance_number'";
				$actual_pickup_lat = "28.6795683";
				$actual_pickup_long = "77.1653517";
				$actual_pickup_address = "N/A";
				$pickup_time = date("Y-m-d H:i:s", time());
				$actual_drop_address = "N/A";
				$actual_drop_lat = "28.6795683";
				$actual_drop_long = "77.1653517";
				$drop_time = date("Y-m-d H:i:s", time());
				$dispatch_center_lng = "77.1653517";
				$dispatch_center_lat = "28.6795683";
				$arrived_time = date("Y-m-d H:i:s", time());
				$pickup_drop_distance = "2000";
				$garage_pickup_distance = "4000";
				$drop_garage_distance = "3000";
				$main_fare = "0";
				$tax_amount = "0"; 
				$discount_amount = "0";
				
				$meter_reading = "300";
				$result = mysqli_query($CFG, $queryChk);
				$booking_trackings = [];
				while ($row = mysqli_fetch_assoc($result)) {
				    $booking_trackings[] = $row;
				}
				foreach ($booking_trackings as $booking_tracking) {
					$dutyStatus = $booking_tracking['dutyStatus'];
					$data = json_decode($booking_tracking['requestdata']);
					if($dutyStatus == 'pickup') {
						$actual_pickup_address = "N/A";
						$actual_pickup_lat = $data->data->lat;
						$actual_pickup_long = $data->data->lng;
						$pickup_time = $data->data->gpsTime;
					} 
					if($dutyStatus == 'drop') {
						$actual_drop_address = "N/A";
						$actual_drop_lat = $data->data->lat;
						$actual_drop_long = $data->data->lng;
						$drop_time = $data->data->gpsTime;
						$current = time() + 3600; 
						$garage_in_time = date("Y-m-d H:i:s", $current);

					}
					if($dutyStatus == 'dispatch') {
						$location_out_time = $data->data->gpsTime;
						$dispatch_center_lat = $data->data->lat;
						$dispatch_center_lng = $data->data->lng;
					}
					if($dutyStatus == 'arrived') {
						$arrived_time = $data->data->gpsTime;
					}
				}
				$requestDataNew->garage_pickup_distance = $garage_pickup_distance;
				$requestDataNew->pickup_drop_distance = $pickup_drop_distance;
				$requestDataNew->drop_garage_distance = $drop_garage_distance;
				$requestDataNew->addons = array("id" => "21", "value" => "0");
				$requestDataNew->main_fare = $main_fare;
				$requestDataNew->tax_amount = $tax_amount;
				$requestDataNew->discount_amount = $discount_amount;
				$requestDataNew->dispatch_center_lat = $dispatch_center_lat;
				$requestDataNew->dispatch_center_lng = $dispatch_center_lng;
				$requestDataNew->actual_pickup_address = $actual_pickup_address;
				$requestDataNew->actual_drop_address = $actual_drop_address;
				$requestDataNew->actual_pickup_lat = $actual_pickup_lat;
				$requestDataNew->actual_pickup_long = $actual_pickup_long;
				$requestDataNew->actual_drop_lat = $actual_drop_lat;
				$requestDataNew->actual_drop_long = $actual_drop_long;
				$requestDataNew->location_out_time = $location_out_time;
				$requestDataNew->pickup_time = $pickup_time;
				$requestDataNew->drop_time = $drop_time;
				$requestDataNew->arrived_time = $arrived_time;
				$requestDataNew->meter_reading = $meter_reading;
				$requestDataNew->garage_in_time = $garage_in_time;
				$requestDataNew->dispute = '0';
				$requestDataNew->vendor_remark = 'N/A';
				if($getBearerToken = self::getBearerToken()) {
					if($payload = self::Verify($getBearerToken, KEY)) {
						if($payload['id'] == "]OwHd&I;@*fwkc/") {
							$status = 1;
							$msg = "Token validated";
						} else {
							$status = 0;
							$msg = "Token validatation failed";
						}
					}else{
						$status = 0;
						$msg = "Missing payload";
					}
				}else{
					$status = 0;
					$msg = "Missing bearer token";
				}
			} else {
				$status = 0;
				$msg = "Invalid booking details";
			}
		} else {
        	$status = 0;
        	$msg = 'Method is not allowed';
        }
		return self::handleReturn($requestDataNew, $status, $msg);
	}
	public static function BookingTracking($requestdata) {
		global $CFG;
		$requestDataNew = new stdClass();
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			/* dispatch, arrived, pickup, drop */
			$bookingId = $requestdata->data->bookingId;
			$dutyStatus = $requestdata->data->dutyStatus;
			$lat = $requestdata->data->lat;
			$lng = $requestdata->data->lng;
			
			$gmtDatetime = $requestdata->data->gpsTime;
			$date = new DateTime($gmtDatetime, new DateTimeZone('GMT'));
			$date->setTimezone(new DateTimeZone('Asia/Kolkata'));
			$gpsTime =  $date->format('Y-m-d H:i:s');
			
			$query = "SELECT `original_param`, `ext_booking_number`, `client_referance_number` FROM booking_creation WHERE ext_booking_number = '$bookingId'";
			if($queryData = mysqli_fetch_assoc(mysqli_query($CFG, $query))) {
				$original_param = json_decode($queryData['original_param']);
				$ext_booking_number = $bookingId; 
				$client_referance_number = $queryData['client_referance_number'];
				$time = time();
				$encodeedData = json_encode($requestdata);
				$queryChek = "SELECT `id` FROM booking_tracking WHERE ext_booking_number = '$ext_booking_number' AND client_referance_number = '$client_referance_number' AND dutyStatus = '$dutyStatus'";
				$result = mysqli_query($CFG, $queryChek);
				$pickup = false;
				if(mysqli_num_rows($result)>0) {
					if($dutyStatus == 'pickup') {	
						$pickup = true;
					}else if($dutyStatus != 'pickup') {
						$queryInsert = "UPDATE booking_tracking SET requestdata = '$encodeedData', modified_at = '$time' WHERE ext_booking_number = '$ext_booking_number' AND client_referance_number = '$client_referance_number' AND dutyStatus = '$dutyStatus'";
					}
				} else {
					$queryInsert = "INSERT INTO booking_tracking(ext_booking_number, client_referance_number, dutyStatus, requestdata, created_at, modified_at) VALUES ('$ext_booking_number', '$client_referance_number', '$dutyStatus', '$encodeedData', $time, $time)";
				}
				if($pickup) {  
					$requestDataNew->event_name = "driver_location";
					$requestDataNew->event_datetime = date("Y-m-d H:i:s",time());
					$requestDataNew->seller_code = SELLER_CODE;
					$requestDataNew->booking_id = $client_referance_number;
					$requestDataNew->locations = array(
						array(
							"current_trip_status"=>$dutyStatus,
							"lat"=>$lat,
							"lng"=>$lng,
							"time"=>date("Y-m-d H:i:s",time()),
							"gps_time"=>$gpsTime,
							"location_accuracy"=>"",
							"speed"=>"",
							"provider"=>"",
							"bearing"=>"",
							"altitude"=>""
						)
					);
				} else {
					if(mysqli_query($CFG, $queryInsert)) {  
						$requestDataNew->event_name = "driver_location";
						$requestDataNew->event_datetime = date("Y-m-d H:i:s",time());
						$requestDataNew->seller_code = SELLER_CODE;
						$requestDataNew->booking_id = $client_referance_number;
						$requestDataNew->locations = array(
							array(
								"current_trip_status"=>$dutyStatus,
								"lat"=>$lat,
								"lng"=>$lng,
								"time"=>date("Y-m-d H:i:s",time()),
								"gps_time"=>$gpsTime,
								"location_accuracy"=>"",
								"speed"=>"",
								"provider"=>"",
								"bearing"=>"",
								"altitude"=>""
							)
						);
					}
				}
				if($getBearerToken = self::getBearerToken()) {
					if($payload = self::Verify($getBearerToken, KEY)) {
						if($payload['id'] == "]OwHd&I;@*fwkc/") {
							$status = 1;
							$msg = "Token validated";
						} else {
							$status = 0;
							$msg = "Token validatation failed";
						}
					}else{
						$status = 0;
						$msg = "Missing payload";
					}
				}else{
					$status = 0;
					$msg = "Missing bearer token";
				}
			} else {
				$status = 0;
				$msg = "Invalid booking details";
			}
		} else {
        	$status = 0;
        	$msg = 'Method is not allowed';
        }
		return self::handleReturn($requestDataNew, $status, $msg);
	}
	public static function BookingInvoice($requestdata) {
		global $CFG;
		$requestDataNew = new stdClass();
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$bookingId = $requestdata->data->bookingId;
			$query = "SELECT `id`, `original_param`, `ext_booking_number`, `client_referance_number` FROM booking_creation WHERE ext_booking_number = '$bookingId'";
			if($queryData = mysqli_fetch_assoc(mysqli_query($CFG, $query))) {
				$original_param = json_decode($queryData['original_param']);
				$requestDataNew->event_name = "generate_bill";
				$requestDataNew->event_datetime = date("Y-m-d H:i:s",time());
				$requestDataNew->seller_code = SELLER_CODE;
				$client_referance_number = $queryData['client_referance_number'];
				$requestDataNew->booking_id = $client_referance_number;
				$query1 = "SELECT trn FROM booking_status WHERE ext_booking_number = '$bookingId' AND client_referance_number='$client_referance_number'";
				$queryData1 = mysqli_fetch_assoc(mysqli_query($CFG, $query1));
				$requestDataNew->trn = $queryData1 ? $queryData1->trn : "";
				$ext_bill_number = date("y")."/ORX/".$queryData['id']."/".$queryData['client_referance_number'];
				$requestDataNew->ext_bill_number = $ext_bill_number;
				if($getBearerToken = self::getBearerToken()) {
					if($payload = self::Verify($getBearerToken, KEY)) {
						if($payload['id'] == "]OwHd&I;@*fwkc/") {
							$status = 1;
							$msg = "Token validated";
						} else {
							$status = 0;
							$msg = "Token validatation failed";
						}
					}else{
						$status = 0;
						$msg = "Missing payload";
					}
				}else{
					$status = 0;
					$msg = "Missing bearer token";
				}
			} else {
				$status = 0;
				$msg = "Invalid booking details";
			}
		} else {
        	$status = 0;
        	$msg = 'Method is not allowed';
        }
		return self::handleReturn($requestDataNew, $status, $msg);
	}
	public static function getEventDetails($bookingId, $eventName) {
		global $CFG;
		$checkQuery = "SELECT `id` FROM booking_tracking WHERE ext_booking_number ='$bookingId' AND dutyStatus = '$eventName' AND count=0 ORDER BY id ASC LIMIT 1";
		$result = mysqli_query($CFG, $checkQuery);
		return mysqli_num_rows($result);
	}	
	public static function callArrived($data, $bookingId) {
		global $CFG;
        $DriverArrived = self::DriverArrived($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, CURL_URL.'arrived');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($DriverArrived['data']));
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'rqid: '.self::Rqid($DriverArrived['data']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        self::updateBookingTracking($bookingId, 'arrived');
        self::InsertPushbackLog($bookingId, json_encode($DriverArrived['data']), $result, 'arrived', json_encode($data));
        $result = json_decode($result);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
	}	
	public static function callDispatch($data, $bookingId) {
		global $CFG;
		$DriverDispatch = self::DriverDispatch($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, CURL_URL.'dispatch');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($DriverDispatch['data']));
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'rqid: '.self::Rqid($DriverDispatch['data']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        self::updateBookingTracking($bookingId,'dispatch');
        self::InsertPushbackLog($bookingId, json_encode($DriverDispatch['data']), $result, 'dispatch', json_encode($data));
        $result = json_decode($result);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
	}
	public static function getBearerToken() {
		if($headers = getallheaders()) {
			 if (isset($headers['Authorization'])) {
		        $authHeader = $headers['Authorization'];
		        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
		            return $matches[1];
		        }
		    }
		}
	}
	public static function handleReturn($data, $status, $msg) {
		$return = [
			 "status"=>$status,
			 "msg"=>$msg,
			 "requestTime"=>date("Y-m-d H:i:s"),
			 "data"=>$data
		];
		return $return;
	}
	public static function updateBookingTracking($bookingId, $dutyStatus) {
		global $CFG;
		$time = time();
		$queryUpdate = "UPDATE booking_tracking SET count = 1, modified_at = '$time' WHERE ext_booking_number = '$bookingId' AND dutyStatus='$dutyStatus'";
		if($result = mysqli_query($CFG, $queryUpdate)) {
			return 1;
		} else {
			return 0;
		}
	}
	public static function callVoidDuty($requestdata) {
		global $CFG;
		$requestDataNew = new stdClass();
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$bookingId = $requestdata->data->bookingId;
			$query = "SELECT `original_param`, `ext_booking_number`, `client_referance_number` FROM booking_creation WHERE ext_booking_number = '$bookingId'";
			if($queryData = mysqli_fetch_assoc(mysqli_query($CFG, $query))) {
				$original_param = json_decode($queryData['original_param']);
				$requestDataNew->event_name = 'void_duty';
				$requestDataNew->event_datetime = date("Y-m-d H:i:s",time());
				$requestDataNew->seller_code = SELLER_CODE;
				$requestDataNew->booking_id = $queryData['client_referance_number'];
				$requestDataNew->reason_id = -1;
				$requestDataNew->reason = "Vehical is not available";
				if($getBearerToken = self::getBearerToken()) {
					if($payload = self::Verify($getBearerToken, KEY)) {
						if($payload['id'] == "]OwHd&I;@*fwkc/") {
							$status = 1;
							$msg = "Token validated";
						} else {
							$status = 0;
							$msg = "Token validatation failed";
						}
					} else {
						$status = 0;
						$msg = "Missing payload";
					}
				} else {
					$status = 0;
					$msg = "Missing bearer token";
				}
			} else {
				$status = 0;
				$msg = "Invalid booking details";
			}
		} else {
        	$status = 0;
        	$msg = 'Method is not allowed';
        }
		return self::handleReturn($requestDataNew, $status, $msg);
	}
	public static function driverReassign($requestdata) {
		global $CFG;
		$requestDataNew = new stdClass();
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			$bookingId = $requestdata->data->bookingId;
			$driverName = $requestdata->data->driverName;
			$driverMobile = $requestdata->data->driverMobile;
			$plateNo = $requestdata->data->plateNo;
			$query = "SELECT `original_param`, `ext_booking_number`, `client_referance_number` FROM booking_creation WHERE ext_booking_number ='$bookingId'";
			if($queryData = mysqli_fetch_assoc(mysqli_query($CFG, $query))) {
				$original_param = json_decode($queryData['original_param']);
				if($bookingId) {
					$requestDataNew->event_name = "assigned";
					$requestDataNew->event_datetime = date("Y-m-d H:i:s",time());
					$requestDataNew->seller_code = SELLER_CODE;
					$requestDataNew->booking_id = $queryData['client_referance_number'];
					$requestDataNew->supplier_id = SUPPLIER_ID;
					$requestDataNew->driver_type = "oncall";
					$requestDataNew->driver_name = $driverName;
					$requestDataNew->driver_phone = $driverMobile;
					$requestDataNew->driving_license = "N/A";
					$requestDataNew->car_number = $plateNo;
					$requestDataNew->model_id = $original_param->model_id;
					$requestDataNew->car_model = "N/A";
					$requestDataNew->car_fuel_type = "hybrid";
					$requestDataNew->dispatch_datetime = date("Y-m-d H:i:s",time());
					$requestDataNew->car_changed = "no_change";
					$requestDataNew->reassign = "yes";
					$requestDataNew->reassign_reason_id = -1;
					$requestDataNew->reassign_reason = "Vehical is not available";
				}
				if($getBearerToken = self::getBearerToken()) {
					if($payload = self::Verify($getBearerToken, KEY)) {
						if($payload['id'] == "]OwHd&I;@*fwkc/") {
							$status = 1;
							$msg = "Token validated";
						} else {
							$status = 0;
							$msg = "Token validatation failed";
						}
					}else{
						$status = 0;
						$msg = "Missing payload";
					}
				}else{
					$status = 0;
					$msg = "Missing bearer token";
				}
			}else {
				$status = 0;
				$msg = "Invalid booking details";
			}
		} else {
        	$status = 0;
        	$msg = 'Method is not allowed';
        }
		return self::handleReturn($requestDataNew, $status, $msg);
	}
	public static function getAssignmentDetails($bookingId) {
		global $CFG;
		$checkQuery = "SELECT * FROM pushback_log WHERE ext_booking_number ='$bookingId' AND api_type='assigned'";
		$result = mysqli_query($CFG, $checkQuery);
		$queryData = mysqli_fetch_assoc($result);
		$responsedata = $queryData['responsedata'];
		$data = json_decode($responsedata, true);
		$status = $data['status'];
		if($status=="success") {
			return 1;
		} else {
			return 0;
		}
	}
}
