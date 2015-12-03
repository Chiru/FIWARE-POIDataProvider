<?php

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
define('SERVER_VERSION', 'CIE dynamic 2');
define('SERVER_NAME', 'get_pois');

define('DEFAULT_DYN_DATA_VALID_TIME', 60); // used, if fw_dynamic.valid_duration
                                           // is not defined

require_once 'db.php';
require_once 'util.php';
require_once 'get_dyn_pois.php';
require_once 'data_manager.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET' )
{
    $components = get_supported_components();

    $dlog = array(); // string array for debug tracing
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
                try {
                    $comp_data = getComponentMongoDB($mongodb, $component, $uuid, $fetch_for_update);
                } catch(Exception $e) {
                    echo "*ERROR: Exception when getting " . $uuid . "." . $comp_name . "\n";
                    echo "  message: " . $e->getMessage() . "\n";
                    echo "  code: " . $e->getCode() . "\n";
              
                }
                if ($comp_data != NULL)
                {
                    $data[$uuid][$component] = $comp_data;
                }
            }
        }
        
        /* Include dynamic data */
        $new_timestamp = time();
        $supported_components = get_supported_components();
        
        foreach(array_keys($data) as $uuid)
        {
          $comps_to_update = array(); // is used to collect the list of names of
                                      // components to update in the database
          if (isset($data[$uuid]['fw_dynamic'])) { // NOTE: Must be written this 
              // way. Taking a reference of a non-existent array item creates the
              // item and sets it to null.
            $fw_dynamic = &$data[$uuid]['fw_dynamic']; // Must not be assigned 
                                                       // before isset test!
            $dyn_comp_names = $fw_dynamic['components']; // list of components
                                // allowed to be updated
            if (!isset($dyn_comp_names)) { // initialize, if not set
              $dyn_comp_names = get_dyn_fields($fw_dynamic); // fields from conf.
              $fw_dynamic['components'] = $dyn_comp_names;
              $comps_to_update = array('fw_dynamic');
            }
            
            $old_timestamp = 0;
            $valid_duration = DEFAULT_DYN_DATA_VALID_TIME;
            // fw_dynamic._ctrl.timestamp contains last dynamic update time
            // of this POI.
            if (isset($fw_dynamic['_ctrl'])) {
              $old_timestamp = $fw_dynamic['_ctrl']['timestamp'];
              if (!isset($old_timestamp)) $old_timestamp = 0;
            }
            if (isset($fw_dynamic['valid_duration'])) {
              $valid_duration = $fw_dynamic['valid_duration'];
            }
            
            $dynamic_data_has_expired = 
                $new_timestamp > ($old_timestamp + $valid_duration);

            if ( $dynamic_data_has_expired ) {  
              $fw_dynamic['_ctrl']['timestamp'] = $new_timestamp;
              $dyn_data = get_dyn_pois($fw_dynamic); // obtain external dynamic data
              // Avoid garbling dynamic definitions!
              if (isset($dyn_data['fw_dynamic'])) unset($dyn_data['fw_dynamic']);
              // merge dynamic data to static data
              $data[$uuid] = array_merge_r($data[$uuid], $dyn_data);
              // fw_dynamic must be updated due to _ctrl.timestamp
              if ( !in_array('fw_dynamic', $comps_to_update)) {
                $comps_to_update[] = 'fw_dynamic';
              }
              // Update dynamic data in the POI data
              $comps_to_update = array_merge($comps_to_update, array_keys($dyn_data));
            } // end if data expired
              
            foreach($comps_to_update as $comp_name)
            {
              if(in_array($comp_name, $dyn_comp_names) || 
                  ($comp_name == 'fw_dynamic')) {
                if($comp_name == "fw_core") {
                  // fw_core has changed
                  // However, don't update, if the place is lost
                  if(isset($dyn_data['fw_core']['location'])){
                    $location = $dyn_data['fw_core']['location'];
                    if(isset($location['wgs84'])) {
                      $lat = $location['wgs84']['latitude'];
                      $lon = $location['wgs84']['longitude'];
                      if (isset($lat) && isset($lon) && ($lat != 0) &&
                          ($lon != 0)) {
                        update_fw_core($db_opts, $pgcon, $uuid, 
                            $data[$uuid]['fw_core'], $fw_core_tbl, $new_timestamp);
                      } 
                    }
                  }
                } else if ($comp_name == "fw_relationships") {
                  // do nothing            
                } else {

                  if (in_array($comp_name, $supported_components))
                  {
                    $collection = $mongodb->$comp_name;
                    $comp_data = $data[$uuid][$comp_name];
                    $comp_data['last_update']['timestamp'] = $new_timestamp;
                    $data[$uuid][$comp_name]['last_update']['timestamp'] = 
                        $new_timestamp;
                    $comp_data["_id"] = $uuid;
                    $upd_criteria = array("_id" => $uuid);
                    try {
                      $collection->update($upd_criteria, $comp_data, array("upsert" => true));
                    } catch(Exception $e) {
                      echo "*ERROR: Exception when updating " . $uuid . "." . $comp_name . "\n";
                      echo "  message: " . $e->getMessage() . "\n";
                      echo "  code: " . $e->getCode() . "\n";
                    }
                  }
                }
              }
            } // end for each dyn_data component
          } // end if dynamic POI
        } // end for each requested POI
    
    
    
    
        $pois_data = array("pois" => $data);
        $pois_data['server_info'] = array();
        $pois_data['server_info']['version'] = SERVER_NAME . ' ' . SERVER_VERSION;
  // Uncomment, if needed
  //    $pois_data['server_info']['log'] = $dlog;
        
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
     echo "You must use HTTP GET for getting POI data!";
     return;
}


?>
