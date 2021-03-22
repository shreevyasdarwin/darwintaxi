<?php defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/API_Controller.php';

class User extends API_Controller
{
    public function __construct() {
        error_reporting(0);
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model("api_model");
    }

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

// ******************************************Register User with API********************************************

    public function login()
    {
        header("Access-Control-Allow-Origin: *");
        // API Configuration
        $this->_apiConfig([
            'methods' => ['POST'],
        ]);
        $phone = $this->input->post('phone');

        $this->form_validation->set_rules('phone', 'Phone Number', 'trim|required|regex_match[/^[0-9]{10}$/]');
        $this->form_validation->set_error_delimiters('','');
        
        // Load Authorization Library or Load in autoload config file
        $this->load->library('Authorization_Token');
        // generte a token
        $payload = [
          'phone' => $phone,
        ];
        $token = $this->authorization_token->generateToken($payload);
        // return data

        if($this->form_validation->run() == FALSE) {
            $errors = explode ("\n", validation_errors());
            $this->api_return(['status' => FALSE,'message' => $errors],200);
        }else
        {
            $otp = rand(111111, 999999);
            $check = $this->db->where('phone',$phone)->get('user_register')->result_array();
            if($check){
                // if user already exist
                $msg = "".$otp."%20is%20your%20code%20and%20is%20valid%20only%20for%205%20min.%20Do%20not%20share%20the%20OTP%20with%20anyone";
                if(send_sms($phone, $msg)){
                    $this->api_return(
                        [
                            'status' => TRUE,
                            'message' => 'Welcome Back',
                            "result" => [
                                'otp' =>  $otp,
                                'token' => $token
                            ],
                        ],200);
                }
                else{
                    $this->api_return(['status' => FALSE,'message' => 'Could not send OTP, please try later'],200);exit;
                }
                
            }
            else{
                // if new user
                $this->form_validation->set_rules('device_name', 'Device Name', 'trim|required');
                $this->form_validation->set_rules('device_type', 'Device Type', 'trim|required');
                $this->form_validation->set_error_delimiters('','');
                if($this->form_validation->run() == FALSE) {
                    $errors = explode ("\n", validation_errors());
                    $this->api_return(['status' => FALSE,'message' => $errors],200);exit;
                }

                $app_version = '2.0';
                $device_name = $this->input->post('device_name');
                $device_type = $this->input->post('device_type');
                $device_model = $this->input->post('device_type');
                $sub_exp_date = date('Y-m-d', strtotime('+3 months'));
                $data = array(
                    'phone' => $phone,
                    'device_type'  => $device_type,
                    'created_date'  => date('Y-m-d H:i:s'),
                    'app_version'  => $app_version,
                    'device_name'  => $device_name,
                    'device_model' => $device_model,
                    'wallet' => 0,
                    'status' => 1
                );
                if($this->db->insert('user_register', $data)){
                    $id = $this->db->insert_id();
                    $msg = "".$otp."%20is%20your%20code%20and%20is%20valid%20only%20for%205%20min.%20Do%20not%20share%20the%20OTP%20with%20anyone";
                    if(send_sms($phone, $msg)){
                        $check = $this->db->get_where('user_register', array('id' => $id))->result_array();
                        $response['token'] = $token;
                        $response['otp'] = $otp;
                        $response['data']=$check[0];
                        $response['referralmsg'] = 'no';
                        $this->api_return(['status' => TRUE,'message' => 'Welcome',"result" => $response],200);
                    }
                    else{
                        $this->api_return(['status' => FALSE,'message' => 'Could not send OTP, please try later'],200);exit;
                    }
                }
            }
        }
    }

    public function update_profile()
    {
        header("Access-Control-Allow-Origin: *");
        $phone = $this->auth('phone',['POST'],true);

        $this->form_validation->set_rules('fullname', 'Full Name', 'trim|required');
        $this->form_validation->set_rules('email', 'Email', 'trim|required');
        $this->form_validation->set_rules('sos', 'SOS', 'trim|required');
        $this->form_validation->set_rules('photo_path', 'Photo Link', 'trim|required');
        $this->form_validation->set_error_delimiters('','');

        if($this->form_validation->run() == FALSE) {
            $errors = explode ("\n", validation_errors());
            $this->api_return(['status' => FALSE,'message' => $errors],200);exit;
        }
        $data['fullname']   = $this->input->post('fullname');
        $data['email']      = $this->input->post('email');
        $data['sos']        = $this->input->post('sos');
        $data['photo_path'] = $this->input->post('photo_path');

        $this->db->where('phone', $phone);
        if($this->db->update('user_register', $data))
        {
            $this->api_return(['status' => TRUE,'message' => 'Profile updated successfully'],200);
        }else{
            $this->api_return(['status' => TRUE,'message' => 'Failed to update'],200);
        }
    }

