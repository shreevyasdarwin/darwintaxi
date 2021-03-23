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
                // $sub_exp_date = date('Y-m-d', strtotime('+3 months'));
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
                    $curl = curl_init();
                    $msg2 = "".$otp."%20is%20your%20code%20and%20is%20valid%20only%20for%205%20min.%20Do%20not%20share%20the%20OTP%20with%20anyone";
                    $response2=send_otp($phone, $msg2);
                    curl_close($curl);
                    $str1=explode('|',$response2);
                    $str= str_replace(' ','',$str1[0]);
                    if($str=='success'){
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
}
        

    public function update_profile()
    {
        header("Access-Control-Allow-Origin: *");
        $data = $this->auth('phone',['POST'],true);
        $this->api_return(['status' => 'TRUE',"result" => $data,],200);
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

    public function wallet(){
        $phone=$this->input->post('phone');
        $this->auth(null,['POST'],TRUE);
        if(is_user($phone)==0){
            $response['status'] = 0;
            $response['message'] = "User Not Found";
            echo json_encode($response);exit;
        }
        $amount=$_GET['amount'];
        $payment_id=$_GET['payment_id'];
        if($phone!='' || $amount!=''){
            $sql=$this->db->set("wallet","wallet"+$amount)->where('phone',$phone)->update('user_register');
            if($sql){
                $data = array(
                    'phone' => $phone,
                    'amount' => $amount,
                    'transaction_id' => $payment_id,
                    'remark' => 'amount added in wallet',
                    'type' => 0,
                    'status' => 1,
                    'date' => date('Y-m-d')
                );
            
                $this->db->insert('user_transaction', $data);
                $response['status'] = 1; // if successful
                $response['message'] = "Amount added successfully";
            }
            else{
                $response['status'] = 0; // if failed
                $response['message'] = "Operation failed";
            }
        }
        else{
            $response['status'] = 0;
            $response['message'] = "Wrong parameter passed";
        }        
        if(empty($response)){
            $response['status']=0;
        }
        echo json_encode($response);
    }
    public function book_ride(){
    // latitude and longitude distance calculator
        $phone=$this->input->post('phone');
        $this->auth(null,['POST'],TRUE);
        if(!is_user($phone)){
            $response['status'] = 0;
            $response['message'] = "User Not Found";
            echo json_encode($response);exit;
        }
        // latitude and longitude
        $lat1 = $this->input->post('depart_lat'); // depature
        $lng1 = $this->input->post('depart_long'); // depature
        $lat2 = $this->input->post('dest_lat'); //destination
        $lng2 = $this->input->post('dest_long'); //destination
        $destination_lat2=$this->input->post('dest_lat2'); // 2nd stop
        $destination_long2=$this->input->post('dest_long2'); // 2nd top
        $ride_time=$this->input->post('ride_time');
        $duration = $this->input->post('duration');
        $demand = $this->input->post('demand');
        $vehicle_type = $this->input->post('vehicle_type');
        $distance = intval($this->input->post('distance'));
        $amount=$this->input->post('amount');
        $tax=$this->input->post('tax'); //tax
        $surcharges=$this->input->post('surcharges');
        $toll=$this->input->post('toll'); // toll
        $base_fare=$this->input->post('base_fare'); // base fare
        $commission = $this->input->post('comm'); // commission
        $discount = $this->input->post('discount');
        $coupon = $this->input->post('coupon') != '' ? $this->input->post('coupon') : NULL;

        $depart_name = $this->input->post('depart_name');
        $destination_name = $this->input->post('destination_name');
        $destination_name2 = $this->input->post('destination_name2');
        $payment_method=$this->input->post('payment_method'); // cash , wallet ,  online
        $date = date('Y-m-d H:i:s');
        $image1 = $this->db->select('photo_path')->where('id',$phone)->get('user_register');
        $image = $image1['photo_path'];
        if($payment_method=='wallet'){
            // check wallet balance
            $u_wallet=$this->db->select('wallet')->where('id',$phone)->get('user_register');
            if($u_wallet['wallet']<$amount){
            $response['status'] = 0;
            $response['message'] = "You don't have enough balance in wallet";
            echo json_encode($response);
            exit;
            }
        }
        // number of stops
        if($destination_name2!='')
        $stop=2;
        else
        $stop=1;

        $count = $this->db->query("SELECT id FROM `booking_details` where phone='$phone' AND ( ride_status='new' OR ride_status='onride' OR ride_status='confirm' OR ride_status='arrived' )")->result_array();

        if(count($count[0])==0){

        // latitude and longitude of Two Points
            $count=0;
            $result=$this->db->query("SELECT r.id,r.latitude,r.longitude,r.go_home,r.go_home_lat,r.go_home_long FROM driver_register r INNER JOIN driver_vehicle d on r.id=d.driver_id WHERE r.status='1' AND r.online!='no' and (r.latitude !='null' and r.latitude !='') and ( r.longitude !='null' and r.longitude !='' ) and d.vehicle_id='$vehicle_type' ")->result_array();            
            $bookid = strtoupper(uniqid().$phone);
            $otp = rand(1111,9999);
            $icount = 0;
            $range1= general_value('go_home_km');
            $range=general_value('km_range'); // km range of driver to get the booking
            foreach($result[0] as $row){
                $id=$row['id'];
                $distance1=distance($lat1,$lng1,$row['latitude'],$row['longitude'],"K");

                if(intval($distance1) <= $range)
                {
                    if($row['go_home'] == 1) //check driver go home query
                    {
                        $home_dis=distance($destination_lat2,$destination_long2,$row['go_home_lat'],$row['go_home_long'],"K");
                        if(intval($home_dis) <= $range1)
                        {
                            $this->db->query("INSERT INTO booking_details(bookid,otp,phone,stop,depart_name,destination_name,destination_name2,depart_lat,depart_long,dest_lat,dest_long,distance,duration,vehicle_type,demand,amount,base_fare,toll,tax,commission,surcharge,ride_status,payment_status,driver_id,payment_method,created_date,image,discount,coupon)
                            VALUES('$bookid','$otp','$phone','$stop','$depart_name','$destination_name','$destination_name2','$lat1','$lng1','$lat2','$lng2',
                            '$distance','$duration','$vehicle_type','$demand','$amount','$base_fare','$toll','$tax','$commission','$surcharges','new',0,'$id','$payment_method','$date','$image','$discount','$coupon')");
                            if($this->db->insert_id() > 0){
                                $icount++;
                            }
                        }
                    }
                    else{

                        $this->db->query("INSERT INTO booking_details(bookid,otp,phone,stop,depart_name,destination_name,destination_name2,depart_lat,depart_long,dest_lat,dest_long,dest_lat2,dest_long2,distance,duration,vehicle_type,demand,amount,base_fare,toll,tax,commission,surcharge,ride_status,payment_status,driver_id,payment_method,created_date,image,discount,coupon)
                        VALUES('$bookid','$otp','$phone','$stop','$depart_name','$destination_name','$destination_name2','$lat1','$lng1','$lat2','$lng2','$destination_lat2','$destination_long2',
                        '$distance','$duration','$vehicle_type','$demand','$amount','$base_fare','$toll','$tax','$commission','$surcharges','new',0,'$id','$payment_method','$date','$image','$discount','$coupon')");
                            if($this->db->insert_id() > 0){
                                $icount++;
                            }
                    }
                }
            }
            if($icount>0)
            {
                $response['status'] = 1;
                $response['message'] = "Booking success";
                $response['booking_id'] = $bookid;
            }
            else
            {
                $response['status'] = 0;
                $response['message'] = "No drivers found in your nearby location";
            }
        }
        else{
            $response['status'] = 0;
            $response['message'] = "You are already on ride";
        }
        if(empty($response)){
            $response['status']=0;
            $response['message'] = "Operation failed";
        }
        echo json_encode($response);
    }
    public function cancel_booking(){
        $phone=$this->input->post('phone');
        $this->auth(null,['POST'],TRUE);
        if(!is_user($phone)){
            $response['status'] = 0;
            $response['message'] = "User Not Found";
            echo json_encode($response);exit;
        }
        $book_id =$this->input->post('booking_id');
        if($phone!='' &&  $book_id!=''){
            $row=$this->db->query("SELECT ride_status from booking_details where phone='$phone' AND bookid='$book_id' and ride_status!='complete' ")->result_array();
            if($row[0]['ride_status']=='new'){
             $this->db->query("delete from booking_details where phone='$phone' and ride_status='new' and bookid='$book_id' ");   
            }
            else{
                $this->db->query("UPDATE booking_details set ride_status='cancel',cancelled_by='user' where phone='$phone' and bookid='$book_id' ");
                //cancellation charges
                $cancel_amount=general_value('cancel_charge');
                $this->db->query("UPDATE user_register set wallet=wallet-$cancel_amount where phone='$phone'");
                $payment_id=strtoupper(uniqid().'CNCEL');
                $this->db->query("INSERT INTO user_transaction (phone,amount,transaction_id,remark,type,status,date) values ('$phone','$cancel_amount','$payment_id','cancellation charge on ride $book_id',0,1,CURDATE())");
            }
            if($this->db->affected_rows()>0){
                  $response['status'] = 1; // if successful
                  $response['message'] = "Ride cancelled successfully";
            }
            else{
              $response['status'] = 0; // if coupon does not exist
              $response['message'] = "Cancellation failed";
            }
    
        }
        else{
            $response['status'] = 0;
            $response['message'] = "Wrong parameter passed";
        }
    
        if(empty($response)){
            $response['status']=0;
        }
        echo json_encode($response);
    }
    public function check_ride_status(){
        $phone=$this->input->post('phone');
        $this->auth(null,['POST'],TRUE);
        if(!is_user($phone)){
            $response['status'] = 0;
            $response['message'] = "User Not Found";
            echo json_encode($response);exit;
        }
        $bookid = $this->input->post('bookid');
        if($phone!=''){
            $sql=$this->db->query("select b.ride_status,b.ride_type,ve.type as vehicle,v.brand,v.model,b.otp,b.depart_name,b.destination_name,b.destination_name2,b.depart_lat,b.depart_long,b.dest_lat,b.dest_long,b.dest_lat2,b.dest_long2,d.id as driver_id,d.phone as driver_phone,d.fullname as driver_name,d.photo_path,v.noplate,b.amount,b.payment_method,b.payment_status,b.payment_status from booking_details b INNER JOIN driver_register d on b.driver_id=d.id INNER JOIN driver_vehicle v on d.id=v.driver_id INNER JOIN vehicle_list ve on ve.id=v.vehicle_id where b.phone='$phone' AND b.bookid='$bookid' and b.ride_status!='new' ");
            if($row=$sql[0]){
                $response['status'] = 1; // if successful
                $response['message'] = "Success";
                $response['data'][]=$row;
                if($row['payment_status']  == 1 || $row['payment_status']  == '1')
                {
                    user_firstride_referal($phone);
                    $rides = update_d_select($phone);
                    $response['data'][0]['d-select'] = $rides;
                }
            }
            else{
                $response['status'] = 0; // if failed
                $response['message'] = "We are fetching nearby drivers. Please wait...";
            }
        }
        if(empty($response)){
            $response['status']=0;
            $response['message'] = "Operation failed";
        }
        echo json_encode($response);
    }
    public function get_my_rides(){
        $phone=$this->input->post('phone');
        $this->auth(null,['POST'],TRUE);
        if(!is_user($phone)){
            $response['status'] = 0;
            $response['message'] = "User Not Found";
            echo json_encode($response);exit;
        }
        if($phone!=''){
            $sql=$this->db->query("select b.bookid as booking_id,v.type,v.image,b.created_date,b.depart_name,b.destination_name,b.destination_name2,b.amount from booking_details b INNER JOIN vehicle_list v on b.vehicle_type=v.id where b.phone='$phone' and b.ride_status='completed' ");
            while($row=$sql[0]){
                $response['status'] = 1; // if successful
                $response['data'][]=$row;
            }
        }
        else{
            $response['status'] = 0;
            $response['message'] = "Wrong parameter passed";    
        }    
        if(empty($response)){
            $response['status']=0;
            $response['message'] = "No Data Found";
        }
        echo json_encode($response);
    }

    public function get_ride_details(){

    $phone=$this->input->post('phone');
    $this->auth(null,['POST'],TRUE);
    if(!is_user($phone)){
        $response['status'] = 0;
        $response['message'] = "User Not Found";
        echo json_encode($response);exit;
    }
    $id= $this->input->post('booking_id');
    if(!empty($id)){
        $sql= $this->db->query("select b.id as booking_id,dv.*,v.type,v.image,b.distance,b.duration,b.base_fare,b.tax,b.commission,b.payment_method,b.created_date,b.depart_name,b.destination_name,b.destination_name2,b.amount from booking_details b INNER JOIN vehicle_list v on b.vehicle_type=v.id INNER JOIN driver_vehicle dv ON dv.driver_id=b.driver_id where b.phone='$phone' and b.bookid='$id' and b.ride_status='completed'");
        foreach($sql[0] as $row){
            $response['status'] = 1; // if successful
            $response['data']=$row;
        }
    }
    else{
        $response['status'] = 0;
        $response['message'] = "Wrong parameter passed";
    }

    if(empty($response)){
        $response['status']=0;
        $response['message']="Booking details not found!";
    }
    echo json_encode($response);
    }
    public function pay_for_ride_cash(){

        $phone=$this->input->post('phone');
        $this->auth(null,['POST'],TRUE);
        if(!is_user($phone)){
            $response['status'] = 0;
            $response['message'] = "User Not Found";
            echo json_encode($response);exit;
        }
        $bookid=$this->input->post('booking_id');
        if($phone!='' && $bookid!=''){
        $sql1=$this->db->query("select driver_id,amount,base_fare,commission,payment_status from booking_details where user_id='$phone' AND ride_status='completed' AND bookid='$bookid'");
        $row1=$sql1[0];
            if(count($row1)>=1){
                $payment_id=strtoupper($bookid.'-CASH');
                if($row1['payment_status']==1){
                    $response['status']=0;
                    $response['message']='Payment already done';
                }
                else{
                    $this->db->query("UPDATE booking_details set transaction_id='$payment_id',payment_status=1 where bookid='$bookid' AND user_id='$phone' ");
                    if($this->db->affected_rows()>0){
                    // user transaction entry                       
                            $amount=$row1['amount'];
                            $this->db->query("INSERT INTO user_transaction (phone,amount,transaction_id,remark,status,date) values ('$phone','$amount','$payment_id','ride payment by cash',1,CURDATE())");
                            // update driver wallet
                            $driver_id=$row1['driver_id'];
                            $comm=$row1['commission'];
                            $base_fare=$row1['base_fare'];
                            $wallet_deduction=$base_fare-$comm;
                            $this->db->query("UPDATE driver_register set wallet = wallet-$wallet_deduction where id='$driver_id' ");                    
                            $this->db->query("INSERT into driver_transaction (driver_id,amount,transaction_id,remark,status,date) values ('$driver_id','$amount','$payment_id','ride payment by cash',1,CURDATE())");            
                            $response['status']=1;
                            $response['message']='Payment successful';
                    }
                }
            }
            else{
            $response['status']=0;
            $response['message']="Ride not completed yet!";
            }
        }
        if(empty($response)){
            $response['status']=0;
            $response['message'] = "Operation failed";
        }
        echo json_encode($response);
    }
    public function set_favourite_ride(){
        $phone=$this->input->post('phone');
        $this->auth(null,['POST'],TRUE);

        if(!is_user($phone)){
            $response['status'] = 0;
            $response['message'] = "User Not Found";
            echo json_encode($response);exit;
        }
        $place_name = $_GET['place_name'];
        $latitude = $_GET['latitude'];
        $longitude = $_GET['longitude'];
        
        $this->db->query("INSERT INTO favourite_ride(phone,place_name,latitude,longitude) VALUES('$phone', '$place_name', '$latitude', '$longitude')");
        if($this->db->affected_rows() > 0){
            $response['status'] = 1;
            $response['message'] = "Successfully added to favourite list";
        }else{
            // echo mysqli_error($con);exit;
            $response['status'] = 0;
            $response['message'] = "Somthing went wrong, try again!";
        }
        echo json_encode($response);
        mysqli_close($con);
    }

    public function purchase_sub()
    {
        $phone = $this->auth('phone',['POST'],TRUE);
        $sub_type = $this->input->post('sub_type');
        $pay_type = $this->input->post('pay_type');
        if(strlen($phone) != 10){
            $this->api_return(['status' => FALSE, 'message' => 'invalid mobile number']);
            exit;
        }
        if(!$phone || !$sub_type |!$pay_type){
            $this->api_return(['status' => FALSE, 'message' => 'fields not provided']);
            exit;
        }
        $fetch = $this->db->select('sub_exp_date')->from('user_register')->where('phone', $phone)->get()->result_array();
        if($sub_type=='1'){
            $d = strtotime("+1 months",strtotime($fetch[0]['sub_exp_date']));
            $sub_exp_date = date("Y-m-d",$d);
            $sql = $this->db->set('sub_exp_date', $sub_exp_date)->set('pay_type', $pay_type)->set('sub_type', $sub_type)->where('phone', $phone)->update('user_register');
            if($sql){
                $this->api_return([
                    'status' => TRUE,
                    'message' => 'success'
                ]);
            }else{
                $this->api_return([
					"status" => FALSE,
					"message" => $this->db->_error_message()
				]);exit;
            }
        }
        if($sub_type=='3'){
            $d = strtotime("+3 months",strtotime($fetch[0]['sub_exp_date']));
            $sub_exp_date = date("Y-m-d",$d);
            $sql = $this->db->set('sub_exp_date', $sub_exp_date)->set('pay_type', $pay_type)->set('sub_type', $sub_type)->where('phone', $phone)->update('user_register');
            if($sql){
                $this->api_return([
                    'status' => TRUE,
                    'message' => 'success'
                ]);
            }else{
                $this->api_return([
					"status" => FALSE,
					"message" => $this->db->_error_message()
				]);exit;
            }
        }
        if($sub_type=='6'){
            $d = strtotime("+6 months",strtotime($fetch[0]['sub_exp_date']));
            $sub_exp_date = date("Y-m-d",$d);
            $sql = $this->db->set('sub_exp_date', $sub_exp_date)->set('pay_type', $pay_type)->set('sub_type', $sub_type)->where('phone', $phone)->update('user_register');
            if($sql){
                $this->api_return([
                    'status' => TRUE,
                    'message' => 'success'
                ]);
            }else{
                $this->api_return([
					"status" => FALSE,
					"message" => $this->db->_error_message()
				]);exit;
            }
        }
        if($sub_type=='12'){
            $d = strtotime("+12 months",strtotime($fetch[0]['sub_exp_date']));
            $sub_exp_date = date("Y-m-d",$d);
            $sql = $this->db->set('sub_exp_date', $sub_exp_date)->set('pay_type', $pay_type)->set('sub_type', $sub_type)->where('phone', $phone)->update('user_register');
            if($sql){
                $this->api_return([
                    'status' => TRUE,
                    'message' => 'success'
                ]);
            }else{
                $this->api_return([
					"status" => FALSE,
					"message" => $this->db->_error_message()
				]);exit;
            }
        }
    }

    public function get_cancel_rides()
    {
        $phone = $this->auth('phone',['POST'],TRUE);
        if(!$phone){
            $this->api_return([
                'status' => FALSE,
                'message' => 'fields not provided'
            ]);exit;
        }
        $row = $this->db->query("select b.bookid as booking_id,v.type,v.image,b.created_date,b.depart_name,b.destination_name,b.destination_name2,b.amount from booking_details b INNER JOIN vehicle_list v on b.vehicle_type=v.id where b.user_phone='$phone' and b.ride_status='cancel'")->result_array();
        if(count($row) > 0){
            foreach ($row as $data) {
                $this->api_return([
                    "status" => TRUE,
                    "data" => $data
                ]);
            }
        }else{
            $this->api_return([
                "status" => FALSE,
                "message" => "No data found"
            ]);exit;
        }
    }

    public function get_profile()
    {
        $phone = $this->input->post('phone');
        // $phone = $this->auth('phone',['POST'],TRUE);
        if(!$phone){
            $this->api_return([
                "status" => FALSE,
                "message" => "fields not provided"
            ]);exit;
        }
        $sql = $this->db->select('*')->from('user_register')->where('phone', $phone)->get()->result_array();
        if(count($sql)){
            $this->api_return([
                "status" => TRUE,
                "data" => $sql[0]
            ]);
        }else{
            $this->api_return([
                "status" => FALSE,
                "message" => "Server error"
            ]);
        }        
    }

    public function user_transaction()
    {
        $phone = $this->auth('phone',['POST'],TRUE); 
        // $phone = $this->input->post('phone');
        $amt = $this->input->post('amount');
        $transaction_id = $this->input->post('transaction_id');
        $status = $this->input->post('status');
        if(!$phone ||!$amt || !$transaction_id ||!$status){
            $this->api_return([
                "status" => FALSE,
                "message" => "fields not provided"
            ]);exit;
        }
        if($amt==0){
            $this->api_return([
                "status" => FALSE,
                "message" => 'Invalid amount'
            ]);exit;
        }
        $data = array(
            "user_phone" => $phone,
            "amount" => $amt,
            "transaction_id" => $transaction_id,
            "remark" => "added in wallet",
            "status" => $status,
            "date" => date("Y-m-d H:i")
        );
        $sql = $this->db->insert('user_transaction', $data);
        if($sql){
            $this->api_return([
                "status" => TRUE,
                "message" => "success"
            ]);
        }else{
            $this->api_return([
                "status" => FALSE,
                "message" => "server error"
            ]);exit;
        }
    }

    public function fetch_fav_ride()
    {
       // $phone = $this->auth('phone',['POST'],TRUE); 
       $phone = $this->input->post('user_phone');
       if(!$phone){
           $this->api_return([
               "status" => FALSE,
               "message" => "fields not provided"
           ]);
           exit;
       }
       $sql = $this->db->select('*')->from('favourite_ride')->where('user_phone', $phone)->get()->result_array();
       if(count($sql) > 0){
           foreach ($sql as $row) {
               $this->api_return([
                   "status" => TRUE,
                   "data" => $row
               ]);
           }
       }else{
           $this->api_return([
               "status" => FALSE,
               "message" => "No data found"
           ]);
       }
    }
}