<?php
class myxml{
	public $error=0;
	public $response="";

	public function listRolesRecursive($myArray) {
		$xml = "";
		if(is_array($myArray)){
			foreach ($myArray as $key => $value) {
				$xml .= "<".$key;
				if(empty($value) && $value != "0"){
					$xml .= "/>";
				} else {
					$xml .= ">";
					$xml .= $this->listRolesRecursive($value);
					$xml .= "</".$key.">";
				}

				// echo $xml;
				// die;
			}
		} else if(is_string($myArray) || !empty($myArray)) {
			$xml .= $myArray;
		}
	    return $xml;
	}
	public function callAPI($url, $header, $body) {
		$curl = curl_init();
		/*Public IP*/
		 $ORIXURL = "https://reservecar.orixindia.com/DTCabManInterfaceService/WebServices/DTCabManInterfaceService.asmx";
		/*Local IP*/
		//$ORIXURL = "http://192.168.111.113/DTCabManInterfaceService/WebServices/DTCabManInterfaceService.asmx";

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $ORIXURL,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\">\r\n   <soapenv:Header>".$header."</soapenv:Header><soapenv:Body>".$body."</soapenv:Body>\r\n</soapenv:Envelope>",
		  CURLOPT_HTTPHEADER => array(
		    "Content-Type: text/xml",
		    "Postman-Token: 2c8296de-73b2-40a2-abe9-b5ec02785675",
		    "cache-control: no-cache"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			$this->error = 1;
			$this->response = "cURL Error #:" . $err;
		} else {
		  $this->error = 0;
		  $response = str_replace('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">', "", $response);
		  $response = str_replace("</soap:Envelope>", "", $response);
		  $response = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $response);
		  // echo "$response";
		  $this->response = $response;
		}
	}
}
$myobj = new myxml();

?>