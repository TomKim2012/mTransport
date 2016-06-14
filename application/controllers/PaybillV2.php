<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
require APPPATH . '/libraries/AfricasTalkingGateway.php';
class PaybillV2 extends CI_Controller {
	function PaybillV2() {
		parent::__construct ();
		date_default_timezone_set ( 'Africa/Nairobi' );
		$this->load->library ( 'CoreScripts' );
		$this->load->model ( 'Paybill_model', 'transaction' );
		$this->load->model ( 'Member_model', 'members' );
		$this->load->helper ( 'file' );
	}
	function index() {
		$request_body = file_get_contents ( 'php://input' );
		$json_parameters = json_decode ( $request_body, true );
		
		// FullNames
		$fullNames = $json_parameters ['KYCInfoList'] [0] ['KYCValue'] . ' ' . $json_parameters ['KYCInfoList'] [1] ['KYCValue'];
		if (sizeof ( $json_parameters ['KYCInfoList'] ) == 3) {
			$fullNames = $fullNames . ' ' . $json_parameters ['KYCInfoList'] [2] ['KYCValue'];
		}
		
		// Time-stamp
		// receiving this:20140227082020
		// what it should be:2015-08-13 13:19:11.000
		$allDate = $json_parameters ['TransTime'];
		
		// Date
		$year = substr ( $json_parameters ['TransTime'], 0, 4 );
		$month = substr ( $json_parameters ['TransTime'], 4, 2 );
		$day = substr ( $json_parameters ['TransTime'], 6, 2 );
		$hour = substr ( $json_parameters ['TransTime'], 8, 2 );
		$minute = substr ( $json_parameters ['TransTime'], 10, 2 );
		$seconds = substr ( $json_parameters ['TransTime'], 12, 2 );
		
		$tstampFormat = $year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute . ':' . $seconds;
		
		/**
		 * Extract IPN Parameters
		 */
		$parameters = array (
				'id' => $json_parameters ['Id'],
				'business_number' => $json_parameters ['BusinessShortCode'],
				'tstamp' => $tstampFormat,
				'mpesa_trx_date' => $day . '/' . $month . '/' . substr ( $year, 2, 2 ),
				'mpesa_trx_time' => $hour . ':' . $minute . ':' . $seconds,
				'mpesa_code' => $json_parameters ['TransID'],
				'mpesa_acc' => $json_parameters ['BillRefNumber'],
				'mpesa_msisdn' => $json_parameters ['MSISDN'],
				'mpesa_amt' => $json_parameters ['TransAmount'],
				'mpesa_sender' => strtoupper ( $fullNames ),
				'ipAddress' => $this->input->ip_address () 
		);
		
		// Log the details
		$myFile = "application/controllers/mpesalog.txt";
		write_file ( $myFile, "\n=============================\n", 'a+' );
		if (! write_file ( $myFile, print_r ( $request_body, true ), 'a+' )) {
			echo "Unable to write to file!";
		}
		// die();
		// foreach ($json_parameters as $var => $value) {
		// if(!write_file($myFile, "$var = $value\n",'a+')){
		// echo "Unable to write to file!";
		// }
		// }
		
		$user = 'pioneerfsa';
		$pass = 'transport@2014';
		
		/**
		 * Saving Parameters on successful Authentication
		 */
		if (($user == 'pioneerfsa' && $pass == 'financial@2013') || ($user = 'mTransport' && $pass = 'transport@2014')) {
			if ($parameters ['id']) {
				$response = $this->transaction->record_transaction ( $parameters );
				echo $response ['message'];
				$parameters ['verificationCode'] = $response ['verificationCode'];
				
				// $ipnAddress = $this->transaction->getipnaddress ( $parameters ['business_number'] );
				$alphaNumeric = $this->transaction->getAlphanumeric ( $parameters ['business_number'] );
				
				if (isset ( $alphaNumeric->alphanumeric )) {
					$parameters ['alphanumeric'] = $alphaNumeric->alphanumeric;
				} else {
					$parameters ['alphanumeric'] = "PioneerFSA";
				}
				
				if (! empty ( $ipnAddress )) {
					// $this->performClientIPN ( $getipnaddress, $parameters );
				}
				// Owner's Message
				$this->prepareOwnerMessage ( $parameters );
				$this->prepareCustomerMessage ( $parameters );
			} else {
				echo "FAIL|No transaction details were sent";
			}
		} else {
			echo "FAIL|The payment could not be completed at this time.
					Incorrect username / password combination. Pioneer FSA";
		}
	}
	function getFirstName($names) {
		$fullNames = explode ( " ", $names );
		$firstName = $fullNames [0];
		$customString = substr ( $firstName, 0, 1 ) . strtolower ( substr ( $firstName, 1 ) );
		return $customString;
	}
	function format_Number($phoneNumber) {
		$formatedNumber = "0" . substr ( $phoneNumber, 3 );
		return $formatedNumber;
	}
	function prepareOwnerMessage($parameters) {
		// Send SMS to Client
		$tDate = date ( "d/m/Y" );
		$tTime = date ( "h:i A" );
		
		// if($parameters ['business_number'] == '510514'){
		// $parameters['business_number']=$parameters['mpesa_acc'];
		// }
		
		$till = $this->members->getOwner_by_id ( $parameters ['mpesa_acc'] );
		
		if ($parameters ['business_number'] == '510514') {
			$balance = $this->members->getTillTotal ( $parameters ['business_number'], $parameters ['mpesa_acc'] );
		} else {
			$balance = $this->members->getTillTotal ( $parameters ['business_number'], null );
		}
		
		$message = "Dear " . $till ['businessName'] . ", transaction " . $parameters ['mpesa_code'] . " of Ksh." . number_format ( $parameters ['mpesa_amt'] ) . " received from " . $parameters ['mpesa_sender'] . " on " . $tDate . " at " . $tTime . ". New Till balance is Ksh " . $balance;
		
		// echo $parameters ['alphanumeric'];
		if ($till ['phoneNo']) {
			$this->sendSMS ( $till ['phoneNo'], $message, $parameters ['mpesa_code'], $parameters ['alphanumeric'] );
		} else {
			echo "The Till Phone details are not saved";
		}
	}
	function prepareCustomerMessage($parameters) {
		// Send SMS to Client
		$tDate = date ( "d/m/Y" );
		$tTime = date ( "h:i A" );
		if ($parameters ['business_number'] == '510514') {
			$parameters ['business_number'] = $parameters ['mpesa_acc'];
		}
		
		$till = $this->members->getOwner_by_id ( $parameters ['business_number'] );
		$firstName = $this->getFirstName ( $parameters ['mpesa_sender'] );
		if ($parameters ['business_number'] == '898467') {
			return;
		}
		
		if ($parameters ['business_number'] == '510513' || $parameters ['business_number'] == '510511' || $parameters ['business_number'] == '510510' || $parameters ['business_number'] == '510512') {
			$message = $firstName . ",MPESA deposit of " . number_format ( $parameters ['mpesa_amt'] ) . " confirmed." . " Own a prime plot by raising 10% deposit, pay balance in upto 2yrs. Offer: Kitengela 499K, Rongai 799K.0705300035";
			$phone = $this->format_IPNnumber ( $parameters ['mpesa_msisdn'] );
			$this->sendSMS ( $phone, $message, $parameters ['mpesa_code'], $parameters ['alphanumeric'] );
		} else {
			$message = "Dear " . $firstName . " transaction " . $parameters ['mpesa_code'] . " of Ksh." . number_format ( $parameters ['mpesa_amt'] ) . " to " . $till ['businessName'] . " confirmed.For queries call 0705300035. Pioneer FSA";
			$marketing_message = "Pioneer FSA Special offer on plots! Raise 10% deposit and own a plot on offer at Kamulu 379K, Rongai 895K, pay balance in 24 months.0705300035";
			$phone = $this->format_IPNnumber ( $parameters ['mpesa_msisdn'] );
			// $this->sendSMS ( $phone, $message, $parameters ['mpesa_code'], $parameters ['alphanumeric'] );
			// $this->sendSMS ( $phone, $marketing_message, $parameters ['mpesa_code'], $parameters ['alphanumeric'] );
		}
	}
	function sendSMS($phoneNo, $message, $mpesaCode, $alphaNumeric) {
		$smsInput = $this->corescripts->_send_sms2 ( $phoneNo, $message, $alphaNumeric );
		
		// Persist sms Log
		$smsInput ['transactionId'] = $mpesaCode;
		$smsInput ['tstamp'] = date ( "Y-m-d G:i" );
		$smsInput ['message'] = $message;
		$smsInput ['destination'] = $phoneNo;
		$smsInput ['retries'] = 0;
		
		$this->transaction->insertSmsLog ( $smsInput );
		
		if ($smsInput ['status']) {
			echo " and sms sent to customer";
		} else {
			echo " Failed to send sms";
		}
	}
	function truncateString($content) {
		$truncated = "";
		if (strlen ( $content ) > 7) {
			$truncated = substr ( $content, 0, 7 ) . "** ";
		} else {
			$truncated = $content;
		}
		return $truncated;
	}
	function performClientIPN($getipnaddress, $parameters) {
		for($x = 0; $x < 4; $x ++) {
			$ipnstatus = $this->httpPost ( $getipnaddress, $parameters, $x + 1 );
			if ($ipnstatus) {
				$x = 4;
			} else {
				sleep ( 30 );
			}
		}
	}
	function format_IPNnumber($phoneNumber) {
		$formatedNumber = "0" . substr ( $phoneNumber, 3 );
		return $formatedNumber;
	}
	function deliveryCallBack() {
		$messageId = $this->input->post ( 'id' );
		$status = $this->input->post ( 'status' );
		
		$this->transaction->updateLog ( $messageId, $status );
	}
	function httpPost($getipndetails, $params, $attempt) {
		$url = $getipndetails->ipn_address;
		$ipn_id = $getipndetails->tillModel_id;
		$username = $getipndetails->username;
		$password = $getipndetails->password;
		
		// username and password in params
		$params ['user'] = $username;
		$params ['pass'] = $password;
		
		$postData = '';
		// create name value pairs seperated by &
		foreach ( $params as $k => $v ) {
			$postData .= $k . '=' . $v . '&';
		}
		rtrim ( $postData, '&' );
		
		$postData = str_replace ( ' ', '%20', $postData );
		
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url . '?' . $postData );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 10 );
		$response = curl_exec ( $ch );
		
		if (! curl_errno ( $ch )) {
			
			$info = curl_getinfo ( $ch );
			$http_status = $info ['http_code'];
			if ($info ['http_code'] == 0) {
				$desc = $response;
				$status = "Failed";
			} else {
				$desc = $response;
				$status = "Successful";
			}
		} else {
			
			$info = curl_getinfo ( $ch );
			$http_status = curl_errno ( $ch );
			$desc = curl_error ( $ch );
			$status = "Not Successful";
		}
		
		$inplog = array (
				'ipn_id' => $ipn_id,
				'status' => $status,
				'description' => $desc,
				'http_status' => $http_status,
				'attempt' => $attempt 
		);
		
		curl_close ( $ch );
		
		if ($this->transaction->inseripnlog ( $inplog )) {
			if ($http_status == 200) {
				return true;
			} else {
				if ($attempt == 4) {
					return true;
				} else {
					return false;
				}
			}
		} else {
			return false;
		}
	}
}

?>