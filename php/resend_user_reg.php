<?php // resend_user_reg.php v.5.1.3.1 ariokkon 2016-02-01

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
define('SERVICE_NAME', 'resend_user_reg');

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

if ($_SERVER['REQUEST_METHOD'] == 'GET' )
{
  // handling a user requires admin permission
  $session = get_session();
  $admin_permission = $session['permissions']['admin'];
  if(!$admin_permission) {
    header("HTTP/1.0 403 Forbidden");
    die("Permission denied.");
  }
  
  $no_mail = FALSE;
  if (isset ($_GET['no_mail'])) {
    $no_mail = strtoupper(pg_escape_string($_GET['no_mail'])) != 'FALSE';
  }
  
  if (isset ($_GET['user_id']))
  {
    $user_id = pg_escape_string($_GET['user_id']);

    $db_opts = get_db_options();

    $timestamp = time();
    
    $mongodb = connectMongoDB($db_opts['mongo_db_name']);

    $users = $mongodb->_users;
    
    $user_data = $users->findOne(array("_id" => $user_id), 
        array("_id" => false));
    if($old_user_data == NULL) {
      header("HTTP/1.0 404 Not found");
      die('User unrecognized');
    }
    
    $registration_key = $user_data['reg_call'];
    $_reg_calls = $mongodb->_reg_calls;
    if ($registration_key) {
      $registration_call = $_reg_calls[$registration_key];
      $registration_call['timestamp'] = $timestamp;
      $_reg_calls->update($registration_call);
    } else {
      $registration_call = NULL;
    }
    if (!$registration_call) { // New registration Id needed
      $registration_key = poi_new_key();

      $user_data['reg_call'] = $registration_key;
      $users->update($user_data);
      
      $registration_call = array();
      $registration_call['_id'] = $registration_key;
      $registration_call['user_id'] = $user_id;
      $registration_call['timestamp'] = $timestamp;
      $_reg_calls->insert($registration_call);
    }

    $ret_val_arr = array('description' => 'Recalled invitation to register.');
    
    $ret_val_arr['service_info'] = get_service_info(SERVICE_NAME);

    $registration_url = getDirUrl() . '/register_me.html?key=' .
        $registration_key;

    $mres = false;
    if (!$no_mail) { // Send invitation, if not forbidden.
      $msubject = 'Invitation to Register to a POI Data Provider';
      $mmessage = 'You may now register to the POI database using the' .
          ' following link:' . "\n" .
          $registration_url . "\n";
      $mres = mail( $email, $msubject, $mmessage); 
    }
    
    $ret_val_arr['name'] = $user_data['name'];
    $ret_val_arr['user_id'] = $user_id;
    $ret_val_arr['email'] = $email;
    $ret_val_arr['registration_url'] = $registration_url;
    $ret_val_arr['mail_sent'] = $mres;
    
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
