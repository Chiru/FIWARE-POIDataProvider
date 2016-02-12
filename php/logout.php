<?php // logout.php 5.1.3.1 2016-01-25 ariokkon

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, 
* All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
define('SERVICE_NAME', 'logout');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET' )
{
  if (isset($_GET['auth_t']))
  {
    $token_sha1 = pg_escape_string($_GET['auth_t']);
    // Remove session from database
    $db_opts = get_db_options();
    $mongodb = connectMongoDB($db_opts['mongo_db_name']);
    $sessions = $mongodb->_sessions;
    $sessions->remove(array("_id" => $token_sha1));

    echo 'Logged out';
  } else {
    echo 'Nothing go do';
  }
}
?>