    public function vehicle_list()
    {
        header("Access-Control-Allow-Origin: *");
        $phone = $this->auth('phone',['GET'],true);

      $user_lat= $this->input->get('user_lat');
      $user_lon= $this->input->get('user_lon');
      $destination_lat= $this->input->get('dest_lat');
      $destination_long= $this->input->get('dest_lon');
      $destination_lat2= $this->input->get('dest_lat2');
      $destination_long2= $this->input->get('dest_lat2');
      
      $area=check_range($user_lat,$user_lon,$destination_lat,$destination_long);
      if($area==1){
        // two stops
        if(($user_lat!='' && $user_lon!='') && ($destination_lat!='' && $destination_long!='') &&  ($destination_lat2!='' && $destination_long2!='')){
            
            // flow starts from here
            $sql=$this->db->query("select image,id,type from vehicle_list where status='1'")->result_array();
            if($sql){
              $response['status'] = TRUE; // if successful
              $response['message']="Vehicle list";
              $i=0;
              foreach ($sql as $key => $row) {
                $response['result'][$i]['image']=$row['image'];
                $response['result'][$i]['vehicle_id']=$row['id'];
                $response['result'][$i]['name']=$row['type'];
                $ride_distance1=intval(distance_calculation($user_lat,$user_lon,$destination_lat,$destination_long));
                $ride_distance2=intval(distance_calculation($destination_lat,$destination_long,$destination_lat2,$destination_long2));
                $ride_distance=$ride_distance1+$ride_distance2;
                $response['result'][$i]['distance']=$ride_distance;
                $toll=check_for_toll($user_lat,$user_lon,$destination_lat,$destination_long);
                $toll1=check_for_toll($destination_lat,$destination_long,$destination_lat2,$destination_long2);
                if($toll1==1 && $toll==1){
                  $toll=70;
                }
                elseif($toll1==1 || $toll==1){
                  $toll=35;
                }
                else{
                  $toll=0;
                }
                
                $response['result'][$i]['arrival']=get_arrival_time($user_lat,$user_lon,$con,$row['id']);
                $response['result'][$i]['duration']=strval(get_journey_time($user_lat,$user_lon,$destination_lat,$destination_long)+get_journey_time($destination_lat,$destination_long,$destination_lat2,$destination_long2));
                $demand= get_area_demand($user_lat,$user_lon,$con);
                $response['result'][$i]['demand']=$demand;
                $response['result'][$i]['price']=json_decode(fare_calculator($con,$ride_distance,$row['id'],$toll,$response['result'][$i]['duration'],$demand));
                $i++;
              }
            }
        }
        // one stop
        elseif ($user_lat!='' && $user_lon!='' && $destination_lat!='' && $destination_long!=''){
          // flow starts from here
            $sql=$this->db->query("select image,id,type from vehicle_list where status='1'")->result_array();
          if($sql){
            $response['status'] = TRUE; // if successful
            $response['message']="Vehicle list";
            $i=0;
            foreach ($sql as $key => $row) {
              $response['result'][$i]['image']=$row['image'];
              $response['result'][$i]['vehicle_id']=$row['id'];
              $response['result'][$i]['name']=$row['type'];
              $ride_distance=intval(distance_calculation($user_lat,$user_lon,$destination_lat,$destination_long));
              $response['result'][$i]['distance']=$ride_distance;
        
              $toll=check_for_toll($user_lat,$user_lon,$destination_lat,$destination_long);
              if($toll==1){
                  $toll=35;
              }
              $response['result'][$i]['arrival']=get_arrival_time($user_lat,$user_lon,$con,$row['id']);
              $response['result'][$i]['duration']=get_journey_time($user_lat,$user_lon,$destination_lat,$destination_long);
              $demand= get_area_demand($user_lat,$user_lon,$con);
              $response['result'][$i]['demand']=$demand;
              $response['result'][$i]['price']=json_decode(fare_calculator($con,$ride_distance,$row['id'],$toll,$response['result'][$i]['duration'],$demand));
              $i++;
            }
          }
        }
        }
        else{
            $response['status']=FALSE;
            $response['message']="Service not available in your location";
        }
        $this->api_return($response,200);
    }

}