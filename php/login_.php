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
  $db_opts = get_db_options();
  $mongodb = connectMongoDB($db_opts['mongo_db_name']);


  $request_body = file_get_contents('php://input');

  $output_msg = http_get('https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $request_body);
  
  $output_data = http_parse_message($output_msg);
  $gauth_body = json_decode($output_data->body);
  $email = $gauth_body->email;
  
  $auth_conf_file = file_get_contents('./auth_conf.json');
  $auth_conf = json_decode($auth_conf_file, true);
  
  if ($auth_conf['admin'][0]['auth_id'] == $email) {
    
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
        'user' => '<UUID of the user>',
        'begin_time' => time(),
        'permissions' => array(
          'admin' => true,
          'add' => true,
          'update' => true,
          'view' => true
        ),
        'identification' => array(
          'provider' => 'google',
          'google' => array(
            'email' => $email,
            'token' => $request_body
          )
        )
      );
    
      $sessions->insert($session);
    }
    
    echo '{"login":true,"auth_t":"' . $auth_t . '"}';
  } else {
    
    // Login failed
    // ============
    echo '{"login":false}';
  }

/*  
  $mres = mail( $gauth_body->email, 'POI login', 'Successfull login');
  echo "\nmail result=" . json_encode($mres);
*/
}
?>
