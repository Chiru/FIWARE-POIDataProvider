<?php // find_users.php v.5.1.3.1 ariokkon 2016-02-08
/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
define('SERVICE_NAME', 'find_users');
define('DEFAULT_QUERY_LIMIT', 50); // 50 users per request

require_once 'db.php';
require_once 'user_data_manager.php';
require_once 'util.php';
require 'security.php';

$debug_log = array();
$qskip = 0; // start form the first user
$qlimit = DEFAULT_QUERY_LIMIT;

if ($_SERVER['REQUEST_METHOD'] == 'GET' )
{
  // adding a user requires admin permission
  $session = get_session();
  $admin_permission = $session['permissions']['admin'];
  if(!$admin_permission) {
    header("HTTP/1.0 401 Unauthorized");
    die("Permission denied.");
  }
  
  if (isset($_GET['skip']))
  {
    $qskip = intval($_GET['skip']);
  }
  if (isset($_GET['limit']))
  {
    $qlimit = intval($_GET['limit']);
    if($qlimit < 0) $qlimit = -$qlimit; // force positive
  }

  $db_opts = get_db_options();
  
  $mongodb = connectMongoDB($db_opts['mongo_db_name']);
  
  $users = $mongodb->_users;
  
  $users_sample = iterator_to_array ($users->find()->skip($qskip)->
      limit(-$qlimit)); // close cursor

  $ret_val = json_encode($users_sample);
    
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
   echo "You must use HTTP POST for adding a new POI!";
   return;
}

?>
