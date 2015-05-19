<?php
//defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
//require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/AfricasTalkingGateway.php';
class Template extends CI_Controller {
	
	function Template(){
		parent::__construct();
		$this->load->library ( 'CoreScripts' );
		$this->load->model('template_model');		
	
	}	

	function index() {		
		
		$firstName = "";
		$surName = "";
		$amount = "";
		$tTime = "";
		$businessNumber = "";
		
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
		

		foreach ($parameters as $row) {
			
			$data = $this->template_model->customers($row->mpesa_msisdn);
			
			if (empty($data)) {
					
				$fullNames = explode ( " ", $row->mpesa_sender);
				$firstName = $fullNames [0];
				$surName = $fullNames [1];
				$amount = $row->mpesa_amt;
				$tTime = $row->mpesa_trx_time;
				$businessNumber = $row-> business_number;
				
				
				$data = array(
					'firstName'=> $firstName,
					'surName' => $surName,
					'phoneNo' => $row->mpesa_msisdn,
					'businesNo' => $row-> business_number
				);
				
				
				$this->db->insert('Customers', $data );
				
			}

			$businessName = $this->template_model->getBusinessName($businessNumber);
			
			$tillModel_Id = $this->template_model->getTillModel_Id($businessNumber);
			
			//$message = $this->getMerchantMessage($tillModel_Id, $msgId);
			
			$message = $this->getMerchantMessage($parameters);
				
			echo $message;
			
			//$this->sendMessage($row->mpesa_msisdn, $message, 'PioneerFSA');			
			
		}	
	
	}
	

	function getMerchantMessage($tillId, $msgId, $firstName, $amount, $tTime, $businessName) {
		$str = $this->template_model->getCustomerMessage($tillId, $msgId);
		
		$str = str_replace("#fullNames", $firstName, $str);
		
		$str = str_replace("#amount", $amount, $str);
		
		$str = str_replace("#time", $tTime, $str);
		
		$str = str_replace("#Business", $businessName, $str);
			
	}
	
	
	function sendMessage($phoneNo, $message, $alphaNumeric) {
		
		$this->corescripts->_send_sms2 ( $phoneNo, $message, $alphaNumeric );
	}
	
	function customMessages() {
		$data = $this->template_model->getCustomerDetails('815916');
		
		
		//$businessName = $this->template_model->getBusinessName('815916');
		//echo $businessName;
		
		//var_dump($data);
		
		
		foreach ( $data as $row ) {
		
			$customerName = $row->mpesa_sender;
			$amount = $row->mpesa_amt;
			$customerNumber = $row->mpesa_msisdn;
			$tTime = $row->mpesa_trx_time;
		
			$businessName = $this->template_model->getBusinessName('815916');
				
				
			//$str = "Dear #fullNames your payment of Kes #amount was received at #time. Thank you. #Business";
				
			$str = "Halo #fullNames. #Business received your payment of Kes #amount at #time.
					Thank you for your support and Karibu tena.  #Business";
		
			$results = $this->template_model->getCustomerMessage(345);
			//var_dump($results);
				
			echo "<br/>";
				
			$str = str_replace("#fullNames", $customerName, $str);
				
			$str = str_replace("#amount", $amount, $str);
				
			$str = str_replace("#time", $tTime, $str);
				
			$str = str_replace("#Business", $businessName, $str);
				
				
			echo $str;
		
			//$this->corescripts->_send_sms2('0713449301', $str, 'PioneerFSA');
				
			echo "<br/>";
		}
				
		
	}

			function sendCustomerMessage( $phoneNo, $message, $alphaNumeric) {
		
				$this->corescripts->_send_sms2 ( $phoneNo, $message, $alphaNumeric );

	}

	
}
