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
				"status" => true,
				"message" => "fields not provided"
			]);exit;
		}
		if(strlen($phone) != 10){
			echo json_encode([
				"status" => true,
				"message" => "Invalid mobile number"
			]);exit;
		}
		$data = $this->db->select('status')->from('user_register')->where('phone', $phone)->get()->result_array();
		if($data[0]['status']!='1'){
			echo json_encode([
				"status" => false,
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
				$this->db->set('active', 'login')->where('phone', $phone)->update('driver_register');
				echo json_encode([
					"token" => encrypt($query[0]['id']),
					"OTP" => $otp,
					"data" => $query[0],
					"message" => "Welcome back"
				]);
            }else{
				echo json_encode([
					"status" => false,
					"message" => "Server error"
				]);exit;
			}
		}else{
			echo json_encode([
				"status" => false,
				"message" => "Account does not exist"
			]);exit;
		}
	}

	//get driver details 
	public function get_driver_detail($id)
	{
		if(!$id){
			echo json_encode([
				"status" => false,
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
				"status" => true,
				"data" => $query[0]
			]);
		} else {
			echo json_encode([
				"status" => false,
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
					"status" => true,
					"data" => $data
				]);
			}
		}else{
			echo json_encode([
				"status" => false,
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
				"status" => false,
				"message" => "Invalid parameter"
			]);exit;
		}
		if(!$booking_type){
			echo json_encode([
				"status" => false,
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
						"status" => true,
						"data" => $data
					]);
				}
			}else{
				echo json_encode([
					"status" => false,
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
						"status" => true,
						"data" => $row
					]);
				}
			}else{
				echo json_encode([
					"status" => false,
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
						"status" => true,
						"data" => $row
					]);
				}
			}else{
				echo json_encode([
					"status" => false,
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
			if(count($query) > 0){
				foreach($query as $row){
					echo json_encode([
						"status" => true,
						"data" => $row
					]);
				}
			}else{
				echo json_encode([
					"status" => false,
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
			if(count($query) > 0){
				foreach($query as $row){
					echo json_encode([
						"status" => true,
						"data" => $row
					]);
				}
			}else{
				echo json_encode([
					"status" => false,
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
						"status" => true,
						"data" => $row
					]);
				}
			}else{
				echo json_encode([
					"status" => false,
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
						"status" => true,
						"data" => $row
					]);
				}
			}else{
				echo json_encode([
					"status" => false,
					"message" => "No data found"
				]);exit;
			}
		}
		if($booking_type=='count_dashboard'){
			$new = $this->db->select('COUNT(ride_status) as new')->from('booking_details')->where('ride_status', 'new')->where('driver_id', $id)->get()->result_array(); 
			$data['new'] = $new[0]['new'];
			$confirm = $this->db->select('COUNT(ride_status) as confirm')->from('booking_details')->where('ride_status', 'confirm')->where('driver_id', $id)->get()->result_array();
			$data['confirm'] = $confirm[0]['confirm'];
			$arrived = $this->db->select('COUNT(ride_status) as arrived')->from('booking_details')->where('ride_status', 'arrived')->where('driver_id', $id)->get()->result_array();
			$data['arrived'] = $arrived[0]['arrived'];
			$onride = $this->db->select('COUNT(ride_status) as onride')->from('booking_details')->where('ride_status', 'onride')->where('driver_id', $id)->get()->result_array();
			$data['onride'] = $onride[0]['onride'];
			$completed = $this->db->select('COUNT(ride_status) as completed')->from('booking_details')->where('ride_status', 'completed')->where('driver_id', $id)->get()->result_array();
			$data['completed'] = $completed[0]['completed'];
			$cancel = $this->db->select('COUNT(ride_status) as cancel')->from('booking_details')->where('ride_status', 'cancel')->where('driver_id', $id)->get()->result_array();
			$data['cancel'] = $cancel[0]['cancel'];
			echo json_encode([
				"status" => true,
				"data" => $data
			]);
		}
	}

	// purchase subscription
	public function purchase_sub()
	{
		$id = $this->input->post('id');
		$sub_type = $this->input->post('sub_type');
		if(!$id || !$sub_type){
			echo json_encode([
				"status" => false,
				"message" => "Fields not provided"
			]);exit;
		}
		$date = $this->db->select('sub_exp_date')->from('driver_register')->where('id', $id)->get()->result_array();
		if($sub_type=='1'){
			$d = strtotime("+1 month",strtotime($date[0]['sub_exp_date']));
			$sub_exp_date = date("Y-m-d",$d);
			$sql = $this->db->set('sub_exp_date', $sub_exp_date)->where('id', $id)->update('driver_register');
			if($sql){
				echo json_encode([
					"status" => true,
					"message" => "success"
				]);
			}else{
				echo json_encode([
					"status" => false,
					"message" => $this->db->_error_message()
				]);exit;
			}
		}
		if($sub_type=='3'){
			$d = strtotime("+3 month",strtotime($date[0]['sub_exp_date']));
			$sub_exp_date = date("Y-m-d",$d);
			$sql = $this->db->set('sub_exp_date', $sub_exp_date)->where('id', $id)->update('driver_register');
			if($sql){
				echo json_encode([
					"status" => true,
					"message" => "success"
				]);
			}else{
				echo json_encode([
					"status" => false,
					"message" => $this->db->_error_message()
				]);exit;
			}
		}
		if($sub_type=='6'){
			$d = strtotime("+6 month",strtotime($date[0]['sub_exp_date']));
			$sub_exp_date = date("Y-m-d",$d);
			$sql = $this->db->set('sub_exp_date', $sub_exp_date)->where('id', $id)->update('driver_register');
			if($sql){
				echo json_encode([
					"status" => true,
					"message" => "success"
				]);
			}else{
				echo json_encode([
					"status" => false,
					"message" => $this->db->_error_message()
				]);exit;
			}
		}
		if($sub_type=='12'){
			$d = strtotime("+12 month",strtotime($date[0]['sub_exp_date']));
			$sub_exp_date = date("Y-m-d",$d);
			$sql = $this->db->set('sub_exp_date', $sub_exp_date)->where('id', $id)->update('driver_register');
			if($sql){
				echo json_encode([
					"status" => true,
					"message" => "success"
				]);
			}else{
				echo json_encode([
					"status" => false,
					"message" => $this->db->_error_message()
				]);exit;
			}
		}
	}

	// service list
	public function service_list()
	{
		$data = $this->db->select('*')->from('service_city')->order_by('id', 'desc')->get()->result_array();
		if(count($data) > 0){
			foreach ($data as $row) {
				echo json_encode([
					"status" => true,
					"data" => $row
				]);
			}
		}else{
			echo json_encode([
				"status" => false,
				"message" => "No data found"
			]);
		}	
	}

	// report user
	public function report_user() 
	{
		$driver_id = $this->input->post('reporter_id');
		$user_id = $this->input->post('reported_id');
		$desc = $this->input->post('description');
		$query = $this->db->select('id')->from('ud_report')->where('reporter', '2')->where('reporter', $driver_id)->where('reported_id', $user_id)->get()->result_array();
		if($query[0] != 0){
			echo json_encode([
				"status" => false,
				"message" => "Already reported to this driver!"
			]);exit;
		}
		if(!$driver_id || !$user_id || !$desc){
			echo json_encode([
				"status" => false,
				"message" => "fields not provided"
			]);exit;
		}
		$data = array(
			"reporter" => "2",
			"reporter_id" => $driver_id,
			"reported_id" => $user_id,
			"description" => $desc
		);
		$this->db->insert('ud_report', $data);
		if($this->db->affected_rows() > 0){
			echo json_encode([
				"status" => true,
				"message" => "Successfully reported"
			]);
		}else{
			echo json_encode([
				"status" => false,
				"message" => "Server error"
			]);
		}
	}

	// subscription
	public function subscription() 
	{
		$id = $this->input->post('id');
		if(!$id){
			echo json_encode([
				"status" => false,
				"message" => "invalid parameter"
			]);exit;
		}
		$sql = $this->db->select('*')->from('subscription')->where('type', 'Driver')->get()->result_array();
		$sql2 = $this->db->select('sub_type,sub_exp_date')->from('driver_register')->where('id', $id)->get()->result_array();
		foreach($sql as $row){
				$response["sub_type"] = $sql2[0]['sub_type'];
				$response["sub_exp_date"] = $sql2[0]['sub_exp_date'];
				$response["status"] = true;
				$response["data"][] = $row;
		}
		echo json_encode($response);
	}

	// update driver status
	public function update_driver_status()
	{
		$id = $this->input->post('id');
		$type = $this->input->post('type');
		$value = $this->input->post('value');
		if(!$id || !$type || !$value){
			echo json_encode([
				"status" => false,
				"message" => "fields not provided"
			]);exit;
		}
		if($type=='online'){
			$msg = $value == 'yes' ? 'Online':'Offline';
			$sql = $this->db->set('online', $value)->where('id', $id)->update('driver_register');
			if($sql){
				echo json_encode([
					"status" => true,
					"message" => "You are ".$msg." now"
				]);
			}else{
				echo json_encode([
					"status" => false,
					"message" => "Server error"
				]);exit;
			}
		}
		if($type=='gohome'){
			$msg = $value == '1' ? 'activated':'deactivated';
			$sql = $this->db->set('go_home', $value)->set('go_home_last_time', date('Y-m-d H:i:s'))->where('id', $id)->update('driver_register');
			if($sql){
				echo json_encode([
					"status" => true,
					"message" => "Go home ".$msg." successfully"
				]);
			}else{
				echo json_encode([
					"status" => false,
					"message" => "Server error"
				]);exit;
			}
		}
	}

	// update location
	public function update_location()
	{
		$id = $this->input->post('id');
		$lat = $this->input->post('lat');
		$long = $this->input->post('long');	
		if(!$id || !$lat || !$long){
			echo json_encode([
				"status" => false,	
				"message" => "fields not provided"
			]);exit;
		}	
		$sql = $this->db->set('latitude', $lat)->set('longitude', $long)->where('id', $id)->update('driver_register');
		if($sql){
			echo json_encode([
				"status" => true,
				"message" => "location updated successfully"
			]);
		}else{
			echo json_encode([
				"status" => true,
				"message" => "Driver on same place"
			]);exit;
		}
	}

	// rental price
	public function rental_price()
	{
		$sql = $this->db->select('*')->from('rental_price')->get()->result_array();
		$i = 0;
		foreach($sql as $row){
			$response['status']=true;
			$response[$i]['id'] = $row['id'];
			$response[$i]['vehicle_id'] = $row['vehicle_id'];
			$response[$i]['km']['10'] = $row[10];
			$response[$i]['km']['20'] = $row[20];
			$response[$i]['km']['30'] = $row[30];
			$response[$i]['km']['40'] = $row[40];
			$response[$i]['km']['60'] = $row[60];
			$response[$i]['km']['80'] = $row[80];
			$response[$i]['km']['100'] = $row[100];
			$response[$i]['km']['120'] = $row[120];
			$i++;
		}
		if(empty($response)){
			$response['status']=false;
		}
		echo json_encode($response);
	}

	// fetch alert
	public function fetch_alert()
	{
		$sql = $this->db->select('*')->from('alert')->where('status', 1)->get()->result_array();
		if(count($sql) > 0){
			foreach ($sql as $row) {
				echo json_encode([
					"status" => true,
					"data" => $row
				]); 
			}
		}else{
			echo json_encode([
				"status" => false,
				"message" => "No data found"
			]);exit;
		}
	}

	// logout driver
	public function logout()
	{
		$phone = $this->input->post('phone');
		if(!$phone){
			echo json_encode([
				"status" => false,
				"message" => "fields not provided"
			]);exit;
		}
		$sql = $this->db->set('active', 'logout')->where('phone', $phone)->update('driver_register');
		if($sql){
			echo json_encode([
				"status" => true,
				"message" => "logout successful" 
			]);
		}else{
			echo json_encode([
				"status" => false,
				"message" => "server error" 
			]);exit;
		}
	}

	// driver transaction history
	public function driver_transaction()
	{
		$driver_id = $this->input->post('driver_id');
		if(!$driver_id){
			echo json_encode([
				"status" => false,
				"message" => "fields not provided"
			]);exit;
		}
		$sql = $this->db->select('*')->from ('driver_transaction')->where('driver_id', $driver_id)->order_by('id', 'desc')->get()->result_array();
		if(count($sql) > 0){
			foreach ($sql as $row) {
				echo json_encode([
					"status" => true,
					"data" => $row
				]);
			}
		}else{
			echo json_encode([
				"status" => false,
				"message" => "No data found"
			]);exit;
		}
	}

	// get wallet
	public function get_wallet()
	{
		$id = $this->input->post('id');
		if(!$id){
			echo json_encode([
				"status" => false,
				"message" => "fields not provided"
			]);exit;
		}
		$sql = $this->db->select('wallet')->from('driver_register')->where('id', $id)->get()->result_array();
		if(count($sql) > 0){
			echo json_encode([
				"status" => true,
				"data" => $sql[0]['wallet']
			]);
		}else{
			echo json_encode([
				"status" => true,
				"message" => "server error"
			]);exit;
		}
	}
}
