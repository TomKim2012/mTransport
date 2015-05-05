<?php
class resendSms_model extends CI_Model {
	function getFailed() {
		$this->db->select ( '*' );
		$this->db->from ( 'smsModel' );
		$this->db->where ('status!=', 'Success' );
		
		$query = $this->db->get();
		
		echo $this->db->last_query();
		return $query->result();
	}
	function updateSMS($messageId, $status, $transactionId) {
		$data = array (
				'messageId' => $messageId,
				'status' => $status
		);
		
		$this->db->where ( 'transactionId', $transactionId );
		$this->db->update ( 'smsModel', $data );
	}
}