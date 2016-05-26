<?php // radial_search.php 5.2.1.1 2016-01-28 ariokkon

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
define('SERVICE_NAME', 'radial_search');

require_once 'db.php';
require_once 'util.php';
require_once 'security.php';
include 'is_open.php';
include 'time_span.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET' )
{
  // finding POIs with radial search requires view_permission
  
  $session = get_session();
  $view_permission = $session['permissions']['view'];
/*  
  $auth_conf_file = file_get_contents('./auth_conf.json');
  if($auth_conf_file){
    // auth_conf.json file is compulsory, so that a possibly sensitive
    // data won't get open simply by accidentally removing the file.
    $auth_conf = json_decode($auth_conf_file, true);
    if($auth_conf['open_data']) {
      $view_permission = true;
    } else { // not open data, check credentials
      if (isset($_GET['auth_t']))
      {
        $auth_t = pg_escape_string($_GET['auth_t']);

        // Find the session from the database
        $db_opts = get_db_options();
        $mongodb = connectMongoDB($db_opts['mongo_db_name']);
        $sessions = $mongodb->_sessions;
        $session = $sessions->findOne(array("_id" => $auth_t), 
            array("_id" => false));
        $view_permission = $session['permissions']['view'];
      }      
      
    }
  }
*/
  $radius = 300;

  if (isset ($_GET['lat']) and isset ($_GET['lon']))
  {
    if ($view_permission) {
      $lat = $_GET['lat'];
      $lon = $_GET['lon'];
          
      if (!is_numeric($lat) or !is_numeric($lon))
      {
        header("HTTP/1.0 400 Bad Request");
        echo "'lat' and 'lon' must be numeric values!";
        return;
      }
      
      if ($lon < -180 or $lon > 180 or $lat < -90 or $lat > 90)
      {
        header("HTTP/1.0 400 Bad Request");
        die("Coordinate values are out of range [-180 -90, 180 90]");
      }
    
      if (isset($_GET['radius']))
      {
        $radius = $_GET['radius'];
        if (!is_numeric($radius))
        {
          header("HTTP/1.0 400 Bad Request");
          echo "'radius' must be a numeric value!";
          return;
        }
      }
    
      $common_params = handle_common_search_params();
      
      $db_opts = get_db_options();
      
      $pgcon = connectPostgreSQL($db_opts['sql_db_name']);
      
      $fw_core_tbl = $db_opts['fw_core_table_name'];
      
      if (isset($common_params['categories']))
      {
        $query = "SELECT uuid, array_to_string(categories, ',') as categories, thumbnail, st_x(location::geometry) as lon, st_y(location::geometry) as lat, st_astext(geometry) as geometry, timestamp, ".
        "source_name, source_website, source_id, source_license " .
        "FROM $fw_core_tbl WHERE ST_DWithin(location, ST_GeogFromText('POINT($lon $lat)'), $radius) AND categories && '{" . $common_params['categories'] . "}' LIMIT " . $common_params['max_results'];
      }
      
      else {
        $query = "SELECT uuid, array_to_string(categories, ',') as categories, thumbnail, st_x(location::geometry) as lon, st_y(location::geometry) as lat, st_astext(geometry) as geometry, timestamp, " .
        "source_name, source_website, source_id, source_license " .
        "FROM $fw_core_tbl WHERE ST_DWithin(location, ST_GeogFromText('POINT($lon $lat)'), $radius) LIMIT " . $common_params['max_results'];
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
    } else { // no view permission, show empty list
      $json_struct['pois'] = array();
    }
   
    $json_struct['service_info'] = get_service_info(SERVICE_NAME);
 
    
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
    echo "'lat' and 'lon' parameters must be specified!";
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