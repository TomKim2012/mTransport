<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
class Member_Model extends CI_Model {
	function __construct() {
		parent::__construct ();
	}
	function getSingleCustomer($parameter, $value) {
		$this->db->where ( array (
				$parameter => $value 
		) );
		$query = $this->db->get ( 'BUser' );
		
		// echo $this->db->last_query();
		// print_r($query->result());
		$fullNames = trim ( (isset ( $query->row ()->firstName )) ? ($query->row ()->firstName) : "N/a" ) . " " . trim ( (isset ( $query->row ()->lastName )) ? ($query->row ()->lastName) : "N/a" );
		
		$custData = array (
				'firstName' => trim ( (isset ( $query->row ()->firstName )) ? ($query->row ()->firstName) : "N/a" ),
				'lastName' => trim ( (isset ( $query->row ()->lastName )) ? ($query->row ()->lastName) : "N/a" ),
				'fullNames' => $fullNames,
				'mobileNo' => trim ( (isset ( $query->row ()->phone )) ? ($query->row ()->phone) : "N/a" ),
				'linkCode' => trim ( (isset ( $query->row ()->linkCode )) ? ($query->row ()->linkCode) : "N/a" ),
				'userId' => trim ( (isset ( $query->row ()->userId )) ? ($query->row ()->userId) : "" ) 
		);
		return $custData;
	}
	function getTills($ownerId) {
		$query = $this->db->query ( "select tillNo,businessName from tillModel where ownerId='" . $ownerId . "'" );
		
		$tillList = $query->result_array ();
		
		// echo $this->db->last_query();
		
		$businessNos = array ();
		foreach ( $tillList as $row ) {
			$data = array (
					'businessNo' => $row ['tillNo'],
					'businessName' => $row ['businessName'] 
			);
			array_push ( $businessNos, $data );
		}
		return $businessNos;
	}
	function getOwner_by_id($businessNo) {
		$query = $this->db->query ( "select businessName,phoneNo from TillModel" . " where business_number='" . $businessNo . "'" );
		if ($query->num_rows () > 0) {
			return $query->row_array ();
		} else {
			return false;
		}
	}
	/*
	 * Total for a single Till
	 */
	function getTillTotal($businessNo) {
		$this->db->query ( 'Use mobileBanking' );

		$query = "select SUM(mpesa_amt) as mpesa_amt from LipaNaMpesaIPN 
					where DAY(tstamp)=".date('d')." and month(tstamp)="
					.date('m')." and YEAR(tstamp)=".date('Y').
					" and business_number='".$businessNo."'";
		$query = $this->db->query ($query);
		
		$amount = $query->row ()->mpesa_amt;
		
		//echo $this->db->last_query();
		
		return number_format ( $amount );
	}
	function getTotals($businessNos) {
		$response = array ();
		$this->db->query ( 'Use mobileBanking' );
		
		foreach ( $businessNos as $row ) {
			$this->db->select_sum ( 'mpesa_amt' );
			$this->db->from ( 'LipaNaMpesaIPN' );
			$this->db->join ( 'TillModel', 'LipaNaMpesaIPN.business_number=TillModel.tillNo' );
			$this->db->where ( array (
					'business_number' => trim ( $row ['businessNo'] ),
					'mpesa_trx_date' => date ( "d/m/y" ) 
			) );
			$this->db->group_by ( "businessName" );
			
			$query = $this->db->get ();
			
			// echo $this->db->last_query ();
			$results = $query->row_array ();
			
			$data = array (
					'business_name' => $row ['businessName'],
					'totals' => $results ['mpesa_amt'],
					'count' => $query->num_rows () 
			);
			array_push ( $response, $data );
		}
		return $response;
	}
	function getTransactedMerchants() {
		
		// Daily
		$date = date ( "d/m/Y" );
		$explodedDate = explode ( '/', $date );
		$day = $explodedDate [0];
		$month = $explodedDate [1];
		$year = $explodedDate [2];
		
		$day = '02';
		$month = '01';
		
		$query = "select DISTINCT(transactions.business_number),tills.ownerId,users.phone,users.firstName, clientdoc.clientcode" . " from LipaNaMpesaIPN as transactions " . " Inner Join TillModel as tills ON (transactions.business_number=tills.business_number)" . " Inner Join BUser as users ON (tills.ownerId=users.userId)" . " Inner Join mergefinalss.dbo.clientdoc as clientdoc ON(tills.business_number=clientdoc.docnum)" . " where DATEPART(YYYY,tstamp)='" . $year . "' and" . " DATEPART(MM,tstamp)='" . $month . "' and " . " DATEPART(DD,tstamp)='" . $day . "'
				";
		
		//echo $query;
		
		$query = $this->db->query ( $query );
		$output = $query->result_array ();
		
		//print_r ( $output );
		
		return $output;
	}
	function getCustTransaction($customerId, $transactionId) {
		$this->db->query ( 'Use mergefinalss' );
		$rs = $this->db->query ( 'SELECT Dbo.SP_GetBalances(\'' . $customerId . '\',' . $transactionId . ') AS balance' );
		$balance = $rs->row ()->balance;
		return $balance;
	}
}