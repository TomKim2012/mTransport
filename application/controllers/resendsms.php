<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
//require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/AfricasTalkingGateway.php';
class ResendSms extends CI_Controller {
	function ResendSms() {
		parent::__construct ();
		$this->load->model ( 'resendSms_model' );
		
		$this->load->library ( 'CoreScripts' );
	}
	function index() {
		$data = $this->resendSms_model->getFailed ();
		
//		print_r($data);
		/*
		 * checks of there are messages that require being resent
		 */
		if (!empty($data) ){
		
			foreach ( $data as $row ) {
				$phoneNo = ($row->destination);
				$message = ($row->message);
				$mpesaCode = ($row->transactionId);
				$messageId = ($row->messageId);
				$messageStatus = ($row->status);

				$this->send_sms ( $phoneNo, $message, $mpesaCode );			
				
			}
			
		}
		
		else{
			echo 'Fail|No records or Maximim retries reached';
		}
	
	}
	function send_sms($phoneNo, $message, $mpesaCode) {
		echo $message;
		
		$smsInput = $this->corescripts->_send_sms2 ( $phoneNo, $message, "PioneerFSA" );
		$transactionId = $mpesaCode;
		$messageId = $smsInput['messageId'];
		$status = $smsInput['status'];
		
//		echo "messageId>>".$messageId."status>>".$status;

		$this->resendSms_model->updateSMS($messageId, $status, $transactionId);

	}
}