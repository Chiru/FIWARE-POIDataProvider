<?php // add_user.php v.5.4.2.1 ariokkon 2016-08-04

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

function getUrl() {
  $url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
  $url .= ( $_SERVER["SERVER_PORT"] != 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
  $url .= $_SERVER["REQUEST_URI"];
  return $url;
}

function getDirUrl() {
  $url = getUrl();
  $path = explode('?', $url, 2)[0];
  $dirpath_end = strrpos($path, '/');
  $dirpath = substr($path, 0, $dirpath_end);
  return $dirpath;
}
 
// BEGIN Resource

if ($_SERVER['REQUEST_METHOD'] == 'POST' )
{
  // adding a user requires admin permission
  $session = get_session();
  $operator_id = $session['user'];
  $admin_permission = $session['permissions']['admin'];
  if(!$admin_permission) {
    header("HTTP/1.0 403 Forbidden");
    die("Permission denied.");
  }

  $site_info_s = file_get_contents("./site_info.json");
  $site_info = json_decode($site_info_s, true);
  
  $no_mail = FALSE;
  if (isset ($_GET['no_mail'])) {
    $no_mail = strtoupper(pg_escape_string($_GET['no_mail'])) != 'FALSE';
  }
  
  $request_body = file_get_contents('php://input');
//     print $request_body;
  
  $user_data = json_decode($request_body, true);
  
  if ($user_data != NULL)
  {
//         print "JSON decoded succesfully!";
    
    $is_valid = validate_user_data($user_data);
    if (!($is_valid))
    {
      header("HTTP/1.0 400 Bad Request");
      die ("Invalid user data");
    }
    
    if(!$user_data['email']) {
      header("HTTP/1.0 400 Bad Request");
      die("Email address missing.");
    }
    $email = $user_data['email'];
    $db_opts = get_db_options();
    
    $user_id = poi_new_uuid_v4();
    $timestamp = time();
    
    $mongodb = connectMongoDB($db_opts['mongo_db_name']);

    $registration_key = poi_new_key();
    
    $user_data["_id"] = $user_id;
    $user_data['last_update'] = array();
    $user_data['last_update']['timestamp'] = $timestamp;
    $user_data['last_update']['responsible'] = $operator_id;
    $user_data['reg_call'] = $registration_key;
    
    $collection = $mongodb->_users;
    $collection->insert($user_data);
    
    $ret_val_arr = array('description' => 'User added.');
    
    $ret_val_arr['service_info'] = get_service_info(SERVICE_NAME);

    $registration_call = array();
    $registration_call['_id'] = $registration_key;
    $registration_call['user_id'] = $user_id;
    $registration_call['timestamp'] = $timestamp;

    $_reg_calls = $mongodb->_reg_calls;
    $_reg_calls->insert($registration_call);

    $registration_url = getDirUrl() . '/register_me.html?key=' .
        $registration_key;

    /*
    $mres = false;
    if (!$no_mail) { // Send invitation, if not forbidden.
      $msubject = 'Invitation to Register to a POI Data Provider';
      $mmessage = 'You may now register to the POI database using the' .
          ' following link:' . "\n" .
          $registration_url . "\n";
      $mres = mail( $email, $msubject, $mmessage); 
    }
    */
    $ret_val_arr['registration_call'] = call_to_register($email,
        $user_data['name'], $registration_url, $site_info['name'], 
        !$no_mail);

    $ret_val_arr['name'] = $user_data['name'];
    $ret_val_arr['user_id'] = $user_id;
    $ret_val_arr['email'] = $email;
    $ret_val_arr['registration_url'] = $registration_url;
    $ret_val_arr['mail_sent'] = $ret_vall_arr['registration_call']['sent'];
    
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
