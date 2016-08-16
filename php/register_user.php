<?php // register_user.php 5.1.3.1 2016-02-03 ariokkon

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
define('SERVICE_NAME', 'register_user');

require_once 'db.php';

// POST https://www.example.com/poi_dp/register_user?key=12a7188211b5058525fbbcd
// 017d2bb52b243c107&auth_p=google
//
// Posted document depends on the authrization provider. In the case of google
// it is bare authentication token.

if ($_SERVER['REQUEST_METHOD'] == 'POST' )
{
  $db_opts = get_db_options();
  $mongodb = connectMongoDB($db_opts['mongo_db_name']);


  $request_body = file_get_contents('php://input');
  $reg_key = '';
  $auth_p = '';
  $errmsg = '';
  
  if (isset($_GET['key']))
  {
    $key = pg_escape_string($_GET['key']);
  } else {
    $errmsg = 'key missing';
  }
  if (isset($_GET['auth_p']))
  {
    $auth_p = pg_escape_string($_GET['auth_p']);
  } else {
    $errmsg = 'auth_p missing';
  }
  if ($errmsg != '') {
    header("HTTP/1.0 400 Bad Request");
    die ($errmsg);
  }
  
  // Find the call from the database

  $_reg_calls = $mongodb->_reg_calls;
  $registration_call = $_reg_calls->findOne(array("_id" => $key), 
      array("_id" => false));
  $auth_s = '';
  if($registration_call) {
    $user_id = $registration_call['user_id'];
    $auth_id = null;
    if ($auth_p == 'google') {  
      $output_msg = http_get('https://www.googleapis.com/oauth2/v3/tokeninfo?' .
          'id_token=' . $request_body);
      $output_data = http_parse_message($output_msg);
      $gauth_body = json_decode($output_data->body);
      $email = $gauth_body->email;
      if ($email) { // recognized by google
        $auth_s = $email;
      } else {
        header("HTTP/1.0 401 Unauthorized");
        echo '{"registration":false,"msg":"Denied by authorization provider"}';
      }
    } else if ($auth_p == 'fiware_lab') {
      $output_msg = http_get('https://account.lab.fiware.org/user?' .
          'access_token=' . $request_body);
      $output_data = http_parse_message($output_msg);
      $gauth_body = json_decode($output_data->body);
      
      $id = $gauth_body->id;
      $email = $gauth_body->email;
      if ($id) { // recognized by keyrock
        $auth_s = $id;
      } else {
        header("HTTP/1.0 401 Unauthorized");
        echo '{"registration":false,"msg":"Denied by authorization provider"}';
      }
    } else {
      header("HTTP/1.0 401 Unauthorized");
      echo '{"registration":false,"msg":"Unknown authorization provider"}';
    }
    $auth_id = $auth_p . ':' . $auth_s;
    
    if($auth_id) {
      $timestamp = time();
      // $auth_id == "<provider>:<id_in_provider>"
  
      $_auth = $mongodb->_auth; // authentication mappings
      $auth_mapping = $_auth->findOne(array("_id" => $auth_id));
      if(!$auth_mapping) {
        $auth_mapping = array(
          '_id' => $auth_id,
          'accounts' => array()
        );
      }
      // $auth_mapping OK
      $auth_mapping['accounts'][$user_id] = array(
        'registration_time' => $timestamp
      );
      $upd_criteria = array("_id" => $auth_id);
      $_auth->update($upd_criteria, $auth_mapping, 
          array("upsert" => true));
        
      $users = $mongodb->_users;
      $user = $users->findOne(array("_id" => $user_id), 
        array("_id" => false));
      $user['identifications'][$auth_id] = true;
      $user['reg_call'] = null;
      $users->update(array("_id" => $user_id),$user);
      
      $_reg_calls->remove(array("_id" => $key));
//      echo '{"registration":true,"name":"' . $user['name'] . '"}';
      echo json_encode(array(
            'registration' => true,
            'name' => $user['name']
          ));
    } else {
      header("HTTP/1.0 401 Unauthorized");
      echo '{"registration":false,' .
          '"msg":"register_user.php internal error: no $auth_id"}';
    }
  } else {
    header("HTTP/1.0 401 Unauthorized");
    echo '{"registration":false,"msg":"Key not recognized."}';
    
  }
}
?>
