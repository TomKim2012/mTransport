<?php

class Template_model extends CI_Model {
	
	function customers($mpesa_msisdn) {
		
		$this->db->select('*');
		$this->db->where("phoneNo", $mpesa_msisdn);
		$this->db->from('Customers');
		
		$query = $this->db->get();
		
		return $query->result();
		
	}
	function getCustomerDetails($businessNumber) {
		
		$this->db->select('*');
		$this->db->where ('business_number', $businessNumber);
		$this->db->from('LipaNaMpesaIPN');
		
		$query = $this->db->get();

		//echo $this->db->last_query();
		return $query->result();
	}
	
	function getCustomerId($phoneNumber) {
		
		$this->db->select('custId');
		$this->db->where ('phoneNo', $phoneNumber);
		$this->db->from('Customers');
		
		$query = $this->db->get();
		return $query->row()->custId;
	}
	
	function getBusinessName($businessNumber) {
		
		$this->db->select('businessName');
		$this->db->where ('business_number', $businessNumber);
		$this->db->from('TillModel');
		$result = $this->db->get();
		
		return $result->row()->businessName;		
	}
	
	function getTillModel_Id($businessNumber) {
		
		$this->db->select('id');
		$this->db->where ('business_number', $businessNumber);		
		$this->db->from('TillModel');
		$result = $this->db->get();
		
		return $result->row()->id;
	}
	
	
	function getCustomerMessage($tillModel_id) {
		
		$this->db->select('message');
		$this->db->where('tillModel_Id', $tillModel_id);
		$this->db->where('isDefault', '1');
		$this->db->from('template');
		$query = $this->db->get();
		
		echo $this->db->last_query();
		
		return $query->row()->message;
	}
	
	
	function getCustomMerchantMessages() {
		
		$this->db->select('*');
		$this->db->from('Customers');
		
		$query = $this->db->get();
	}
}