<?php // get_user.php v.5.1.3.1 ariokkon 2016-02-09
/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, 
* All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
define('SERVICE_NAME', 'get_user');

require_once 'db.php';
require_once 'user_data_manager.php';
require_once 'util.php';
require 'security.php';

$debug_log = array();

if ($_SERVER['REQUEST_METHOD'] == 'GET' )
{
  if (isset ($_GET['user_id']))
  {
    $user_id_param = pg_escape_string($_GET['user_id']);
    $esc_ids = escape_csv($user_id_param, "'");
    $user_id_arr = explode(",", $esc_ids);
    foreach($user_id_arr as &$user_id)
    {
      $user_id = str_replace("'", "", $user_id);
      $data[$user_id] = array();
    }

    // viewing a user requires administrator permission
    $session = get_session();
    $update_permission = $session['permissions']['admin'];
    if(!$update_permission) {
      header("HTTP/1.0 403 Forbidden");
      die("Permission denied.");
    }
    $db_opts = get_db_options();
    $mongodb = connectMongoDB($db_opts['mongo_db_name']);
    $users = $mongodb->_users;
    foreach($user_id_arr as $user_id)
    {
      $user = $users->findOne(array("_id" => $user_id), 
        array("_id" => false));
      if($user == NULL) {
        header("HTTP/1.0 404 Not found");
        die('User ' . $user_id . ' not recognized.');
      }
      $data[$user_id] = $user;
    }
    $user_data = array('users' => $data);
    $user_data['service_info'] = get_service_info(SERVICE_NAME);
    header("HTTP/1.0 200 OK");
    header("Content-type: application/json");
    header("Access-Control-Allow-Origin: *");
    echo json_encode($user_data);
  }
  
  else {
    header("HTTP/1.0 400 Bad Request");
    die("'user_id' parameter must be specified!");
  }
}

else if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  header("Access-Control-Allow-Origin: *");
  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
    header("Access-Control-Allow-Methods: GET, OPTIONS");

  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
    header("Access-Control-Allow-Headers:" . 
        " {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
  exit(0);
}

else {
  header("HTTP/1.0 400 Bad Request");
  die("You must use HTTP GET for fetching user data!");
}
?>
