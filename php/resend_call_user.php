<?php // add_user.php v.5.4.2.1 ariokkon 2016-08-04

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, 
* All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
define('SERVICE_NAME', 'resend_call_user');

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
  // sending a new call to user requires admin permission
  $session = get_session();
//  $operator_id = $session['user'];
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

  if (isset ($_GET['user_id']))
  {
    $user_id = pg_escape_string($_GET['user_id']);
  } else {
    header("HTTP/1.0 404 Not found");
    die ('Error: user_id missing');
  }
  
  $db_opts = get_db_options();
  $mongodb = connectMongoDB($db_opts['mongo_db_name']);
  $users = $mongodb->_users;
  
  $user_data = $users->findOne(array("_id" => $user_id), 
      array("_id" => false));
  if($user_data == NULL) {
    header("HTTP/1.0 404 Not found");
    die('User unrecognized');
  }
  $email = $user_data['email'];
//  $timestamp = time();
  
  $registration_key = $user_data['reg_call'];
  $ret_val_arr = array('description' => 'Resend registration call');
  $ret_val_arr['service_info'] = get_service_info(SERVICE_NAME);
  $registration_url = getDirUrl() . '/register_me.php?key=' .
      $registration_key;

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
   echo "You must use HTTP POST to renew a call to user!";
   return;
}

?>
