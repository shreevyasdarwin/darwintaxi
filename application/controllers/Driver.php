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
	
    public function auth($args=null,$method=['POST'],$auth=true)
    {
        // API Configuration [Return Array: User Token Data]
        $token_data = $this->_apiConfig([
            'methods' => $method,
            'requireAuthorization' => $auth,
            'limit' => [100, 'ip', 1],
            'key' => ['header']
        ]);
        if($args == 'phone')
            return $token_data['token_data']['phone'];
        else
            return $token_data['token_data'];
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
		$data = $this->db->select('status')->from('user_register')->where('phone', $phone)->get()->result_array();
		if($data[0]['status']!='1'){
			echo json_encode([
				"status" => '0',
				"message" => "Account is disabled"
			]);exit;
		}
		$otp = rand(111111,999999);
		$this->db->select("id, phone, status");
		$this->db->from("user_register");
		$this->db->where('phone', $phone);
		$this->db->where('status', '1');
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

	//get driver details 
	public function get_driver_detail($id)
	{
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

	// get vehicle list
	public function get_vehicle_list()
	{
		$this->db->select('type,image,id as vehicle_id');
		$this->db->from('vehicle_list');
		$this->db->where('status', 1);
		$query = $this->db->get()->result_array();
		if(count($query) > 1){
			foreach($query as $data){
				echo json_encode([
					"status" => "1",
					"data" => $data
				]);
			}
		}else{
			echo json_encode([
				"status" => '1',
				"message" => "No data found"
			]);exit;
		}
	}

	// get my bookings
	public function get_my_booking()
	{
		$id=$this->input->post('driver_id');
		$booking_type=$this->input->post('booking_type');
		if(!$id){
			echo json_encode([
				"status" => "0",
				"message" => "Invalid parameter"
			]);exit;
		}
		if(!$booking_type){
			echo json_encode([
				"status" => "0",
				"message" => "No booking found"
			]);exit;
		}
		if($booking_type=='new'){
			$this->db->select('*, fullname as user_name, phone as user_phone');
			$this->db->from('booking_details');
			$this->db->join('user_register', 'booking_details.driver_id = user_register.id', 'inner');
			$this->db->where('booking_details.ride_status', 'new');
			$this->db->where('booking_details.driver_id', $id);
			$this->db->where('booking_details.status', '1');
			$query = $this->db->get()->result_array();
			if(count($query) > 0){
				foreach($query as $data){
					echo json_encode([
						"status" => "1",
						"data" => $data
					]);
				}
			}else{
				echo json_encode([
					"status" => "0",
					"message" => "No booking found"
				]);exit;
			}
		}
		if($booking_type=='confirm'){
			$this->db->select('*, fullname as user_name, phone as user_phone');
			$this->db->from('booking_details');
			$this->db->join('user_register', 'booking_details.driver_id = user_register.id', 'inner');
			$this->db->where('booking_details.ride_status', 'confirm');
			$this->db->where('booking_details.driver_id', $id);
			$this->db->where('booking_details.status', '1');
			$query = $this->db->get()->result_array();
			if(count($query) > 0){
				foreach($query as $row){
					echo json_encode([
						"status" => "1",
						"data" => $row
					]);
				}
			}else{
				echo json_encode([
					"status" => "0",
					"message" => "No data found"
				]);exit;
			}
		}
		if($booking_type=='arrived'){
			$this->db->select('*, fullname as user_name, phone as user_phone');
			$this->db->from('booking_details');
			$this->db->join('user_register', 'booking_details.driver_id = user_register.id', 'inner');
			$this->db->where('booking_details.ride_status', 'arrived');
			$this->db->where('booking_details.driver_id', $id);
			$this->db->where('booking_details.status', '1');
			$query = $this->db->get()->result_array();
			if(count($query) > 0){
				foreach($query as $row){
					echo json_encode([
						"status" => "1",
						"data" => $row
					]);
				}
			}else{
				echo json_encode([
					"status" => "0",
					"message" => "No data found"
				]);exit;
			}
		}
		if($booking_type=='onride'){
			$this->db->select('*, fullname as user_name, phone as user_phone');
			$this->db->from('booking_details');
			$this->db->join('user_register', 'booking_details.driver_id = user_register.id', 'inner');
			$this->db->where('booking_details.ride_status', 'onride');
			$this->db->where('booking_details.driver_id', $id);
			$this->db->where('booking_details.status', '1');
			$query = $this->db->get()->result_array();
			// print_r($this->db->last_query());exit;
			if(count($query) > 0){
				foreach($query as $row){
					echo json_encode([
						"status" => "1",
						"data" => $row
					]);
				}
			}else{
				echo json_encode([
					"status" => "0",
					"message" => "No data found"
				]);exit;
			}
		}
		if($booking_type=='completed'){
			$this->db->select('*, fullname as user_name, phone as user_phone');
			$this->db->from('booking_details');
			$this->db->join('user_register', 'booking_details.driver_id = user_register.id', 'inner');
			$this->db->where('booking_details.ride_status', 'completed');
			$this->db->where('booking_details.driver_id', $id);
			$this->db->where('booking_details.status', '1');
			$query = $this->db->get()->result_array();
			// print_r($this->db->last_query());exit;
			if(count($query) > 0){
				foreach($query as $row){
					echo json_encode([
						"status" => "1",
						"data" => $row
					]);
				}
			}else{
				echo json_encode([
					"status" => "0",
					"message" => "No data found"
				]);exit;
			}
		}
		if($booking_type=='cancel'){
			$this->db->select('*, fullname as user_name, phone as user_phone');
			$this->db->from('booking_details');
			$this->db->join('user_register', 'booking_details.driver_id = user_register.id', 'inner');
			$this->db->where('booking_details.ride_status', 'cancel');
			$this->db->where('booking_details.driver_id', $id);
			$this->db->where('booking_details.status', '1');
			$query = $this->db->get()->result_array();
			// print_r($this->db->last_query());exit;
			if(count($query) > 0){
				foreach($query as $row){
					echo json_encode([
						"status" => "1",
						"data" => $row
					]);
				}
			}else{
				echo json_encode([
					"status" => "0",
					"message" => "No data found"
				]);exit;
			}
		}
		if($booking_type=='all'){
			$this->db->select('*, fullname as user_name, phone as user_phone');
			$this->db->from('booking_details');
			$this->db->join('user_register', 'booking_details.driver_id = user_register.id', 'inner');
			$this->db->where('booking_details.ride_status !=', 'new');
			$this->db->where('booking_details.driver_id', $id);
			$this->db->where('booking_details.status', '1');
			$query = $this->db->get()->result_array();
			if(count($query) > 0){
				foreach($query as $row){
					echo json_encode([
						"status" => "1",
						"data" => $row
					]);
				}
			}else{
				echo json_encode([
					"status" => "0",
					"message" => "No data found"
				]);exit;
			}
		}
		if($booking_type=='count_dashboard'){
			$new = $this->db->select('COUNT(ride_status) as new')->from('booking_details')->where('ride_status', 'new')->where('driver_id', $id)->get()->result_array();
			echo $this->db->last_query();exit;
		}
	}
}
