<?php
function encrypt($userid){
    $token=base64_encode($userid.rand(11,99));
    return $token;
 }

 