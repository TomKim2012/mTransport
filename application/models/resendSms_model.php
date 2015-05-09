<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
class resendSms_model extends CI_Model {
	/*
	 * gets a record of texts not delivered
	 */
	function resendSms_model() {
		parent::__construct();
		date_default_timezone_set ( 'Africa/Nairobi' );
		
	}
	function getFailed() {
		
		$this->db->select('SettingValue');
		$this->db->where ('id', 2);
		$this->db->from('SettingModel');
		$result = $this->db->get();		
		$maximumRetries = $result->row()->SettingValue;		
		
		
		$this->db->select ( '*' );
		$this->db->from ( 'smsModel' );
		$this->db->where ('status!=', 'Success' );
		$this->db->where('retries <', $maximumRetries);
			
		$query = $this->db->get();
		
//		echo $this->db->last_query();
		return $query->result();
	}
	
	/*
	 * updates the records after texts are resent
	 */
	function updateSMS($messageId, $status, $transactionId) {
		
		$this->db->select('retries');
		$this->db->where ( 'transactionId', $transactionId );
		$this->db->from ( 'smsModel' );
		$query = $this->db->get();
		
		
		$retries = $query->row()->retries;

		
			$data = array (
					'tstamp'=> date ( "Y-m-d G:i" ),
					'messageId' => $messageId,
					'status' => $status,
					'retries' => $retries+1
			);
			
			$this->db->where ( 'transactionId', $transactionId );
			$this->db->update ( 'smsModel', $data );			
		
	}
}