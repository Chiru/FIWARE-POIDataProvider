<?php // delete_user.php v.5.1.3.1 ariokkon 2016-02-09
/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, 
* All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
define('SERVICE_NAME', 'delete_user');

require_once 'db.php';
require_once 'user_data_manager.php';
require_once 'util.php';
require 'security.php';

$debug_log = array();

if ($_SERVER['REQUEST_METHOD'] == 'DELETE' )
{
  if (isset ($_GET['user_id']))
  {
    $user_id = pg_escape_string($_GET['user_id']);

    // deleting a user requires administrator permission
    $session = get_session();
    $update_permission = $session['permissions']['admin'];
    if(!$update_permission) {
      header("HTTP/1.0 403 Forbidden");
      die("Permission denied.");
    }
    $db_opts = get_db_options();
    $mongodb = connectMongoDB($db_opts['mongo_db_name']);
    $users = $mongodb->_users;
    $user = $users->findOne(array("_id" => $user_id), 
      array("_id" => false));
    
    if($user != NULL) { // remove user
    
      // remove registration call
      if($user['reg_call']) {
        $_reg_calls = $mongodb->_reg_calls;
        $_reg_calls->remove(array("_id" => $user['reg_call']));
      }
      
      // remove identifications
      if ($user['identifications']) {
        $_auth = $mongodb->_auth;
        foreach ($user['identifications'] as $auth_id => $temp ) {
          // to be removed
          $auth_reg = $_auth->findOne(array("_id" => $auth_id), 
               array("_id" => false));
          unset($auth_reg['accounts'][user_id]);
          if(count($auth_reg['accounts']) < 1) {  // no accounts left
            $_auth->remove(array("_id" => $user_id));
          } else { // still accounts
            $upd_criteria = array("_id" => $auth_id);
            $_auth->update($upd_criteria, $auth_reg, 
                array("upsert" => true));
          }
        }
      }

      // remove user record
      $users->remove(array("_id" => $user_id));
    } else {
      header("HTTP/1.0 404 Not found");
      die("User id not recognized.");
    }
    
    header("Access-Control-Allow-Origin: *");
    echo 'User ' . $user['name'] . ' deleted succesfully';
  }
  
  else {
    header("HTTP/1.0 400 Bad Request");
    die("'user_id' parameter must be specified!");
  }
}

else if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  header("Access-Control-Allow-Origin: *");
  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
    header("Access-Control-Allow-Methods: DELETE, OPTIONS");

  if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
    header("Access-Control-Allow-Headers:" . 
        " {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
  exit(0);
}

else {
  header("HTTP/1.0 400 Bad Request");
  die("You must use HTTP DELETE for deleting user data!");
}
?>
