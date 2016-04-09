<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
require APPPATH . '/libraries/AfricasTalkingGateway.php';
class Paybill extends CI_Controller {
	function Paybill() {
		parent::__construct ();
		date_default_timezone_set ( 'Africa/Nairobi' );
		$this->load->library ( 'CoreScripts' );
		$this->load->model ( 'Paybill_model', 'transaction' );
		$this->load->model ( 'Member_model', 'members' );
		$this->load->helper ( 'file' );
	}

	function index() {
		/**
		 * Extract IPN Parameters
		 */
		$parameters = array (
				'id' => $this->input->get ( 'id' ),
				'business_number' => $this->input->get ( 'business_number' ),
				'orig' => $this->input->get ( 'orig' ),
				'dest' => $this->input->get ( 'dest' ),
				'tstamp' => $this->input->get ( 'tstamp' ),
				'mpesa_code' => $this->input->get ( 'mpesa_code' ),
				'mpesa_acc' => $this->input->get ( 'mpesa_acc' ),
				'mpesa_msisdn' => $this->input->get ( 'mpesa_msisdn' ),
				'mpesa_trx_date' => $this->input->get ( 'mpesa_trx_date' ),
				'mpesa_trx_time' => $this->input->get ( 'mpesa_trx_time' ),
				'mpesa_amt' => $this->input->get ( 'mpesa_amt' ),
				'mpesa_sender' => $this->input->get ( 'mpesa_sender' ),
				'ipAddress' => $this->input->ip_address () 
		);
		
		$user = $this->input->get ( 'user' );
		$pass = $this->input->get ( 'pass' );
		
		/**
		 * **********************************
		 */
		if ($parameters ['business_number'] == '885850') {
			return;
		}

		if($parameters ['business_number'] == '510514'){
			$parameters ['business_number'] = $parameters ['mpesa_acc'];
		}else if ($parameters ['business_number'] == '510513' || $parameters ['business_number'] == '510511' || 
			$parameters ['business_number'] == '510510' || $parameters ['business_number'] == '510512') { 
			
		}else {
			/*
			 * Should be sorted asap
			 * we are making account number to be the same as business number because Pioneer's integration does not take
			 * into consideration empty account Number;
			 */
			$parameters ['mpesa_acc'] = $parameters ['business_number'];
		}
		
		/**
		 * Saving Parameters on successful Authentication
		 */
		if (($user == 'pioneerfsa' && $pass == 'financial@2013') || ($user = 'mTransport' && $pass = 'transport@2014')) {
			if ($parameters ['id']) {
				$response = $this->transaction->record_transaction ( $parameters );
				echo $response ['message'];
				$parameters ['verificationCode'] = $response ['verificationCode'];
				
				//$ipnAddress = $this->transaction->getipnaddress ( $parameters ['business_number'] );
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
				
				/*if ($alphaNumeric->allowCustomerSMS == 1) {
					$this->prepareCustomerMessage ( $parameters );
				}*/
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
		$till = $this->members->getOwner_by_id ( $parameters ['business_number'] );
		$balance = $this->members->getTillTotal ( $parameters ['business_number'] );
		
		$message = "Dear " .  $till ['businessName'] . ", transaction " . $parameters ['mpesa_code'] . " of Ksh." . number_format ( $parameters ['mpesa_amt'] ) . " received from " . $parameters ['mpesa_sender'] . " on " . $tDate . " at " . $tTime . ". New Till balance is Ksh " . $balance;
		
		//echo $parameters ['alphanumeric'];
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
		$till = $this->members->getOwner_by_id ( $parameters ['business_number'] );
		$firstName = $this->getFirstName ( $parameters ['mpesa_sender'] );

		if ($parameters ['business_number'] == '898467'){
			return;
		}

		if ($parameters ['business_number'] == '510513' || $parameters ['business_number'] == '510511' || 
			$parameters ['business_number'] == '510510' || $parameters ['business_number'] == '510512') {
			$message=$firstName.",MPESA deposit of ".number_format($parameters['mpesa_amt'])." confirmed.".
					"Own a plot by raising 10% deposit, pay balance in upto 2yrs.Offer: Kamulu 399K, Rongai 995K,0705300035";
			$phone = $this->format_IPNnumber ( $parameters ['mpesa_msisdn'] );
			$this->sendSMS ( $phone, $message, $parameters ['mpesa_code'], $parameters ['alphanumeric'] );
			
		}else{
			$message = "Dear " . $firstName . " MPESA payment of Ksh." . 
			number_format ( $parameters ['mpesa_amt'] ) . " to ".$till ['businessName'] ." confirmed.";
			$marketing_message = "Pioneer FSA Special offer on plots! Own a prime plot by raising 10% deposit, pay balance in upto 2yrs. Kamulu 399K, Rongai Commercial Plots 995K. 0724391213";
			$phone = $this->format_IPNnumber ( $parameters ['mpesa_msisdn'] );
			$this->sendSMS ( $phone, $message, $parameters ['mpesa_code'], $parameters ['alphanumeric'] );
			$this->sendSMS ( $phone, $marketing_message, $parameters ['mpesa_code'], $parameters ['alphanumeric'] );
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