<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['default_controller'] = 'user';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// ***************************************User*****************************************************

$route['api/v1/user/login'] 				= 'User/login';

$route['api/v1/user/update_profile'] 		= 'User/update_profile';

$route['api/v1/user/profile'] 				= 'User/profile';

$route['api/v1/user/vehicle_list'] 			= 'User/vehicle_list';

$route['api/v1/user/generateAccessToken'] 	= 'User/generateAccessToken';

$route['api/v1/user/payForRideFromWallet'] 	= 'User/payForRideFromWallet';
$route['api/v1/user/purchase_sub'] 	= 'User/purchase_sub';

$route['api/v1/user/get_cancel_rides'] 	= 'User/get_cancel_rides';

$route['api/v1/user/get_profile'] 	= 'User/get_profile';

$route['api/v1/user/user_transaction'] 	= 'User/user_transaction';

$route['api/v1/user/fetch_fav_ride'] 	= 'User/fetch_fav_ride';


// ***************************************Driver*****************************************************

$route['api/v1/driver/get_driver_detail'] 	= 'Driver/get_driver_detail/{1}';

$route['api/v1/driver/get_vehicle_list'] 	= 'Driver/get_vehicle_list';

$route['api/v1/driver/get_my_booking'] 		= 'Driver/get_my_booking';

$route['api/v1/driver/login'] 				= 'Driver/login';

$route['api/v1/driver/purchase_sub'] 		= 'Driver/purchase_sub';

$route['api/v1/driver/service_list'] 		= 'Driver/service_list';

$route['api/v1/driver/report_user'] 		= 'Driver/report_user';

$route['api/v1/driver/subscription'] 		= 'Driver/subscription';

$route['api/v1/driver/update_driver_status']= 'Driver/update_driver_status';

$route['api/v1/driver/update_location'] 	= 'Driver/update_location';

$route['api/v1/driver/rental_price'] 		= 'Driver/rental_price';

$route['api/v1/driver/fetch_alert'] 		= 'Driver/fetch_alert';

$route['api/v1/driver/driver_transaction'] 	= 'Driver/driver_transaction';

$route['api/v1/driver/get_wallet'] 			= 'Driver/get_wallet';

$route['api/v1/driver/logout'] 				= 'Driver/logout';

$route['api/v1/driver/get_driver_detail'] 	= 'Driver/get_driver_detail/{1}';
