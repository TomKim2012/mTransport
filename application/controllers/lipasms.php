<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
// Receiving messages boils down to reading values in the POST array
// This example will read in the values received and compose a response.

// 1.Import the helper Gateway class
require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/AfricasTalkingGateway.php';
class Lipasms extends REST_Controller {
	function __construct() {
		parent::__construct ();
		date_default_timezone_set ( 'Africa/Nairobi' );
		$this->load->library ( 'curl' );
		$this->load->library ( 'CoreScripts' );
		$this->load->model ( 'Member_Model', 'members' );
		$this->load->model ( 'Paybill_model', 'transaction' );
	}
	function custSms_get() {
		
		// Add Balance from the text
		if ($this->get ( "phoneNumber" )) {
			
			$phone = $this->get ( "phoneNumber" );
			$custData = $this->members->getSingleCustomer ( 'phone', $phone );
			
			if (empty ( $custData ['userId'] )) {
				$message = 'Dear Customer, your phoneNumber is not registered.' . 'Kindly contact your nearest branch';
				echo $message;
				$this->corescripts->_send_sms2 ( $phone, $message );
				return;
			}
			
			$response = $this->corescripts->getTotals ( $custData ['userId'] );
			
			// print_r($response);
			// return;
			
			if (empty ( $response )) {
				$message = 'Dear Customer, you dont have any registered tills.' . 'Kindly call branch to be assigned a Till';
				echo $message;
				$this->corescripts->_send_sms2 ( $custData ['mobileNo'], $message );
				return;
			} else if ($response [0] ['count'] == 0) {
				$message = "Dear " . $custData ['firstName'] . ", There were no Lipa Na Mpesa transactions for your tills today.";
			} else {
				
				// //---------------Compose the SMS-----------------------------------
				// $tDate = date ( "d/m/Y" );
				$tTime = date ( "h:i A" );
				$message = "Dear " . $custData ['firstName'] . ", Lipa Na Mpesa Summary as at " . $tTime . " is as follows:";
				$counter = 1;
				foreach ( $response as $row ) {
					$message .= "<" . $counter ++ . "." . $row ['business_name'] . "- KES " . number_format ( $row ['totals'] ) . ">";
				}
			}
			echo $message;
			$sms_feedback = $this->corescripts->_send_sms2 ( $custData ['mobileNo'], $message );
			if ($sms_feedback) {
				echo "Success";
			} else {
				echo "Failed";
			}
		} else {
			echo 'No phone Number sent';
		}
	}
	function dailySMSToMerchant_get() {
		// Get Transacted Merchants phone Numbers
		$results = $this->members->getTransactedMerchants ();
		
		foreach ( $results as $row ) {
			$bankBalance = number_format ( $this->members->getCustTransaction ( $row ['clientcode'], 2 ) );
			$tillBalance = number_format ( $this->members->getTillTotal ( $row ['business_number'] ) );
			$phoneNumber = $row ['phone'];
			
			$tTime = date ( "h:i A" );
			// Send SMSes to this merchants
			$message = "Dear " . $row ['firstName'] . ",your summary as at " . $tTime . 
			" is as follows: Lipa Na Mpesa Totals " . " is Kes " . $tillBalance .
			 ",Savings balance is Kes " . $bankBalance . ". Contact 0705300035 for any queries.";
			
			//echo $message;
			
			if (isset ( $phoneNumber )) {
				echo "Send request to send";
				$this->sendSMS ( $phoneNumber, $message, $this->random_string ( 6 ) );
			}
		}
	}
	
	function random_string($length = 4) {
		$firstPart = substr ( str_shuffle ( "ABCDEFGHIJKLMNOPQRSTUVWXYZ" ), 0, 2 );
		// Generate random 4 character string
		$string = md5 ( microtime () );
		$secondPart = substr ( $string, 1, $length );
		$randomString = $firstPart . strtoupper ( $secondPart );
		
		// Confirm its not a duplicate
		$this->db->where ( 'transactionId', $randomString );
		$query = $this->db->get ( 'smsModel' );
		if ($query->num_rows () > 0) {
			random_string ( $length );
		} else {
			return $randomString;
		}
	}
	function sendSMS($phoneNo, $message, $mpesaCode) {
		$smsInput = $this->corescripts->_send_sms2 ( $phoneNo, $message );
		
		// Persist sms Log
		$smsInput ['transactionId'] = $mpesaCode;
		$smsInput ['tstamp'] = date ( "Y-m-d G:i" );
		$smsInput ['message'] = $message;
		$smsInput ['destination'] = $phoneNo;
		
		$this->transaction->insertSmsLog ( $smsInput );
		
		if ($smsInput ['status']) {
			echo " and sms sent to customer";
		} else {
			echo " sms not sent to customer";
		}
	}
}