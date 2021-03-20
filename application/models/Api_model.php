<?php
class Api_model extends CI_Model
{

	public function get_value_by_id($table_name,$column_need,$column_have,$column_value)
	{
		$this->db->select($column_need);
		$this->db->where($column_have,$column_value);
		$result=$this->db->get($table_name)->result_array();
		if(count($result) != 0)
			return $result[0][$column_need];
		else
			return null;
	}
}
?>
