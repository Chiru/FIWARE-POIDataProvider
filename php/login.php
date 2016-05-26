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
  $user = null; $user_id = null; // set either
  
  if ($auth_p == 'google') {  

    $output_msg = http_get('https://www.googleapis.com/oauth2/v3/tokeninfo?' .
        'id_token=' . $request_body);
    
    $output_data = http_parse_message($output_msg);
    $gauth_body = json_decode($output_data->body);
    $email = $gauth_body->email;
    if ($email) { // recognized by google
      if ($hard_auths['google'][$email]) {
        $user = $hard_auths['google'][$email];
      } else {
        $auth_google = $mongodb->_auth_google; // Google authentication mappings
        
        $user_id = $auth_google->findOne(array("_id" => $email), 
            array("_id" => false))['user'];

      }
      $identification = array(
            'provider' => 'google',
            'google' => array(
              'email' => $email,
              'token' => $request_body
            )
          );
    }
  } else if ($auth_p == 'fiware_lab') {
    
    $output_msg = http_get('https://account.lab.fiware.org/user?' .
        'access_token=' . $request_body);
    
    $output_data = http_parse_message($output_msg);
    
    $gauth_body = json_decode($output_data->body);

    $id = $gauth_body->id;
    if ($id) { // recognized by fiware_lab
      if ($hard_auths['fiware_lab'][$id]) {
        $user = $hard_auths['fiware_lab'][$id];
      } else {
        $auth_fiware_lab = $mongodb->_auth_fiware_lab;
            // fiware_lab authentication mappings
        
        $user_id = $auth_fiware_lab->findOne(array("_id" => $id), 
            array("_id" => false))['user'];

      }
      $identification = array(
            'provider' => 'fiware_lab',
            'fiware_lab' => array(
              'id' => $id,
              'token' => $request_body
            )
          );
    }
  } 
  // $user or $user_id is set, $identification is set
  
  if (!$user) {
    if (!$user_id) {
      header("HTTP/1.0 401 Unauthorized");
      die("Permission denied A.");
    }
    $users = $mongodb->_users;
    $user = $users->findOne(array("_id" => $user_id), 
          array("_id" => false));
  }
  if (!$user) {
      header("HTTP/1.0 401 Unauthorized");
      die("Permission denied B.");
  }
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
      'identification' => $identification
    );
  
    $sessions->insert($session);
  }
  
  echo '{"login":true,"auth_t":"' . $auth_t . '"}';

/*  
  $mres = mail( $gauth_body->email, 'POI login', 'Successfull login');
  echo "\nmail result=" . json_encode($mres);
*/
}
?>
