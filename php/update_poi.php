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
        $new_timestamp = time();
        $uuid = pg_escape_string(key($request_array));
        $poi_data = $request_array[$uuid];
        
        $is_valid = validate_poi_data($poi_data);
        if (!$is_valid)
        {
            header("HTTP/1.0 400 Bad Request");
            die ("POI data validation failed!");
        }
        
        
        $db_opts = get_db_options();
        $pgcon = connectPostgreSQL($db_opts["sql_db_name"]);
        $fw_core_tbl = $db_opts['fw_core_table_name'];
        $uuid_exists_query = "SELECT count(*) FROM $fw_core_tbl WHERE uuid='".$uuid."'";
        $uuid_exists_result = pg_query($uuid_exists_query);
            
        if (!$uuid_exists_result)
        {
            header("HTTP/1.0 500 Internal Server Error");
            $error = pg_last_error();
            die($error);
        }
        
        $row = pg_fetch_row($uuid_exists_result);
        $uuid_exists = $row[0];
        if ($uuid_exists != 1)
        {
            header("HTTP/1.0 400 Bad Request");
            die("The specified UUID was not found!");
        }
        
        //process fw_core component
        if ($poi_data["fw_core"])
        {
           
//             print "fw_core found!";
            $fw_core = $poi_data["fw_core"];
            
            if (!isset($fw_core['name']) or !isset($fw_core['categories']) or !isset($fw_core['location']))
            {
                die ("Error: 'name', 'categories' and 'location' are mandatory fields in fw_core!");
            }
            
            $update_timestamp = 0;
            
            if (isset($fw_core['last_update']))
            {
                $last_update = $fw_core['last_update'];
                if (isset($last_update['timestamp']))
                {
                    $update_timestamp = intval($last_update['timestamp']);
                }
                    
            }
            
            if ($update_timestamp == 0)
            {
                header("HTTP/1.0 400 Bad Request");
                die("No valid 'last_update:timestamp' value was found for 'fw_core' component!");
            }
            
            //....
            $curr_timestamp_query = "SELECT timestamp FROM $fw_core_tbl WHERE uuid='".$uuid."'";
            $curr_timestamp_result = pg_query($curr_timestamp_query);
        
            if (!$curr_timestamp_result)
            {
                header("HTTP/1.0 500 Internal Server Error");
                $error = pg_last_error();
                die($error);
            }
        
            $row = pg_fetch_row($curr_timestamp_result);
            $curr_timestamp = $row[0];
            if ($curr_timestamp != NULL)
            {
                if ($curr_timestamp != $update_timestamp) {
                    header("HTTP/1.0 400 Bad Request");
                    die("The given last_update:timestamp (". $update_timestamp .") does not match the value in the database (". $curr_timestamp .") in fw_core!");
                }
                
            }


            update_fw_core($db_opts, $pgcon, $uuid, $fw_core, $fw_core_tbl, $new_timestamp);

        }
        
        $supported_components = get_supported_components();
        
        //Update other components to MongoDB...
        $mongodb = connectMongoDB($db_opts['mongo_db_name']);
        foreach($poi_data as $comp_name => $comp_data) 
        {
            //Skip fw_core as it has been allready processed...
            if ($comp_name == "fw_core")
            {
                continue;
            }
            
            //Process update for fw_relationships. 
            //Each relationship object is stored as a single document in MongoDB,
            //identified by MongoDB's internal identifier (ObjectID).
            if ($comp_name == "fw_relationships")
            {
                foreach($comp_data as $relationship)
                {
                    //Relationships are identified by the pair (subject, predicate)
                    $subj = $relationship['subject'];
                    $pred = $relationship['predicate'];
                    $pred_type = key($pred);
                    $pred_value = $pred[$pred_type];
                    $rel_id = "";
                    
                    if ($subj != $uuid)
                    {
                        header("HTTP/1.0 400 Bad Request");
                        die("The subject does match POI UUID ($uuid) in relationship ($subj, $pred_type:$pred_value) !");
                    }
                    
                    $rel_collection = $mongodb->fw_relationships;
                    $existing_rel = $rel_collection->findOne(array("subject" => $uuid, "predicate" => $pred));
                    if ($existing_rel != NULL)
                    {
                        $rel_id = $existing_rel['_id'];
                        $update_timestamp = 0;
                        
                        if (isset($relationship['last_update']))
                        {
                            $last_update = $relationship['last_update'];
                            if (isset($last_update['timestamp']))
                            {
                                $update_timestamp = intval($last_update['timestamp']);
                            }
                        }
                        
                        if ($update_timestamp == 0)
                        {
                            header("HTTP/1.0 400 Bad Request");
                            die("No valid 'last_update:timestamp' value was found for relationship ($subj, $pred_type:$pred_value) !");
                        }
                        
                        if (isset($existing_rel['last_update']))
                        {
                            if (isset($existing_rel['last_update']['timestamp']))
                            {
                                $curr_timestamp = $existing_rel['last_update']['timestamp'];
                                if ($curr_timestamp != $update_timestamp) {
                                    header("HTTP/1.0 400 Bad Request");
                                    die("The given last_update:timestamp (". $update_timestamp .") does not match the value in the database (". $curr_timestamp .") in relationship ($subj, $pred_type:$pred_value) !");
                                }
                            }
                        }
                    }
                    
                    if (!isset($relationship['last_update']))
                    {
                        $relationship['last_update'] = array();
                    }
                    $relationship['last_update']['timestamp'] = $new_timestamp;
                    
                    if ($rel_id != "")
                    {
                        $relationship["_id"] = $rel_id;
                    }
                    $upd_criteria = array("subject" => $uuid, "predicate" => $pred);
                    $rel_collection->update($upd_criteria, $relationship, array("upsert" => true));
                }
                
                continue;
                
            }
            
            if (in_array($comp_name, $supported_components))
            {
                $collection = $mongodb->$comp_name;
                
                $existing_component = getComponentMongoDB($mongodb, $comp_name, $uuid, true);
                if ($existing_component != NULL)
                {
                    $update_timestamp = 0;
                    $curr_timestamp = 0;
                    
                    if (isset($comp_data['last_update']))
                    {
                        $last_update = $comp_data['last_update'];
                        if (isset($last_update['timestamp']))
                        {
                            $update_timestamp = intval($last_update['timestamp']);
                        }
                    }
                    
                    
                    if (isset($existing_component['last_update']))
                    {
                        if (isset($existing_component['last_update']['timestamp']))
                        {
                            $curr_timestamp = $existing_component['last_update']['timestamp'];
                        }   
                    }
                    if ($curr_timestamp != $update_timestamp) {
                        header("HTTP/1.0 400 Bad Request");
                        if ($update_timestamp == 0)
                        {
                            die("No valid 'last_update:timestamp' value was ' .
                                'found for '$comp_name' component!");
                        } else {
                            die("The given last_update:timestamp (". 
                                $update_timestamp .") does not match the ' .
                                'value in the database (". $curr_timestamp .
                                ") in $comp_name!");
                        }
                    }
                }                
                
                if (!isset($comp_data['last_update']))
                {
                    $comp_data['last_update'] = array();
                }
                $comp_data['last_update']['timestamp'] = $new_timestamp;
                
                $comp_data["_id"] = $uuid;               
                $upd_criteria = array("_id" => $uuid);
                try {
                $collection->update($upd_criteria, $comp_data, array("upsert" => true));
                } catch(Exception $e) {
                  echo "*ERROR: Exception when updating " . $uuid . "." . $comp_name . "\n";
                  echo "  message: " . $e->getMessage() . "\n";
                  echo "  code: " . $e->getCode() . "\n";
                  
                }
                  
            } else echo "*ERROR: Data component " . $comp_name . " not supported\n";
        }
        
        header("Access-Control-Allow-Origin: *");
        print "POI data updated succesfully!";
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
     die("You must use HTTP POST for updating data!");
}

?>
