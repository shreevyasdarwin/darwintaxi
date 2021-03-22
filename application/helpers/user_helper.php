<?php
function send_sms($phone, $msg)
{
    $curl = curl_init();
    $new = str_replace(' ', '%20', $msg);
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://enterprise.smsgupshup.com/GatewayAPI/rest?method=sendMessage&msg=" . $new . "&send_to=" . $phone . "&msg_type=Text&userid=2000190745&auth_scheme=Plain&password=jdHq2QoSg&v=1.1&format=TEXT",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: text/plain"
        ) ,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    // echo "<pre>";print_r($response);exit;
    $str1 = explode('|', $response);
    $str = str_replace(' ', '', $str1[0]);
    if ($str == 'success') return 1;
    else return 0;
}

// check for area range
function check_range($user_lat, $user_lon, $destination_lat, $destination_long)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://maps.googleapis.com/maps/api/directions/json?origin=$user_lat,$user_lon&destination=$destination_lat,$destination_long&mode=driving&key=AIzaSyAmZkCXszu47oMu-pPgxt3ZFBVDYgC2PEk",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
    ));

    $result = curl_exec($curl);
    $datas = json_decode($result);
    print_r($datas);exit;
    curl_close($curl);
    $steps = $datas->routes[0]
        ->legs[0]->start_address;
    $steps2 = $datas->routes[0]
        ->legs[0]->end_address;
    if ((strpos($steps, 'Mumbai') == true && strpos($steps2, 'Mumbai') == true) || (strpos($steps, 'Thane') == true && strpos($steps2, 'Thane') == true) || (strpos($steps, 'Lucknow') == true && strpos($steps2, 'Lucknow') == true))
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

// distance calculator
function distance_calculation($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://maps.googleapis.com/maps/api/directions/json?origin=$latitudeFrom,$longitudeFrom&destination=$latitudeTo,$longitudeTo&mode=driving&key=AIzaSyAmZkCXszu47oMu-pPgxt3ZFBVDYgC2PEk",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
    ));

    $result = curl_exec($curl);
    $datas = json_decode($result);

    curl_close($curl);
    $str = $datas->routes[0]
        ->legs[0]
        ->distance->text;
    if (empty($str))
    {
        return 1;
    }
    else
    {
        $str1 = str_replace('""', '', $str);
        return round($str1);
    }
}

function distance($lat1, $lon1, $lat2, $lon2, $unit)
{
    if (($lat1 == $lat2) && ($lon1 == $lon2))
    {
        return 0;
    }
    else
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K")
        {
            return ($miles * 1.609344);
        }
        else if ($unit == "N")
        {
            return ($miles * 0.8684);
        }
        else
        {
            return $miles;
        }
    }
}

// check for toll charges
function check_for_toll($user_lat, $user_lon, $destination_lat, $destination_long)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://maps.googleapis.com/maps/api/directions/json?origin=$user_lat,$user_lon&destination=$destination_lat,$destination_long&mode=driving&key=AIzaSyAmZkCXszu47oMu-pPgxt3ZFBVDYgC2PEk",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
    ));

    $result = curl_exec($curl);
    $datas = json_decode($result);

    curl_close($curl);
    $steps = $datas->routes[0]
        ->legs[0]->steps;
    foreach ($steps as $step)
    {
        if (strpos($step->html_instructions, 'Toll') == true)
        {
            $toll = 1;
            return $toll;
            exit;
        }
        else
        {
            $toll = 0;
        }
    }
    return $toll;
}

function fare_calculator($con, $distance, $p_vehicle_id, $p_toll, $minutes, $demand)
{
    date_default_timezone_set('asia/kolkata');
    $CI =& get_instance();
    $row  = $CI->db->get_where('vehicle_list', array('id'=>$p_vehicle_id,'status'=>1))->result_array();
    $row = $row[0];
    switch ($demand)
    {
        case 0:
            $percent = 0;
            $bp = 0;
        break;
        case $demand < 3:
            $percent = 0;
            $bp = 0;
        break;
        case $demand == 3:
            $percent = 50;
            $bp = 50;
        break;
        case $demand == 4:
            $percent = 100;
            $bp = 50;
        break;
        case $demand == 5:
            $percent = 150;
            $bp = 50;
        break;
        case $demand == 6:
            $percent = 200;
            $bp = 25;
        break;
        case $demand > 6:
            $percent = 250;
            $bp = 25;
        break;
        default:
            $percent = 0;
            $bp = 0;
    }

    $vehicle_price = $row['price'] - ($row['price'] * ($bp / 100));
    $vehicle_price_km = $row['km_rate'] + ($row['km_rate'] * ($percent / 100));
    $vehicle_minute = $row['minute_rate'] + ($row['minute_rate'] * ($percent / 100));
    $i = 1;
    $price = $vehicle_price;
    while ($i <= $distance)
    {
        // add km rate
        $price = $price + $vehicle_price_km;
        $i++;
    }
    $i = 1;
    while ($i <= $minutes)
    {
        // add minute rate
        $price = $price + $vehicle_minute;
        $i++;
    }
    $base_fare = strval($price);
    $response['status'] = 1;

    // end of base fare calculation
    if ($base_fare == '' || $base_fare == 0)
    {
        $response['status'] = 0;
        unset($base_fare);
    }
    else
    {
        $response['breakup']['base_fare'] = round($base_fare);
        //toll price
        $response['breakup']['toll'] = $p_toll;
        //commision
        $comm = general_value('commission');
        $response['breakup']['comm'] = round($response['breakup']['base_fare'] * $comm / 100);

        // tax
        //   $response['breakup']['tax']=round(($base_fare+$response['breakup']['toll']) * 5/100);
        $response['breakup']['tax'] = 0;

        //total price
        $response['total_price'] = $base_fare + $response['breakup']['toll'] + $response['breakup']['tax'];

        // transaction charges
        //   $response['breakup']['surcharges']=round($response['total_price']*2/100);
        $response['breakup']['surcharges'] = 0;

        $response['total_price'] = round($response['total_price'] + $response['breakup']['surcharges']);

    }

    return json_encode($response);
}

