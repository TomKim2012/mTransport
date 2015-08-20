<?php
class Template_model extends CI_Model {
	function updateCustomerRecords() {
			
		$this->db->select('*');
		$this->db->from ( 'LipaNaMpesaIPN' );
		$query = $this->db->get();
		$results = $query->result();
		
		var_dump($results);
		//$tillmodel_id = $this->template_model->getTillModel_Id ( $parameters ['business_number'] );
		
		if (! empty ( $results )) {
			echo "Not empty";
			foreach ( $results as $row ) {
				$phoneNo = $row->mpesa_msisdn;
				$businessNo = $row->business_number;
				$tillmodel_id = $this->getTillModel_Id($businessNo);
				$customerDetails = array (
						'firstName' => $this->getFirstName ( $row->mpesa_sender ),
						'lastName' => $this->getLastName ( $row->mpesa_sender ),
						'surName' => $this->getsurName ( $row->mpesa_sender ),
						'phoneNo' => $row->mpesa_msisdn,
						'tillModel_id' => $tillmodel_id 
				);
				
				if ($this->customers ( $phoneNo, $tillmodel_id )) {
					// ignore, record alreasy exists
				} else {
					$this->db->insert ( 'CustomerModel', $customerDetails );
				}
			}
			
			echo 'Previous records have been  updated';
		} else {
			echo 'All records are up to date';
		}
	}
	function customers($mpesa_msisdn, $tillmodel_id) {
		$this->db->select ( '*' );
		$this->db->where ( "phoneNo", $mpesa_msisdn );
		$this->db->where ( "tillModel_id", $tillmodel_id );
		$this->db->from ( 'CustomerModel' );
		
		$query = $this->db->get ();
		
		return $query->result ();
	}
	function retrieveCustomers($tillModel_Id) {
		$this->db->select ( '*' );
		$this->db->where ( 'tillModel_id', $tillModel_Id );
		$this->db->from ( 'CustomerModel' );
		
		$query = $this->db->get ();
		return $query->result ();
	}
	function getCustomerDetails($businessNumber) {
		$this->db->select ( '*' );
		$this->db->where ( 'business_number', $businessNumber );
		$this->db->from ( 'LipaNaMpesaIPN' );
		
		$query = $this->db->get ();
		
		// echo $this->db->last_query();
		return $query->result ();
	}
	function getCustomerId($phoneNumber) {
		$this->db->select ( 'custId' );
		$this->db->where ( 'phoneNo', $phoneNumber );
		$this->db->from ( 'CustomerModel' );
		$query = $this->db->get ();
		return $query->row ()->custId;
	}
	function getAlphanumeric($tillId) {
		$this->db->select ( 'alphanumeric' );
		$this->db->where ( 'tillModel_id', $tillId );
		$this->db->from ( 'Alphanumeric' );
		$query = $this->db->get ();
		return $query->row ()->alphanumeric;
	}
	function getBusinessName($businessNumber) {
		$this->db->select ( 'businessName' );
		$this->db->where ( 'business_number', $businessNumber );
		$this->db->from ( 'TillModel' );
		$result = $this->db->get ();
		
		return $result->row ()->businessName;
	}
	function getTillModel_Id($businessNumber) {
		$this->db->select ( 'id' );
		$this->db->where ( 'business_number', $businessNumber );
		$this->db->from ( 'TillModel' );
		$result = $this->db->get ();
		
		return $result->row ()->id;
	}
	function insertCustomer($customerDetails) {
		$this->db->insert ( 'CustomerModel', $customerDetails );
		return $this->db->insert_id ();
	}
	function updateFKCust_id($parameters, $cust_id) {
		$updates = array (
				'FKcust_id' => $cust_id
		);
		
		$this->db->where('tstamp', $parameters['tstamp']);
		$this->db->where ( 'business_number', $parameters['business_number'] );
		$this->db->where('mpesa_msisdn', $parameters['mpesa_msisdn']);
		$query = $this->db->update ( 'LipaNaMpesaIPN', $updates );
		
		//echo $this->db->last_query ();
		
	}	
	function getTransactionMessage($tillModel_id) {
		$this->db->select ( 'message' );
		$this->db->where ( 'tillModel_Id', $tillModel_id );
		$this->db->where ('type', 'Transaction: After customer transaction');
		$this->db->where ( 'isDefault', '1' );
		$this->db->from ( 'TemplateModel' );
		$query = $this->db->get ();
	
		// echo $this->db->last_query();
	
		return $query->row()->message;
	}
	
	function getDefaultMessage() {
		$this->db->select ( 'message' );
		$this->db->where ('type', 'Transaction: After customer transaction');
		$this->db->where ( 'isDefault', '2' );
		$this->db->from ( 'TemplateModel' );
		$query = $this->db->get ();
		
		 echo $this->db->last_query();
		
		return $query->row()->message;		
	}
	function getCustomerMessage($tillModel_id) {
		$this->db->select ( 'message' );
		$this->db->where ( 'tillModel_Id', $tillModel_id );
		$this->db->where ( 'isDefaultAutomatic', '1' );
		$this->db->from ( 'TemplateModel' );
		$query = $this->db->get ();
		
		// echo $this->db->last_query();
		
		return $query->row ()->message;
	}
	function getCustomersCommunicationMessage($tillModel_id) {
		$this->db->select ( 'message' );
		$this->db->where ( 'tillModel_Id', $tillModel_id );
		$this->db->where ( 'isDefaultCustom', '1' );
		$this->db->from ( 'template' );
		$query = $this->db->get ();
		
		// echo $this->db->last_query();
		
		return $query->row ()->message;
	}
	function getMerchantCredit($tillModel_id) {
		$this->db->select ( 'credit_amt' );
		$this->db->where ( 'tillModel_Id', $tillModel_id );
		$this->db->from ( 'Credit' );
		$query = $this->db->get ();
		$credit = $query->row ()->credit_amt;
		
		$this->db->select ( 'SettingValue' );
		$this->db->where ( 'id', '3' );
		$this->db->from ( 'SettingModel' );
		$result = $this->db->get ();
		$cost = $result->row ()->SettingValue;
		
		if ($credit >= $cost) {
			$credit = $credit - $cost;
			$data = array (
					'credit_amt' => $credit 
			);
			
			$this->db->where ( 'tillModel_id', $tillModel_id );
			$this->db->update ( 'Credit', $data );
			
			print_r ( $credit );
			return TRUE;
		}
	}
	function getCustomMerchantMessages() {
		$this->db->select ( '*' );
		$this->db->from ( 'Customers' );
		
		$query = $this->db->get ();
	}
	function getFirstName($names) {
		$fullNames = explode ( " ", $names );
		$firstName = $fullNames [0];
		$customString = substr ( $firstName, 0, 1 ) . strtolower ( substr ( $firstName, 1 ) );
		return $customString;
	}
	function getLastName($names) {
		$fullNames = explode ( " ", $names );
		$lastName = $fullNames [1];
		$customString = substr ( $lastName, 0, 1 ) . strtolower ( substr ( $lastName, 1 ) );
		return $customString;
	}
	function getsurName($names) {
		$fullNames = explode ( " ", $names );
		if (str_word_count($names)>2) {
			$surName = $fullNames [2];
			$customString = substr ( $surName, 0, 1 ) . strtolower ( substr ( $surName, 1 ) );
			return $customString;
		} else {
			return null;
		}
			
	}
}