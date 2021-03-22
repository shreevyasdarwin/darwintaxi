<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/API_Controller.php';

class Driver extends API_Controller {
	public function __construct() {
        error_reporting(0);
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model("api_model");
    }

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	
	public function auth($args=null)
    {
        // API Configuration [Return Array: User Token Data]
        $token_data = $this->_apiConfig([
            'methods' => ['POST'],
            'requireAuthorization' => true,
        ]);
        if($args == 'contact')
            return $token_data['token_data']['contact'];
        else
            return $token_data['token_data'];
    }

    public function checkID($id)
    {
        if($id == null || $id == '')
        {
            $this->api_return(['status' => 'error','code' => '102',"result" => 'ID Parameter Required',],200);exit;
        }
    } 
	 
	// driver login
	public function login()
	{
		header("Access-Control-Allow-Origin: *");
        // API Configuration
        $this->_apiConfig([
            'methods' => ['POST'],
        ]);
		$phone = $this->input->post('phone');
		if(!$phone){
			echo json_encode([
				"status" => "1",
				"message" => "fields not provided"
			]);exit;
		}
		if(strlen($phone) != 10){
			echo json_encode([
				"status" => "1",
				"message" => "Invalid mobile number"
			]);exit;
		}
		$otp = rand(111111,999999);
		$this->db->select("id, phone");
		$this->db->from("user_register");
		$this->db->where('phone', $phone);
		$query = $this->db->get()->result_array();
		if(count($query) == 1){
			$msg2 = "".$otp."%20is%20your%20code%20and%20is%20valid%20only%20for%205%20min.%20Do%20not%20share%20the%20OTP%20with%20anyone"; 
			$response2=send_otp($phone, $msg2);
			$str1=explode('|',$response2);
            $str= str_replace(' ','',$str1[0]);
			if($str=='success'){
                // response
				echo json_encode([
					"token" => encrypt($query[0]['id']),
					"OTP" => $otp,
					"data" => $query[0],
					"message" => "Welcome back"
				]);
            }else{
				echo json_encode([
					"status" => "0",
					"message" => "Server error"
				]);exit;
			}
		}else{
			echo json_encode([
				"status" => '1',
				"message" => "Account does not exist"
			]);exit;
		}
	}

	// 
	public function get_driver_detail($id)
	{
		// echo $id;exit;
		if(!$id){
			echo json_encode([
				"status" => "0",
				"message" => "Invalid parameter"
			]);exit;
		}
		$this->db->select('fullname, phone, photo_path, type, brand, model, color, noplate');
		$this->db->from('driver_register');
		$this->db->join('driver_vehicle', 'driver_register.id = driver_vehicle.driver_id', 'inner');
		$this->db->join('vehicle_list', 'vehicle_list.id = driver_vehicle.vehicle_id', 'inner');
		$this->db->where('driver_register.id', $id);
		$query = $this->db->get()->result_array();
		if(count($query) == 1){
			echo json_encode([
				"status" => "1",
				"data" => $query[0]
			]);
		} else {
			echo json_encode([
				"status" => "0",
				"message" => "No data found"
			]);exit;
		}
	}
}
