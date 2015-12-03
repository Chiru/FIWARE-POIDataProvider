<?php

// Dependency: JSON Schema validator, 
// https://github.com/justinrainbow/json-schema
require_once 'vendor/autoload.php';

// Read and process the schema only once to globals
$supported_components = array(); // set in load_poi_schema() as side effect
$poi_schema = load_poi_schema();
$intl_properties = find_intl_properties($poi_schema->properties);

function get_supported_components()
{
    global $supported_components;
    
    return $supported_components;
}

function validate_poi_data($poi_data)
{
    global $poi_schema;
    $result = false;
    
//=    $poi_schema = load_poi_schema();
    
    $intl_props = find_intl_properties($poi_schema->properties);
    
    $temp = json_encode($poi_data);
    $poi_data = json_decode($temp);
    
    
    // Validate
    $validator = new JsonSchema\Validator();
    $validator->check($poi_data, $poi_schema);

    if ($validator->isValid()) {
        $result = true;
    } else {
        echo "JSON does not validate. Violations:\n";
        foreach ($validator->getErrors() as $error) {
            echo sprintf("[%s] %s\n", $error['property'], $error['message']);
        }
    }
    
    return $result;
}

//Loads the POI schema from file to a PHP object structure
function load_poi_schema($poi_schema_file = 'poi_schema_3.5.json')
{
    global $supported_components;
    
    $retriever = new JsonSchema\Uri\UriRetriever;
    $poi_schema = $retriever->retrieve('file://' . realpath($poi_schema_file));
    $supported_components = array_keys(
        get_object_vars($poi_schema->properties));
    
    $refResolver = new JsonSchema\RefResolver($retriever);
    $refResolver->resolve($poi_schema, 'file://' .realpath($poi_schema_file));
    
    return $poi_schema;
}

//This function finds all the 'internationalized' i.e. multilingual properties from the given POI JSON schema
function find_intl_properties($schema_obj, $base_path = '')
{
    $intl_properties = array();
    foreach($schema_obj as $key => $value)
    {
        if ($key == "schedule")
        {
            continue;
        }
        if ($key != "properties")
        {
            //Denote array in the path with a "*"
            if ($key == "items")
            {
                $path = $base_path . "*";
            }
            else
            {
                $path = $base_path . $key;
            }
        }
        else
        {
            $path = $base_path;
        }
        if (isset($value->id) and gettype($value->id) == "string")
        {
            //if ($value->title == "Internationalized string" or $value->title == "Internationalized URI")
            if (strpos($value->id, "intl_string") != FALSE or strpos($value->id, "intl_uri") != FALSE)
            {
               $intl_properties[] = $path;
               continue;
            }
        }
        
        if (is_object($value))
        {
            if (substr($path, -1) != ".")
            {
                $path = $path . ".";
            }
            $intl_p = find_intl_properties($value, $path);
            $intl_properties = array_merge($intl_properties, $intl_p);
        }
    }
    
    return $intl_properties;
    
}

//This function returns the value of the attribute in $path in a multidimensional
//associative array structure.
//$path should be a string, where key names are separated with a dot: "fw_core.name"
//A normal array in the path is denoted with a "*", 
//e.g. "fw_media.entities.*.short_label" where "entities" is an array containing 
//unnamed objects containing an attribute "short_label"
function get_arr_value_by_path($array, $path)
{
    $found_values = array();
    $curr_node = $array;
    $path_elems = explode(".", $path);
    $remaining_path = $path;
    foreach($path_elems as $elem)
    {
        $remaining_path = substr($remaining_path, strlen($elem)+1);
        
        if ($elem == "*")
        {
            foreach($curr_node as $arr_item)
            {
                $values = get_arr_value_by_path($arr_item, $remaining_path);
                
                if (is_array($values))
                {
                    $found_values = array_merge($found_values, $values);
                }
                
            }
            break;
        }
        
        if (isset($curr_node[$elem]))
        {
            $curr_node = $curr_node[$elem];
            if (!is_array($curr_node) or $remaining_path == "")
            {
                $found_values[] = $curr_node;
            }
        }
        else
        {
            return NULL;
        }
    }
    return $found_values;
}

