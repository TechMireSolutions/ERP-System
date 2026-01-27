<?php

class login_model extends CI_Model
{
	function __construct()
	{
		// Call the Model constructor
		parent::__construct();
	}

	// Read data using username and password
	public function login($data)
	{

		$this->db->select('*');
		$this->db->from('xin_employees');
		$this->db->where('username', $data['username']);
		$this->db->where('password', $data['password']);
		$this->db->where('is_active', '1');
		$this->db->limit(1);
		$query = $this->db->get();

		if ($query->num_rows() == 1) {
			return true;
		} else {
			return false;
		}
	}

	// Read data from database to show data in admin page
	public function read_user_information($username)
	{

		$this->db->select('*');
		$this->db->from('xin_employees');
		$this->db->where('username', $username);
		$this->db->limit(1);
		$query = $this->db->get();

		if ($query->num_rows() == 1) {
			return $query->result();
		} else {
			return false;
		}
	}

}
?>