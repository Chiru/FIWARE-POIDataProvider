<?php // add_user.php v.5.1.3.1 ariokkon 2016-02-01

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
define('SERVICE_NAME', 'add_user');

require_once 'db.php';
require_once 'user_data_manager.php';
require_once 'util.php';
require 'security.php';

$debug_log = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' )
{
  // adding a user requires admin permission
  $session = get_session();
  $admin_permission = $session['permissions']['admin'];
  if(!$admin_permission) {
    header("HTTP/1.0 401 Unauthorized");
    die("Permission denied.");
  }

  $request_body = file_get_contents('php://input');
//     print $request_body;
  
  $request_array = json_decode($request_body, true);
  
  if ($request_array != NULL)
  {
//         print "JSON decoded succesfully!";
    
    $is_valid = validate_user_data($request_array);
    if (!($is_valid && $request_array['_user']))
    {
      header("HTTP/1.0 400 Bad Request");
      die ("User data missing or validation failed!");
    }
    
    $user_data = $request_array["_user"];

    if(!$user_data['email']) {
      header("HTTP/1.0 400 Bad Request");
      die("Email address missing.");
    }
    $email = $user_data['email'];
    $db_opts = get_db_options();
    $pgcon = connectPostgreSQL($db_opts["sql_db_name"]);
    
    $uuid = poi_new_uuid_v4();
    $timestamp = time();
    
    $mongodb = connectMongoDB($db_opts['mongo_db_name']);

    $user_data["_id"] = $uuid;
    $user_data['last_update'] = array();
    $user_data['last_update']['timestamp'] = $timestamp;
    
    $collection = $mongodb->_users;
    $collection->insert($user_data);
    
    $new_user_info = array();
    $new_user_info['uuid'] = $uuid;
    $new_user_info['timestamp'] = $timestamp;
    $ret_val_arr = array("created_user" => $new_user_info);
    
    $ret_val_arr['service_info'] = get_service_info(SERVICE_NAME);

    $registration_key = poi_new_key();
    $registration_call = array();
    $registration_call['_id'] = $registration_key;
    $registration_call['user_id'] = $uuid;
    $registration_call['timestamp'] = $timestamp;

    $_reg_calls = $mongodb->_reg_calls;
    $_reg_calls->insert($registration_call);

// *** CHANGE THE WEB ADDRESS! ***    
    $msubject = 'Call for a POI DB Registration';
    $mmessage = 'You may now register to the POI database' . "\n" .
        'http://ari.webhop.org/register_user?key='. $registration_key . "\n";
        
    $mres = mail( $email, $msubject, $mmessage); 

    $ret_val_arr['mail_data'] =  array(
        'address' => $email,
        'subject' => $msubject,
        'message' => $mmessage,
        'result' => $mres);
    
    $ret_val = json_encode($ret_val_arr);
      
    header("Access-Control-Allow-Origin: *");
    print $ret_val;
  }
  
  else
  {
    header("HTTP/1.0 400 Bad Request");
    die("Error decoding request payload as JSON!");
  }
  
}

else if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  header("Access-Control-Allow-Origin: *");
  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
    header("Access-Control-Allow-Methods: POST, OPTIONS");

  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

  exit(0);
}

else {
   header("HTTP/1.0 400 Bad Request");
   echo "You must use HTTP POST for adding a new POI!";
   return;
}

?>
