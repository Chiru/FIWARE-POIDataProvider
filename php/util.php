<?php

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/

require_once 'data_manager.php';

function handle_common_search_params()
{
    $params = array();
    
    $params['max_results'] = 9999;
    $params['components'] = get_supported_components();
    
    if (isset($_GET['category']))
    {
        $category = $_GET['category'];
        $esc_categories = escape_csv($category, "\"");
        $params['categories'] = $esc_categories;
    }
  
    if (isset($_GET['component']))
    {
        $component = $_GET['component'];
        $esc_components = pg_escape_string($component);
        $components = explode(",", $esc_components);
        $params['components'] = $components;
        
    }
  
    if (isset($_GET['max_results']))
    {
        $max_res = $_GET['max_results'];
        if (!is_numeric($max_res))
        {
            header("HTTP/1.0 400 Bad Request");
            die("'max_results' must be a positive integer value!");
        }
        $max_results = intval($max_res);
        if ($max_results < 1)
        {
            header("HTTP/1.0 400 Bad Request");
            die("'max_results' must be a positive integer value!");
        }
        $params['max_results'] = $max_results;
    }
    
    if (isset($_GET['begin_time']) and isset($_GET['end_time']))
    {
        $min_minutes = 1;  //Default value
        if (isset($_GET['min_minutes']))
        {
            $min_minutes = $_GET['min_minutes'];
            if (!is_numeric($min_minutes))
            {
                header("HTTP/1.0 400 Bad Request");
                die("'min_minutes' must be a positive integer value!");
            }
            
            $min_minutes = intval($min_minutes);
            
            if ($min_minutes < 1)
            {
                header("HTTP/1.0 400 Bad Request");
                die("'min_minutes' must be a positive integer value!");
            }
        }
        $params['min_minutes'] = $min_minutes;
        
        if (isset($_GET['schedule']))
        {
            $schedule_json = $_GET['schedule'];
            $schedule = json_decode($schedule_json);
            if ($schedule == NULL)
            {
                header("HTTP/1.0 400 Bad Request");
                die("JSON decoding failed for 'schedule'. Is it valid JSON and properly url-encoded?");
            }
            
            //TODO: Validate the schedule JSON against schema!
            
            $schedule_valid = validate_poi_data($schedule, 'schedule_schema_3.3.json');
            if (!$schedule_valid)
            {
                header("HTTP/1.0 400 Bad Request");
                die("'schedule' does not validate against JSON schema!");
            }
            
            $schedule = json_decode($schedule_json, true);
            
            $params['schedule'] = $schedule;
        }
        
        $begin_time = $_GET['begin_time'];
        $end_time = $_GET['end_time'];
        $begin_time_obj = date_parse($begin_time);
        $end_time_obj = date_parse($end_time);
        
        if ($begin_time_obj['error_count'] != 0) {
            header("HTTP/1.0 400 Bad Request");
            die("Error parsing 'begin_time'!");
        }
        
        if ($end_time_obj['error_count'] != 0) {
            header("HTTP/1.0 400 Bad Request");
            die("Error parsing 'end_time'!");
        }
        
        $params['begin_time'] = $begin_time_obj;
        $params['end_time'] = $end_time_obj;
    }
    
    return $params;
}
    
function escape_csv($csv_string, $quote_type)
{
    $esc_str = pg_escape_string($csv_string);
    $str_values = explode(",", $esc_str);
    foreach ($str_values as &$val)
    {
        $val = $quote_type.$val.$quote_type;
    }
    $esc_csv = implode(",", $str_values);
    return $esc_csv;
}  

function parse_accept_language($accept_language_str)
{
    if ($accept_language_str == null or $accept_language_str == "")
        return array();
    $accept_language_str = str_replace(" ", "", $accept_language_str);
    $langs = array();

    $splits = explode(",", $accept_language_str);
    foreach($splits as $split)
    {
        if (strpos($split, ";") == false)
            $langs[$split] = 1;
        else
        {
            $lang_q = explode(";q=", $split);
            $langs[$lang_q[0]] = floatval($lang_q[1]);
        }
    }        
    arsort($langs, SORT_NUMERIC);
    
//     var_dump($langs);
    return $langs;
}
/*
  array_merge_r - recursive array merge
  
  Replaces if same keys.
  
  According to original idea by Daniel Smedegaard Buus
    http://danielsmedegaardbuus.dk/2009-03-19/phps-array_merge_recursive-as-it-should-be/
  
*/
function &array_merge_r(array &$array1, &$array2 = null)
{
  $merged = $array1;
  
  if (is_array($array2)) {
    foreach ($array2 as $key => $val) {
      if (is_array($val)) {
        $merged[$key] = (isset($merged[$key]) && is_array($merged[$key])) ? 
            array_merge_r($merged[$key], $val) : $val;
      } else {
        $merged[$key] = $val;
      }
    }
  }
  return $merged;
}

// This does not overwrite numeric keys
function &array_merge_r2(array &$array1, &$array2 = null)
{
  $merged = $array1;
  
  if (is_array($array2)) {
    foreach ($array2 as $key => $val) {
      if (is_array($val)) {
        $item = (isset($merged[$key]) && is_array($merged[$key])) ? 
            array_merge_r2($merged[$key], $val) : $val;
      } else {
        $item = $val;
      }
      if (is_int($key)) {
        array_push($merged, $item);
      } else {
        $merged[$key] = $item;
      }
    }
  }
  return $merged;
}
?>
