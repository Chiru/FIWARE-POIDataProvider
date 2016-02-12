<?php // update_user.php v.5.1.3.1 ariokkon 2016-02-09
/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, 
* All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
define('SERVICE_NAME', 'update_user');

require_once 'db.php';
require_once 'user_data_manager.php';
require_once 'util.php';
require 'security.php';

$debug_log = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' )
{
  // updating user data requires administrator permission
  $session = get_session();
  $permission = $session['permissions']['admin'];
  if(!$permission) {
    header("HTTP/1.0 403 Forbidden");
    die("Permission denied.");
  }
  
  $request_body = file_get_contents('php://input');
  $request_array = json_decode($request_body, true);
  
  if ($request_array != NULL)
  {
    $new_timestamp = time();
    $user_id = pg_escape_string(key($request_array));
    $new_user_data = $request_array[$user_id];
    $is_valid = validate_user_data($new_user_data);
    if (!$is_valid)
    {
      header("HTTP/1.0 400 Bad Request");
      die ("User data validation failed!");
    }
    
    $db_opts = get_db_options();
    $mongodb = connectMongoDB($db_opts['mongo_db_name']);
    $users = $mongodb->_users;
    
    $old_user_data = $users->findOne(array("_id" => $user_id), 
        array("_id" => false));
    if($old_user_data == NULL) {
      header("HTTP/1.0 404 Not found");
      die('User unrecognized');
    }
    $update_timestamp = 0;
    $curr_timestamp = 0;
    if (isset($new_user_data['last_update']))
    {
      $last_update = $new_user_data['last_update'];
      if (isset($last_update['timestamp']))
      {
        $update_timestamp = intval($last_update['timestamp']);
      }
    }
    if (isset($old_user_data['last_update']))
    {
      if (isset($old_user_data['last_update']['timestamp']))
      {
        $curr_timestamp = $old_user_data['last_update']['timestamp'];
      }   
    }
    if ($curr_timestamp != $update_timestamp) {
      if ($update_timestamp == 0)
      {
        header("HTTP/1.0 400 Bad Request");
        die("No valid 'last_update.timestamp' value was ' .
          'found for '$comp_name' component!");
      } else {
        header("HTTP/1.0 409 Conflict");
        die('Edit conflict. Get the data modified by others and redo edits.');
      }
    }
    if (!isset($new_user_data['last_update']))
    {
      $new_user_data['last_update'] = array();
    }
    $new_user_data['last_update']['timestamp'] = $new_timestamp;
    
    $new_user_data["_id"] = $user_id;               
    $upd_criteria = array("_id" => $user_id);
    try {
      $users->update($upd_criteria, $new_user_data, 
          array("upsert" => true));
    } catch(Exception $e) {
      echo "*ERROR: Exception when updating " . $user_id . "." . $comp_name . "\n";
      echo "  message: " . $e->getMessage() . "\n";
      echo "  code: " . $e->getCode() . "\n";
      
    }
    header("Access-Control-Allow-Origin: *");
    print "User data updated succesfully!";
  }
}

else if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  header("Access-Control-Allow-Origin: *");
  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
    header("Access-Control-Allow-Methods: POST, OPTIONS");

  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
    header("Access-Control-Allow-Headers:" . 
        " {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
  exit(0);
}

else {
  header("HTTP/1.0 400 Bad Request");
  die("You must use HTTP POST for updating user data!");
}
?>
