<?php

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/

require_once 'db.php';
require_once 'util.php';


if ($_SERVER['REQUEST_METHOD'] == 'GET' )
{
    if (isset ($_GET['north']) and isset ($_GET['south']) and isset ($_GET['east']) and isset ($_GET['west']))
    {
        $north = $_GET['north'];
        $south = $_GET['south'];
        $east = $_GET['east'];
        $west = $_GET['west'];
                
        if (!is_numeric($north) or !is_numeric($south) or !is_numeric($east) or !is_numeric($west))
        {
            header("HTTP/1.0 400 Bad Request");
            echo "'north' and 'south' and 'east' and 'west' must be numeric values!";
            return;
        }
        
        if ($east < -180 or $east > 180 or $west < -180 or $west > 180 or $north < -90 or $north > 90 or $south < -90 or $south > 90)
        {
            header("HTTP/1.0 400 Bad Request");
            die("Coordinate values are out of range: east and west [-180, 180], north and south [-90, 90]");
        }
    
        $common_params = handle_common_search_params();
        $db_opts = get_db_options();
        $pgcon = connectPostgreSQL($db_opts["sql_db_name"]);
        $fw_core_tbl = $db_opts['fw_core_table_name'];
        
        if (isset($common_params['categories']))
        {
            $query = "SELECT uuid, array_to_string(categories, ',') as categories, thumbnail, st_x(location::geometry) as lon, st_y(location::geometry) as lat, st_astext(geometry) as geometry, timestamp, " .
            "source_name, source_website, source_id, source_license " .
            "FROM $fw_core_tbl WHERE ST_Intersects(ST_Geogfromtext('POLYGON(($west $south, $east $south, $east $north, $west $north, $west $south))'), location) " .
            "AND categories && '{" . $common_params['categories'] . "}' LIMIT " . $common_params['max_results'];
        }
        
        else {
            $query = "SELECT uuid, array_to_string(categories, ',') as categories, thumbnail, st_x(location::geometry) as lon, st_y(location::geometry) as lat, st_astext(geometry) as geometry, timestamp, " .
            "source_name, source_website, source_id, source_license " .
            "FROM $fw_core_tbl WHERE ST_Intersects(ST_Geogfromtext('POLYGON(($west $south, $east $south, $east $north, $west $north, $west $south))'), location) LIMIT " . $common_params['max_results'];
        }
    //     echo "<br>" . $query;

        $core_result = pg_query($query);
        
        if (!$core_result)
        {
            header("HTTP/1.0 500 Internal Server Error");
            $error = pg_last_error();
            die($error);
        }
        
        $incl_fw_core = FALSE;
        if (in_array("fw_core", $common_params['components']))
        {
            $incl_fw_core = TRUE;
        }
    
        $json_struct = fw_core_pgsql2array($core_result, $incl_fw_core);

        if ($incl_fw_core)
        {
            $fw_core_intl_tbl = $db_opts['fw_core_intl_table_name'];
            foreach ($json_struct['pois'] as $core_poi_uuid => $fw_core_content)
            {
                $poi_data = get_fw_core_intl_properties_for_poi($pgcon, $fw_core_intl_tbl, $core_poi_uuid, $fw_core_content);
                    
                $json_struct['pois'][$core_poi_uuid] = $poi_data;
            }
        }
        
        $mongodb = connectMongoDB($db_opts['mongo_db_name']);
        
        //Time constraints based filtering
        if (isset($common_params['begin_time']) and isset($common_params['end_time']) and isset($common_params['min_minutes']))
        {
            $begin_time = $common_params['begin_time'];
            $end_time = $common_params['end_time'];
            
            foreach(array_keys($json_struct["pois"]) as $uuid)
            {
        
                $fw_time = getComponentMongoDB($mongodb, "fw_time", $uuid, false);
                
                //Remove POI from $json_struct as it does not contain fw_time...
                if ($fw_time == NULL)
                {
                    unset($json_struct["pois"][$uuid]);
                    continue;
                }
                
                $schedule = $fw_time['schedule'];
                
                //If schedule given as a search parameter, combine it with POIs schedule
                //using 'and' operator
                if (isset($common_params['schedule']))
                {
                    $schedule = array("and" => array($schedule, $common_params['schedule']));
                }

                $res_begintime = array();
                $res_endtime = array();
                $start_event = array($begin_time['year'], $begin_time['month'], $begin_time['day'], $begin_time['hour'], $begin_time['minute'], $begin_time['second']);
                $end_limit = array($end_time['year'], $end_time['month'], $end_time['day'], $end_time['hour'], $end_time['minute'], $end_time['second']);
                $result = find_open_time($schedule, $common_params['min_minutes']*60, $start_event, $end_limit, $res_begintime, $res_endtime);

                //Filter POIs from $json_struct that do not fulfill the time constraints...
                if ($result == False)
                {
                    unset($json_struct["pois"][$uuid]);
                }
                
            }
        }
        
        //Handle other components from MongoDB...
        foreach ($common_params['components'] as $component)
        {
            //skip fw_core, as it hase been already handled...
            if ($component == "fw_core")
            {
                continue;
            }
            foreach(array_keys($json_struct["pois"]) as $uuid)
            {
    //             print $uuid;
                $comp_data = getComponentMongoDB($mongodb, $component, $uuid, false);
                if ($comp_data != NULL)
                {
                    $json_struct["pois"][$uuid][$component] = $comp_data;
                }
            }
            
        }    
        
        //Language filtering
        $accept_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $langs = parse_accept_language($accept_lang);  
        filter_poi_intl_properties($json_struct, array_keys($langs));
        
        $return_val = json_encode($json_struct);
        header("Content-type: application/json");
        header("Access-Control-Allow-Origin: *");
        echo $return_val;
    }

    else {
        header("HTTP/1.0 400 Bad Request");
        echo "'north' and 'south' and 'east' and 'west' parameters must be specified!";
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
     echo "You must use HTTP GET for searching POIs!";
     return;
}

?>
