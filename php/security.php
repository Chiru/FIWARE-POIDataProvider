<?php // security.php 5.2.1.1 2016-01-29 ariokkon

/*
* Project: FIWARE
* Copyright (c) 2016 Adminotech Oy, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
require_once 'db.php';

function get_session() {
  // 
  $session = array(  // default session data - no permissions
    'user' => '',
    'begin_time' =>0,
    'permissions' => array(
      'admin' => false,
      'add' => false,  // permits adding a new POIs
      'update' => false, // permits deletion, also
      'view' => false // permits searching and getting data
    ),
    'identification' => array(
      'provider' => ''
    )
  );
  
  $auth_conf_file = file_get_contents('./auth_conf.json');
  if($auth_conf_file){
    // auth_conf.json file is compulsory, so that a possibly sensitive
    // data won't get open simply by accidentally removing the file.
    $auth_conf = json_decode($auth_conf_file, true);
    if (isset($_GET['auth_t']))
    {
      $key = pg_escape_string($_GET['auth_t']);

      // Find the session from the database
      $db_opts = get_db_options();
      $mongodb = connectMongoDB($db_opts['mongo_db_name']);
      $sessions = $mongodb->_sessions;
      $session = $sessions->findOne(array("_id" => $key), 
          array("_id" => false));
    }    
    if($auth_conf['open_data']) { // everybody can view open data
      $session['permissions']['view'] = true;
    }
  }

  return $session;
}