function get_arrival_time($user_lat, $user_lon, $con, $vehicle_id)
{
    $CI =& get_instance();
    $sql1  = $CI->db->query("SELECT r.id,r.latitude,r.longitude FROM driver_register r INNER JOIN driver_vehicle v on r.id=v.driver_id WHERE r.status='1' AND r.online='yes' and (r.latitude !='null' and r.latitude !='') and ( r.longitude !='null' and r.longitude !='' ) AND v.vehicle_id=$vehicle_id");

    $i = 0;
    while ($row1 = mysqli_fetch_array($sql1, MYSQLI_ASSOC))
    {
        $driver_lat = $row1['latitude'];
        $driver_lon = $row1['longitude'];
        $distance[$i] = round(distance_calculation($driver_lat, $driver_lon, $user_lat, $user_lon));
        $distance[$i]['id'] = $row1['id'];
        $i++;
    }
    $distance1[] = asort($distance);

    $driver_id = $distance1[0];

    $row2  = $CI->db->query("SELECT latitude,longitude FROM driver_register where id=$driver_id")->result_array();
    $row2 = $row2[0];
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://maps.googleapis.com/maps/api/directions/json?origin=" . $row2['latitude'] . "," . $row2['longitude'] . "&destination=$user_lat,$user_lon&mode=driving&key=AIzaSyAmZkCXszu47oMu-pPgxt3ZFBVDYgC2PEk",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
    ));

    $result = curl_exec($curl);
    $datas = json_decode($result);

    curl_close($curl);
    $minute = ($datas->routes[0]
        ->legs[0]
        ->duration
        ->value) / 60;
    if (empty($minute))
    {
        return "unknown";
    }
    else return strval(round($minute));
}
function get_journey_time($user_lat, $user_lon, $destination_lat, $destination_long)
{

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://maps.googleapis.com/maps/api/directions/json?origin=$destination_lat,$destination_long&destination=$user_lat,$user_lon&mode=driving&key=AIzaSyAmZkCXszu47oMu-pPgxt3ZFBVDYgC2PEk",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
    ));

    $result = curl_exec($curl);
    $datas = json_decode($result);

    curl_close($curl);
    $minute = round($datas->routes[0]
        ->legs[0]
        ->duration
        ->value) / 60;
    $return = strval(intval($minute));
    if (empty($return))
    {
        return "unknown";
    }
    else return $return;

}

function get_area_demand($lat1, $lng1, $con)
{
    $range = general_value('km_range'); // km range of driver to get the

    $CI =& get_instance();
    $result  = $CI->db->query(" SELECT r.id,r.latitude,r.longitude,r.go_home,r.go_home_lat,r.go_home_long FROM driver_register r INNER JOIN driver_vehicle d on r.id=d.driver_id WHERE r.status='1' AND r.online!='no' and (r.latitude !='null' and r.latitude !='') and ( r.longitude !='null' and r.longitude !='' )");

    while ($row = mysqli_fetch_array($result))
    {
        $id = $row['id'];
        $distance1 = $this->distance_calculation($lat1, $lng1, $row['latitude'], $row['longitude']);

        if (round($distance1) <= $range)
        {
            $demand = mysqli_num_rows(mysqli_query($con, "SELECT id from booking_details where driver_id='$id' AND ride_status='new' "));
            return $demand;
            exit;
        }
    }
}


function general_value($column){
    $CI = & get_instance();
    $value  = $CI->db->query("SELECT value from general_settings where name='$column'")->result_array();
    if($value){
        return $value[0]['value'];
    }
    else
        return 0;
}

