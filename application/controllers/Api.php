<?php defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/API_Controller.php';

class Api extends API_Controller
{
    public function __construct() {
        error_reporting(0);
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model("api_model");
    }
    /**
     * API Limit
     * 
     * @link : api/v1/limit
     */
    public function api_limit()
    {
        /**
         * API Limit
         * ----------------------------------
         * @param: {int} API limit Number
         * @param: {string} API limit Type (IP)
         * @param: {int} API limit Time [minute]
         */

        $this->_APIConfig([
            'methods' => ['POST'],

            /**
             * Number limit, type limit, time limit (last minute)
             */
            'limit' => [15, 'ip', 'everyday']
        ]);
    }

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

// ******************************************Register User with API********************************************


    public function check()
    {
        $this->api_return(['status' => 'error','code' => '102',"result" => 'ID Parameter Required',],200);exit;
    }

    public function register_user()
    {
        header("Access-Control-Allow-Origin: *");

        // API Configuration
        $this->_apiConfig([
            'methods' => ['POST'],
        ]);

        $this->form_validation->set_rules('name', 'Full Name', 'trim|required|min_length[4]');
        $this->form_validation->set_rules('email', 'Email Address', 'trim|valid_email|is_unique[participant.email]',array('is_unique' => 'This %s already exists.'));
        $this->form_validation->set_rules('contact', 'Contact Number', 'required|regex_match[/^[0-9]{10}$/]|is_unique[participant.contact]',array('required' => 'You have not provided %s.','is_unique' => 'This %s already exists.'));
        $this->form_validation->set_rules('city', 'City', 'trim|required');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]|max_length[15]');
        $this->form_validation->set_rules('con_password', 'Confirmation Password', 'trim|required|matches[password]');
        $this->form_validation->set_error_delimiters('','');
        if($this->form_validation->run() == FALSE) {
            $errors = explode ("\n", validation_errors());
            $this->api_return(['status' => 'error','code' => '102',"result" => $errors,],200);
        }else
        {
            $data['name'] = htmlspecialchars($this->input->post('name'));
            $data['email'] = htmlspecialchars($this->input->post('email'));
            $data['contact'] = htmlspecialchars($this->input->post('contact'));
            $data['city'] = htmlspecialchars($this->input->post('city'));
            $data['password'] = md5($this->input->post('password'));

            // Contact Verify Not Required On Registration
            $data['contact_verify'] = '1';
            if($this->db->insert('participant', $data))
            {
                $api_res['message'] = 'Successfully Registered';
                $this->api_return(['status' => 'success','code' => '101',"result" => $api_res,],200);
            }else{
                $api_res['message'] = 'Somthing went wrong, please try again!';
                $this->api_return(['status' => 'error','code' => '102',"result" => $api_res,],200);
            }
        }
    }

    public function login($para='')
    {
        header("Access-Control-Allow-Origin: *");

        // API Configuration
        $this->_apiConfig([
            'methods' => ['POST'],
        ]);
        $contact = $this->input->post('contact');
        $password = $this->input->post('password');
        $otp = $this->input->post('otp');
        if($password != ''){
            $user_id = $this->api_model->validate_user_credentials($contact, hash('md5',$password));
        }else{
            $user_id = $this->api_model->validate_user_otp($contact, $otp);
        }
        if($user_id['status'] == 1){
                $payload = [
                  'contact' => $user_id['contact'],
                ];
        }
        elseif($user_id['status'] == 2){
                $sms['contact'] = $user_id['contact'];
                $sms['participant_id'] = $this->api_model->get_value_by_id('participant','id','contact',$user_id['contact']);
                $this->send_otp($sms);
                $api_res['message'] = 'Contact not verified, OTP send successfully please verify your contact';
                $api_res['participant_id'] = $sms['participant_id'];
                $this->api_return(['status' => 'error','code' => '102',"result" => $api_res,],200);exit;
        }
        elseif($user_id['status'] == 3){
                $api_res['message'] = 'Incorrect OTP';
                $this->api_return(['status' => 'error','code' => '102',"result" => $api_res,],200);exit;
        }
        elseif($user_id['status'] == 4){
                $api_res['message'] = 'Mobile number not registered';
                $this->api_return(['status' => 'error','code' => '102',"result" => $api_res,],200);exit;
        }
        else{
            $api_res['message'] = 'Invalid contact or password';
            $this->api_return(['status' => 'error','code' => '102',"result" => $api_res,],200);exit;
        }
        // Load Authorization Library or Load in autoload config file
        $this->load->library('Authorization_Token');

        // generte a token
        $token = $this->authorization_token->generateToken($payload);

        // return data
        $this->api_return(
            [
                'status' => 'success',
                'code' => '101',
                "result" => [
                    'contact' =>  $user_id['contact'],
                    'token' => $token
                ],
            ],
        200);
    }

    public function send_otp($sms){

        $otp = mt_rand(100000, 999999);
        $to = $sms['contact'];
        $data['participant_id'] = $sms['participant_id'];
        $data['otp'] = $otp;
        $this->db->delete('verify_otp', array('participant_id' => $data['participant_id']));
        if($this->db->insert('verify_otp', $data))
        {
/*            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => "https://api.msg91.com/api/v5/otp?authkey=313606AduogjJd8s5e217f97P1&template_id=5f48cec9d6fc0560517b6ff2&mobile=91".$to."&invisible=1&otp=".$otp,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "GET",
              CURLOPT_SSL_VERIFYHOST => 0,
              CURLOPT_SSL_VERIFYPEER => 0,
              CURLOPT_HTTPHEADER => array(
                "content-type: application/json"
              ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $response = json_decode($response,true);

            curl_close($curl);

            if ($err) {
                // print_r($err);exit;
              echo "cURL Error #:" . $err;
            } else {
              if ($response['type'] == 'success') {
                return TRUE;
              }else{
                return FALSE;
              }
            }*/
            return TRUE;
        }
    }

    public function send_login_otp()
    {
        header("Access-Control-Allow-Origin: *");

        // API Configuration
        $this->_apiConfig([
            'methods' => ['POST'],
        ]);
        $contact = $this->input->post('contact');
        $this->db->limit(1);
        $res = $this->db->get_where('participant',array('contact' => $contact))->result_array();
        if(count($res) == 1)
        {
            // $otp = mt_rand(100000, 999999);
            $otp = 999999;
            $to = $contact;
            $data['contact'] = $contact;
            $sms='';
            $data['otp'] = $otp;
            $this->db->delete('login_otp', array('contact' => $contact));
            if($this->db->insert('login_otp', $data))
            {
/*                  $curl = curl_init();
                curl_setopt_array($curl, array(
                  CURLOPT_URL => "https://api.msg91.com/api/v5/otp?authkey=313606AduogjJd8s5e217f97P1&template_id=5f48cec9d6fc0560517b6ff2&mobile=91".$to."&invisible=1&otp=".$otp,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 30,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => "GET",
                  CURLOPT_SSL_VERIFYHOST => 0,
                  CURLOPT_SSL_VERIFYPEER => 0,
                  CURLOPT_HTTPHEADER => array(
                    "content-type: application/json"
                  ),
                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);
                $response = json_decode($response,true);

                curl_close($curl);

                if ($err) {
                    // print_r($err);exit;
                  echo "cURL Error #:" . $err;
                } else {
                  if ($response['type'] == 'success') {
                    return TRUE;
                  }else{
                    return FALSE;
                  }
                }*/

                // echo '0~Failed to send OTP, Try again';
                $api_res['message'] = 'OTP sent successfully';
                $this->api_return(['status' => 'success','code' => '101',"result" => $api_res,],200);
            }
        }else{
            $api_res['message'] = 'Mobile number not registered';
            $this->api_return(['status' => 'error','code' => '102',"result" => $api_res,],200);
        }
    }

    public function verify_otp()
    {
        header("Access-Control-Allow-Origin: *");

        // API Configuration
        $this->_apiConfig([
            'methods' => ['POST'],
        ]);
        $this->form_validation->set_rules('otp', 'OTP', 'trim|required');
        if($this->form_validation->run() == FALSE) {
            $api_res['message'] = 'OTP Required';
            $this->api_return(['status' => 'error','code' => '102',"result" => $api_res,],200);
        }else
        {
            $data['otp'] = $this->input->post('otp');
            $data['participant_id'] = $this->input->post('participant_id');
            $result = $this->api_model->verify_otp($data);
            if ($result != FALSE) {
                $this->db->where('id', $data['participant_id']);
                $this->db->update('participant', array('contact_verify' => '1'));
                $contact = $this->api_model->get_value_by_id('participant','contact','id',$data['participant_id']);
                $payload = [
                  'contact' => $contact,
                ];
                // Load Authorization Library or Load in autoload config file
                $this->load->library('Authorization_Token');

                // generte a token
                $token = $this->authorization_token->generateToken($payload);

                // return data
                $this->api_return(
                    [
                        'status' => 'success',
                        'code' => '101',
                        "result" => [
                            'contact' =>  $contact,
                            'token' => $token
                        ],
                    ],
                200);

            }else{
                $api_res['message'] = 'Incorrect OTP';
                $this->api_return(['status' => 'error','code' => '102',"result" => $api_res,],200);
            }
        }
    }

    public function profile()
    {
        header("Access-Control-Allow-Origin: *");
        $this->auth();
        $data['leaderboard'] = $this->api_model->get_leaderboard();
        $key = array_search($this->auth('contact'), array_column($data['leaderboard'], 'contact'));
        $data['my_profile'] = $data['leaderboard'][$key];
        $data['my_profile']['rank'] = $key+1;
        $data['point_rate'] = $this->api_model->get_value_by_id('settings','value','name','point');
        unset($data['leaderboard']);
        unset($data['my_profile']['password']);

        $this->api_return(['status' => 'success','code' => '101',"result" => $data,],200);
    }

}