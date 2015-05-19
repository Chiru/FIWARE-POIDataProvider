<?php

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/

require 'db.php';

$components = get_supported_components();

$json_struct = array("components" => $components);
$return_val = json_encode($json_struct);

header("Content-type: application/json");
header("Access-Control-Allow-Origin: *");
echo $return_val;

?>