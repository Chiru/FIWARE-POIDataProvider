<?php

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENCE
*/

require 'db.php';
require 'data_manager.php';

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
            $description = NULL;
            $label = NULL;
            $url = NULL;
            $thumbnail = NULL;
            $source_name = NULL;
            $source_website = NULL;
            $source_id = NULL;
            $source_licence = NULL;
            
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
            
            $fw_core_intl_tbl = $db_opts['fw_core_intl_table_name'];

            update_fw_core_intl_properties($pgcon, $fw_core_intl_tbl, $uuid, $fw_core);
                      
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
            
//             $update = "UPDATE $fw_core_tbl SET name='$name', category='$category', location=ST_GeogFromText('POINT($lon $lat)'), description='$description', " .
//             "label='$label', url='$url', thumbnail='$thumbnail', timestamp=$new_timestamp WHERE uuid='$uuid';";

            $update = "UPDATE $fw_core_tbl SET categories='$pg_categories', location=ST_GeogFromText('POINT($lon $lat)'), " .
            "thumbnail='$thumbnail', timestamp=$new_timestamp, source_name='$source_name', source_website='$source_website', " .
            "source_id='$source_id', source_license='$source_license' WHERE uuid='$uuid';";
            
            $update_result = pg_query($update);
            if (!$update_result)
            {
                echo "*ERROR: A database error has occured!\n";
                echo "  " . pg_last_error() . "\n";
                exit;
            }
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

else {
     header("HTTP/1.0 400 Bad Request");
     die("You must use HTTP POST for updating data!");
}

?>
