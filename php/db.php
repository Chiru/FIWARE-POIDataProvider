<?php

/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/

function update_fw_core($db_opts, $pgcon, $uuid, $fw_core, $fw_core_tbl, 
    $new_timestamp)
{
  $description = NULL;
  $label = NULL;
  $url = NULL;
  $thumbnail = NULL;
  $source_name = NULL;
  $source_website = NULL;
  $source_id = NULL;
  $source_license = NULL;
   
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

//Database options
function get_db_options()
{
    $options = array();
    $options["sql_db_name"] = "poidatabase";
    $options["fw_core_table_name"] = "fw_core";
    $options["fw_core_intl_table_name"] = "fw_core_intl";
    
    $options["mongo_db_name"] = "poi_db";
    
    return $options;
}

function get_fw_core_intl_property_names()
{
    $intl_props = array("name", "label", "description", "url");
    return $intl_props;
}

function connectPostgreSQL($db_name)
{
    $pgcon = pg_connect("dbname=".$db_name." user=gisuser");
    
    if (!$pgcon) {
        die("Error connecting to PostgreSQL database: " . $db_name);
    }
    
    return $pgcon;
}

function fw_core_pgsql2array($core_result, $incl_fw_core)
{
    $json_struct = array();
    $pois = array();
    
    while ($row = pg_fetch_assoc($core_result)) {
        //var_dump($row);
        $uuid = $row['uuid'];
        if ($uuid == NULL)
            continue;
        $poi = array();
        
        //fw_core component is included in the request...
        if ($incl_fw_core == TRUE) {
            $core_component = array();
            $core_component["location"] = array("wgs84" => array("latitude" => floatval($row['lat']), "longitude" => floatval($row['lon'])));
            $core_component["categories"] = explode(',', $row['categories']);
            
            if ($row['timestamp'] != NULL)
            {
//                 if ($row['userid'] != NULL) {
//                     $core_component['last_update'] = array('timestamp' => $row['timestamp'], 'user_id' => $row['userid']);
//                 }
//                 else {
                    $core_component['last_update'] = array('timestamp' => intval($row['timestamp']));
//                 }
            }
            
            foreach (array_keys($row) as $key)
            {
                #Skip these attributes, as they are handled differently
                if ($key == 'uuid' or $key == 'lat' or $key == 'lon' or $key == 'timestamp' or $key == 'userid' or $key == 'categories')
                    continue;
                
                if ($row[$key] != NULL)
                {
                    //process source_* attributes
                    if (substr($key, 0, 7) == "source_")
                    {
                        $src_attr = substr($key, 7);
                        if (!isset($core_component['source']))
                        {
                            $core_component['source'] = array();
                        }
                        $core_component['source'][$src_attr] = $row[$key];

                        continue;
                    }
                    
                    if ($key == 'name' or $key == 'label' or $key == 'description' or $key == 'url')
                    {
                        //$core_component[$key] = array("" => $row[$key]);
                        continue;
                    }
                    else
                    {
                        $core_component[$key] = $row[$key];
                    }
                }
            }
            
            $poi['fw_core'] = $core_component;
            $pois[$uuid] = $poi;
        }
        else {
            $pois[$uuid] = (object) null;
        }
        
    }
    $json_struct["pois"] = $pois;
    return $json_struct;
}

function update_fw_core_intl_properties($pgcon, $fw_core_intl_tbl, $uuid, $fw_core)
{
    
    $prop_names = get_fw_core_intl_property_names();
    
    foreach($prop_names as $prop_name)
    {
        if (isset($fw_core[$prop_name]))
            upsert_intl_property_pgsql($pgcon, $fw_core_intl_tbl, $uuid, $prop_name, $fw_core[$prop_name]);
        else
        {
            $del_stmt = "DELETE FROM $fw_core_intl_tbl WHERE uuid='$uuid' and property_name='$prop_name'";
            $del_result = pg_query($del_stmt);
        
            if (!$del_result)
            {
                header("HTTP/1.0 500 Internal Server Error");
                $error = pg_last_error();
                die($error);
            }
        }
    }
    
}

function upsert_intl_property_pgsql($db_con, $table, $uuid, $property_name, $intl_values)
{
    
    $select_existing_values = "SELECT lang FROM $table WHERE uuid='$uuid' and property_name='$property_name'";
    $existing_values_res = pg_query($db_con, $select_existing_values);
        
    if (!$existing_values_res)
    {
        header("HTTP/1.0 500 Internal Server Error");
        $error = pg_last_error();
        die($error);
    }

    $existing_langs = array();
    
    while($row = pg_fetch_row($existing_values_res))
    {
        $existing_langs[] = $row[0];
    }

    $upsert_langs = array_keys($intl_values);
    $remove_langs = array_diff($existing_langs, $upsert_langs);   

    foreach($intl_values as $lang_key => $intl_val)
    {
        $value = pg_escape_string($db_con, $intl_val);
        
        $row_exists = in_array($lang_key, $existing_langs);
        
        if ($row_exists == true)
        {
            $upsert = "UPDATE $table SET value='$value' WHERE uuid='$uuid' and property_name='$property_name' and lang='$lang_key'";
        }
        
        else 
        {
            $upsert = "INSERT INTO $table (uuid, property_name, lang, value) " . 
                "VALUES('$uuid', '$property_name', '$lang_key', '$value')";
        }

        $upsert_result = pg_query($db_con, $upsert);
        if (!$upsert_result)
        {
            header("HTTP/1.0 500 Internal Server Error");
            echo "A database error has occured!";
            echo pg_last_error();
            exit;
        }
    
    }
    
    foreach($remove_langs as $remove_lang)
    {
        $del_stmt = "DELETE FROM $table WHERE uuid='$uuid' and property_name='$property_name' and lang='$remove_lang'";

        $del_result = pg_query($del_stmt);
        
        if (!$del_result)
        {
            header("HTTP/1.0 500 Internal Server Error");
            $error = pg_last_error();
            die($error);
        }
    }
    
}

function get_intl_property_pgsql($db_con, $table, $uuid, $property_name)
{
    $select_values = "SELECT lang, value FROM $table WHERE uuid='$uuid' and property_name='$property_name'";
    $values_res = pg_query($db_con, $select_values);
        
    if (!$values_res)
    {
        header("HTTP/1.0 500 Internal Server Error");
        $error = pg_last_error();
        die($error);
    }

    $existing_values = array();
    
    while($row = pg_fetch_row($values_res))
    {
        $lang = $row[0];
        $value = $row[1];
        $existing_values[$lang] = $value;
    }
    
    return $existing_values;
}

function get_fw_core_intl_properties_for_poi($db_con, $table, $uuid, $poi_data)
{
    
    $intl_props = get_fw_core_intl_property_names();
    
    foreach($intl_props as $prop_name)
    {
        $prop_value = get_intl_property_pgsql($db_con, $table, $uuid, $prop_name);
        if (sizeof($prop_value) > 0)
        {
            $poi_data["fw_core"][$prop_name] = $prop_value;
        }
        
    }
    
    return $poi_data;
}

function connectMongoDB($db_name)
{
    try {
        $m = new MongoClient();
        $m_db = $m->selectDB($db_name);
        return $m_db;
    } catch (MongoConnectionException $e)
    {
        die("Error connecting to MongoDB server");
    }
}

//Retrieves a data component from MongoDB for a given UUID
function getComponentMongoDB($db, $component_name, $uuid, $fetch_for_update)
{
    if ($component_name == "fw_relationships")
    {
        $relationships = findRelationshipsForUUID($db, $uuid, $fetch_for_update);
        return $relationships;
    }
    
    $collection = $db->$component_name;
    $component = $collection->findOne(array("_id" => $uuid), array("_id" => false));
    return $component;
}

//Finds all documents from "fw_relations" collection in MongoDB where the given UUID is present,
//either in "subject" or "object" field
function findRelationshipsForUUID($db, $uuid, $fetch_for_update)
{
    $collection = $db->fw_relationships;
    if ($fetch_for_update == True) {
        $relationships = $collection->findOne(array("subject" => $uuid),
            array("_id" => false));
    }
    
    else
    {
        $relationships = $collection->findOne(array('$or' => array(
            array("subject" => $uuid), 
            array("objects" => $uuid))),
            array("_id" => false));
    }
    return $relationships;
}

?>