//This function returns a reference to the value of the attribute in $path 
//in a multidimensional associative array structure.
//$path should be a string, where key names are separated with a dot: "fw_core.name"
//A normal array in the path is denoted with a "*", 
//e.g. "fw_media.entities.*.short_label" where "entities" is an array containing 
//unnamed objects containing an attribute "short_label"
function &get_arr_ref_by_path(&$array, $path)
{
    $found_values = array();
    $path_elems = explode(".", $path);
    $path_elem = $path_elems[0];
    $remaining_path = substr($path, strlen($path_elem)+1);
    
    if ($path_elem == "*")
    {
        foreach($array as &$arr_item)
        {
            $values = &get_arr_ref_by_path($arr_item, $remaining_path);
            $found_values[] = &$values;
        }
    }
    
    else if (isset($array[$path_elem]))
    {
        if ($remaining_path == "")
        {
            $found_values[] = &$array[$path_elem];
        }
        else
        {
            $found_values = &get_arr_ref_by_path($array[$path_elem], $remaining_path);
        }
        
    }
    else
    {
        $ret_val = array();
        return $ret_val;
    }

    return $found_values;
}


//This function sets the value of the attribute in $path in a multidimensional
//associative array structure.
//$path should be a string, where key names are separated with a dot: "fw_core.name"
//A numerical array index is simply denoted with a number
//e.g. "fw_media.entities.0.short_label" where "entities" is an array containing 
//unnamed objects containing an attribute "short_label"
function set_arr_value_by_path(&$array, $path, $new_val)
{
    $path_elems = explode(".", $path);
    $path_elem = $path_elems[0];
    $remaining_path = substr($path, strlen($path_elem)+1);
        
    if (isset($array[$path_elem]))
    {
        if ($remaining_path == "")
        {
            $array[$path_elem] = $new_val;
        }
        else
        {
            set_arr_value_by_path($array[$path_elem], $remaining_path, $new_val);
        }
        
    }
    else
    {
    }
    
}


//Filters the internationalized properties of the given POIs
//given languages
function filter_poi_intl_properties(&$pois_data, $langs)
{
//*    global $poi_schema;
    global $intl_properties;
    $pois = &$pois_data['pois'];
//*    $schema = load_poi_schema();
//*    $intl_properties = find_intl_properties($schema->properties);
//*    $intl_properties = find_intl_properties($poi_schema->properties);
    
    foreach($pois as &$poi)
    {
        foreach($poi as &$poi_data_comp)
        {
            if (isset($poi_data_comp['last_update']))
                unset($poi_data_comp['last_update']);
        }
        foreach($intl_properties as $intl_prop)
        {
            $prop_values = get_arr_ref_by_path($poi, $intl_prop);
            foreach ($prop_values as &$prop_val)
            {
                $prop_val = filter_intl_string_by_langs($prop_val, $langs);
            }
        }
        
    }
    
}

function filter_intl_string_by_langs($text_intl, $langs) { // : string
/* 
    text_intl - internationalized string with language variants
    langs - array of accepted language codes in descending priority
            "*" for any language.
*/
    $result = null;
    $resstring = null;
    $deflang = ""; $i = ""; $reslang = "";
    $anylang = false;
  
  if($text_intl) {
    if(sizeof($langs) == 0) {
      $anylang = true;
    } else {
      for($i = 0; ($i < sizeof($langs)) && ($resstring == null); $i++) {
        $reslang = $langs[$i];
        $resstring = null;
        if (isset($text_intl[$reslang]))
            $resstring = $text_intl[$reslang];
        if($reslang == "*") $anylang = true;
      }
    }
    /*
      Now: we may have resstring, or anylang or neither
    */  
    if ($resstring == null) {
        $reslang = "";
        if (isset($text_intl[$reslang]))
            $resstring = $text_intl[$reslang];
    }
    if ($resstring == null) {
      if (isset($text_intl["_def"]))
          $deflang = $text_intl["_def"];
      if ($deflang) {
        $reslang = $deflang;
        if (isset($text_intl[$reslang]))
            $resstring = $text_intl[$reslang];
        if(!$anylang) $reslang = "";
      }
    }
    if (($resstring == null) and ($anylang == true)) {
      foreach($text_intl as $i => $val) {
        $resstring = $text_intl[$i];
        $reslang = $i;
        if ( $resstring != null) break;
      }
    }
  } 
  if($resstring != null) {
    $result = array();
    $result[$reslang] = $resstring;
  }
  return $result;
}


?>