<?php
function encrypt($userid){
    $token=base64_encode($userid.rand(11,99));
    return $token;
 }

//  send otp
function send_otp($phone, $msg){
    $curl = curl_init();
			$new = str_replace(' ', '%20', $msg);
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
            return $response2;
} 

 