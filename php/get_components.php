<?php

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/

//require_once 'db.php';
require_once 'data_manager.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET' )
{
    $components = get_supported_components();

    $json_struct = array("components" => $components);
    $return_val = json_encode($json_struct);

    header("Content-type: application/json");
    header("Access-Control-Allow-Origin: *");
    echo $return_val;
}

else if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

else {
     header("HTTP/1.0 400 Bad Request");
     echo "You must use HTTP GET for getting supported data components!";
     return;
}

?>
