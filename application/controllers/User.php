<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller {

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

	public function index()
	{
		echo 'hello';
		// $this->load->view('welcome_message');
    }
    
    public function login($phone)
    {
    if($phone!='' && (strlen($phone)==10)) {
        $otp = rand(111111, 999999);
        $check = $this->db->where('phone',$phone)->get('user_register')->result_array();
        if($check){
            // if user already exist

            $curl = curl_init();
            $msg2 = "".$otp."%20is%20your%20code%20and%20is%20valid%20only%20for%205%20min.%20Do%20not%20share%20the%20OTP%20with%20anyone";
            $new = str_replace(' ', '%20', $msg2);
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://enterprise.smsgupshup.com/GatewayAPI/rest?method=sendMessage&msg=".$new."&send_to=".$phone."&msg_type=Text&userid=2000190745&auth_scheme=Plain&password=jdHq2QoSg&v=1.1&format=TEXT",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS =>"",
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: text/plain"
                ),
            ));

            $response2 = curl_exec($curl);

            curl_close($curl);
            $str1=explode('|',$response2);
            $str= str_replace(' ','',$str1[0]);
            if($str=='success'){
                // response
                $response['token'] = encrypt($check[0]['id']);
                $response['otp'] = $otp;
                $response['data']=$check[0];
                $response['message']='welcome back';
            }
            else{
                $response['status']=0;
                $response['message']='Could not send OTP, please try later';
            }

            
        }
        else{
           
            // if new user
            $app_version = '2.0';
            $device_name = $_GET['device_name'];
            $device_model = $_GET['device_model'];
            $device_type = $_GET['device_type'];
            $sub_exp_date = date('Y-m-d', strtotime('+3 months'));
            $data = array(
                'phone' => $phone,
                'device_type'  => $device_type,
                'created_date'  => date('Y-m-d'),
                'updated_date'  => date('Y-m-d'),
                'app_version'  => $app_version,
                'device_name'  => $device_name,
                'device_model' => $device_model,
                'wallet' => 0,
                'status' => 1
            );
            $this->db->insert('user_register', $data);
            $id = $this->db->insert_id();
            if(isset($id)){
                $curl = curl_init();
                $msg2 = "".$otp."%20is%20your%20code%20and%20is%20valid%20only%20for%205%20min.%20Do%20not%20share%20the%20OTP%20with%20anyone";
                $new = str_replace(' ', '%20', $msg2);
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://enterprise.smsgupshup.com/GatewayAPI/rest?method=sendMessage&msg=".$new."&send_to=".$phone."&msg_type=Text&userid=2000190745&auth_scheme=Plain&password=jdHq2QoSg&v=1.1&format=TEXT",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_POSTFIELDS =>"",
                    CURLOPT_HTTPHEADER => array(
                        "Content-Type: text/plain"
                    ),
                ));
                
                $response2 = curl_exec($curl);

                curl_close($curl);
                
                $str1=explode('|',$response2);
                $str= str_replace(' ','',$str1[0]);
                if($str=='success'){
                    // update wallet with reward 
                    
                    $ip =  $_SERVER['REMOTE_ADDR'];
                    // $referal = check_referal($con,$ip,$id);
                    // // echo $referal;
                    // // exit;
                    // if($referal == 0)
                    //     $referralmsg = 'No Reward Found';
                    // else
                    //     $referralmsg = 'Reward Credited';
                    // response
                    $check = $this->db->get_where('user_register', array('id' => $id))->result_array();
                    $response['token'] = encrypt($check[0]['id']);
                    $response['otp'] = $otp;
                    $response['data']=$check[0];
                    $response['message']='welcome';
                    // $response['referralmsg'] = '$referralmsg';
                    $response['referralmsg'] = 'no';
                }
                else{
                    $response['status']=0;
                    $response['message']='Couldnt send OTP, please try later';
                }
            }
        }
    }
    if(empty($response)){
        $response['status']=0;
    }
    echo json_encode($response);
    }
}
