<?php // login.php 5.1.3.1 2015-12-10 ariokkon

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
define('SERVICE_NAME', 'login');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' )
{
  if (isset($_GET['auth_p']))
  {
    $auth_p = pg_escape_string($_GET['auth_p']);
  } else {
     header("HTTP/1.0 400 Bad Request");
    die ('auth_p missing');
  }

  $user_id_sp = ""; // specified user_id, if multiple choices available
  if (isset($_GET['user_id']))
  {
    $user_id_sp = pg_escape_string($_GET['user_id']);
  }


  $db_opts = get_db_options();
  $mongodb = connectMongoDB($db_opts['mongo_db_name']);

  $auth_conf_file = file_get_contents('./auth_conf.json');
  $auth_conf = json_decode($auth_conf_file, true);
  
  $request_body = file_get_contents('php://input');
  $user = null;
  $user_id = null;
  $identification = null;

  
  // Hard authorizations set $user, others set $user_id
  
  $hard_auths = $auth_conf['hard_auths'];
  $hard_users = $auth_conf['hard_users'];
  $user = null; $user_id = null; // set either
  
  $auth_id = null;
  $auth_s = null;
  if ($auth_p == 'google') {  

    $output_msg = http_get('https://www.googleapis.com/oauth2/v3/tokeninfo?' .
        'id_token=' . $request_body);
    
    $output_data = http_parse_message($output_msg);
    $gauth_body = json_decode($output_data->body);
    $email = $gauth_body->email;
    if ($email) { // recognized by google
      $auth_s = $email;
    }
  } else if ($auth_p == 'fiware_lab') {
    
    $output_msg = http_get('https://account.lab.fiware.org/user?' .
        'access_token=' . $request_body);
    
    $output_data = http_parse_message($output_msg);
    
    $gauth_body = json_decode($output_data->body);

    $id = $gauth_body->id;
    if ($id) { // recognized by fiware_lab
      $auth_s = $id;
    }
  } // New authentication providers here!
  
  $auth_key = $auth_p . ':' . $auth_s;
  $account_ids = array();
  // Collect hard accounts 
  foreach ($hard_auths[$auth_key]['accounts'] as $user_id => $reg_time) {
    array_push($account_ids, $user_id);
  }
  // and add soft accounts
  $auth = $mongodb->_auth;
  $soft_auths = $auth->findOne(array("_id" => $auth_key), 
            array("_id" => false));
  if ($soft_auths) {
    $soft_accounts = $soft_auths['accounts'];
    if ($soft_accounts) {
      foreach ($soft_accounts as $user_id => $reg_time) {
        array_push($account_ids, $user_id);
      }
    }
  }
  
  // account_ids contains now account choices for given authentication

  $users = $mongodb->_users;
  
  $choices = array();
  $user_record_choices = array();
  foreach ( $account_ids as $user_id ) {
    $user_description = null;
    $user_record = $hard_users[$user_id];
    if ( !$user_record ){
      $user_record = $users->findOne(array("_id" => $user_id), 
            array("_id" => false));
    }
    if ( $user_record ) {
      $user_description = $user_record['name'] . ', ' . $user_record['email'];
      $choices[$user_id] = $user_description;
      $user_record_choices[$user_id] = $user_record;
    }
  }
  
  if (count($choices) == 0) { // not permitted
  
  } else if (( count($choices) == 1 ) || ($choices[$user_id_sp])) {
            // directly log in
    if ($choices[$user_id_sp]) {
      $user_id = $user_id_sp;
    } else {
      foreach( $choices as $user_id => $user_description) {
        break;
      }
    }
    // Now login as $user_id

    $user = $user_record_choices[$user_id];
    // Login succeeded
    // ===============
    // Create session data to MongoDB
    
    $auth_t = sha1($request_body);
    $sessions = $mongodb->_sessions;
    // Check for double login
    if(!$sessions->findOne(array("_id" => $auth_t), 
        array("_id" => false))) {
      $session = array(
        '_id' => $auth_t,
        'user' => $user_id,
        'begin_time' => time(),
        'permissions' => $user['permissions'],
        'identification' => $auth_key
      );
      $sessions->insert($session);
    }
    
    echo '{"login":true,"auth_t":"' . $auth_t . '"}';

  } else { // several accounts available, request selection
    $response = array(
      'login' => false,
      'choices' => $choices
    );
    echo json_encode($response);
  }
}
?>
