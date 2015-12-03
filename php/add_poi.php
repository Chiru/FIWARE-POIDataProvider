<?php

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/

require_once 'db.php';
require_once 'data_manager.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' )
{
    $request_body = file_get_contents('php://input');
//     print $request_body;
    
    $request_array = json_decode($request_body, true);
    
    if ($request_array != NULL)
    {
//         print "JSON decoded succesfully!";
        
        $is_valid = validate_poi_data($request_array);
        if (!$is_valid)
        {
            header("HTTP/1.0 400 Bad Request");
            die ("POI data validation failed!");
        }
        
        $db_opts = get_db_options();
        $pgcon = connectPostgreSQL($db_opts["sql_db_name"]);
        $uuid_generate_query = "SELECT uuid_generate_v4()";
        $uuid_result = pg_query($uuid_generate_query);
        
        if (!$uuid_result)
        {
            header("HTTP/1.0 500 Internal Server Error");
            $error = pg_last_error();
            die($error);
        }
        $row = pg_fetch_row($uuid_result);
        $uuid = $row[0];
//         print "Generated UUID: ". $uuid;
        
        $supported_components = get_supported_components();
        $timestamp = time();
        
        //process fw_core component
        if ($request_array["fw_core"])
        {
            $description = NULL;
            $label = NULL;
            $url = NULL;
            $thumbnail = NULL;
            
//             print "fw_core found!";
            $fw_core = $request_array["fw_core"];
            
            if (!isset($fw_core['name']) or !isset($fw_core['categories']) or !isset($fw_core['location']))
            {
                die ("Error: 'name', 'categories' and 'location' are mandatory fields in fw_core!");
            }
            
            $categories = $fw_core['categories'];
            foreach($categories as &$category)
            {
                $category = pg_escape_string($category);
            }
            
            $pg_categories = "{". implode(",", $categories). "}";
            
            $location = $fw_core['location'];
            $lat = NULL;
            $lon = NULL;
            if ($location['wgs84'])
            {
                $lat = pg_escape_string($location['wgs84']['latitude']);
                $lon = pg_escape_string($location['wgs84']['longitude']);
            }
            if ($lat == NULL or $lon == NULL)
            {
                header("HTTP/1.0 400 Bad Request");
                die ("Failed to parse location: lat or lon is NULL!");
            }
            
            $fw_core_intl_tbl = $db_opts['fw_core_intl_table_name'];
            update_fw_core_intl_properties($pgcon, $fw_core_intl_tbl, $uuid, $fw_core);

            if (isset($fw_core['thumbnail']))
                $thumbnail = pg_escape_string($fw_core['thumbnail']);
            
            if (isset($fw_core['source']))
            {
                $src = $fw_core['source'];
                if (isset($src['name']))
                    $source_name = pg_escape_string($src['name']);
                if (isset($src['website']))
                    $source_website = pg_escape_string($src['website']);
                if (isset($src['id']))
                    $source_id = pg_escape_string($src['id']);
                if (isset($src['license']))
                    $source_license = pg_escape_string($src['license']);
            }
            
            $fw_core_tbl = $db_opts['fw_core_table_name'];
            $insert = "INSERT INTO $fw_core_tbl (uuid, categories, location, thumbnail, timestamp, source_name, source_website, source_license, source_id) " . 
            "VALUES('$uuid', '$pg_categories', ST_GeogFromText('POINT($lon $lat)'), '$thumbnail', $timestamp, '$source_name', '$source_website', '$source_license', '$source_id');";
            
            $insert_result = pg_query($insert);
            if (!$insert_result)
            {
                header("HTTP/1.0 500 Internal Server Error");
                echo "A database error has occured!";
                echo pg_last_error();
                exit;
            }
        }
        
        else
        {
            header("HTTP/1.0 400 Bad Request");
            die("'fw_core' not found, POI addition aborted!");
        }
        
        //Insert other components to MongoDB...
        $mongodb = connectMongoDB($db_opts['mongo_db_name']);
        foreach($request_array as $comp_name => $comp_data) 
        {
            if ($comp_name == 'fw_core')
                continue;
            if (in_array($comp_name, $supported_components))
            {
                $comp_data["_id"] = $uuid;
                // Set timestamp, if not prepared
                if (!isset($comp_data['last_update']))
                {
                    $comp_data['last_update'] = array();
                }
                $comp_data['last_update']['timestamp'] = $timestamp;
                

                $collection = $mongodb->$comp_name;
                $collection->insert($comp_data);
            }
        }
        
        $new_poi_info = array();
        $new_poi_info['uuid'] = $uuid;
        $new_poi_info['timestamp'] = $timestamp;
        $ret_val_arr = array("created_poi" => $new_poi_info);
        $ret_val = json_encode($ret_val_arr);
            
        header("Access-Control-Allow-Origin: *");
        print $ret_val;
    }
    
    else
    {
        header("HTTP/1.0 400 Bad Request");
        die("Error decoding request payload as JSON!");
    }
    
}

else if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: POST, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

else {
     header("HTTP/1.0 400 Bad Request");
     echo "You must use HTTP POST for adding a new POI!";
     return;
}

?>
