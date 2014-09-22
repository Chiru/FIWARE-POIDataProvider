<?php

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENCE
*/

require 'db.php';
require 'util.php';

$components = get_supported_components();

if (isset ($_GET['poi_id']))
{
    $poi_id = $_GET['poi_id'];
    $esc_ids = escape_csv($poi_id, "'");
    
    if (isset($_GET['component']))
    {
        $component = $_GET['component'];
        $esc_components = pg_escape_string($component);
        $components = explode(",", $esc_components);
    }
    
    $fetch_for_update = false;
    if (isset($_GET['fetch_for_update']))
    {
        if ($_GET['fetch_for_update'] == "true")
        {
            $fetch_for_update = true;
        }
    }

    $data = array();
    $esc_ids_arr = explode(",", $esc_ids);
    foreach($esc_ids_arr as $poi_uuid)
    {
        $poi_uuid = str_replace("'", "", $poi_uuid);
        $data[$poi_uuid] = array();
    }
    
    $db_opts = get_db_options();
    
    //Include fw_core in result data...
    if (in_array("fw_core", $components))
    {
        
        $pgcon = connectPostgreSQL($db_opts["sql_db_name"]);
        $fw_core_tbl = $db_opts['fw_core_table_name'];
        
        $query = "SELECT uuid, array_to_string(categories, ',') as categories, thumbnail, st_x(location::geometry) as lon, st_y(location::geometry) as lat, st_astext(geometry) as geometry, timestamp, " .
            "source_name, source_website, source_id, source_license FROM $fw_core_tbl WHERE uuid IN ($esc_ids)";

        $core_result = pg_query($query);
        
        if (!$core_result)
        {
            header("HTTP/1.0 500 Internal Server Error");
            $error = pg_last_error();
            die($error);
        }
        
        $core_json_struct = fw_core_pgsql2array($core_result, TRUE);
        
        $core_pois = $core_json_struct['pois'];
        $fw_core_intl_tbl = $db_opts['fw_core_intl_table_name'];
        foreach ($core_pois as $core_poi_uuid => $fw_core_content)
        {
            $poi_data = get_fw_core_intl_properties_for_poi($pgcon, $fw_core_intl_tbl, $core_poi_uuid, $fw_core_content);
            
            $data[$core_poi_uuid] = $poi_data;
        }
    }
    
    //Handle other components from MongoDB...
    
    $mongodb = connectMongoDB($db_opts['mongo_db_name']);
    
    foreach ($components as $component)
    {
        //skip fw_core, as it hase been already handled...
        if ($component == "fw_core")
        {
            continue;
        }
        
        foreach(array_keys($data) as $uuid)
        {
                       
//             print $uuid;
            $comp_data = getComponentMongoDB($mongodb, $component, $uuid, $fetch_for_update);
            if ($comp_data != NULL)
            {
                $data[$uuid][$component] = $comp_data;
            }
        }
    }
    
    $pois_data = array("pois" => $data);
    
    $get_for_update = false;
    
    if (isset ($_GET['get_for_update']))
    {
        if ($_GET['get_for_update'] == "true")
        {
            $get_for_update = true;
        }
    }
    if (!$get_for_update)
    {
        $accept_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $langs = parse_accept_language($accept_lang);  
        filter_poi_intl_properties($pois_data, array_keys($langs));
        
    }
    
    $return_val = json_encode($pois_data);
    header("Content-type: application/json");
    header("Access-Control-Allow-Origin: *");
    echo $return_val;
    
}

else {
    header("HTTP/1.0 400 Bad Request");
    echo "'poi_id' parameter must be specified!";
    return;
}


?>