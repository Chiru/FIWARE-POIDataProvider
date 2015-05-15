<?php
/*
header('Content-Type: application/json');
$poi_id = $_GET["poi_id"];
*/

function get_dyn_fields($fw_dynamic){

	$conf_data = file_get_contents("poi_dp_dyn_conf.json");
	$conf = json_decode($conf_data,true);

	$type = $fw_dynamic["data_type"];
	$field_list = array_keys($conf["data_mapping"][$type]);
	return  ($field_list ? $field_list : array());
}

function get_dyn_pois($fw_dynamic){

	$conf_data = file_get_contents("poi_dp_dyn_conf.json");
	$conf = json_decode($conf_data,true);

	$host = $fw_dynamic["host_type"];
	$type = $fw_dynamic["data_type"];
	$ids  = $fw_dynamic["host_id"];
	$id   = $fw_dynamic["host_id"][0];

	switch ($conf["host_type"][$host]["method"])
		{
		case "REST_GET":  	
			$output = http_get($conf["host_type"][$host]["params"]["url"].$id.$conf["host_type"][$host]["params"]["params"],
			array('headers' => $conf["host_type"][$host]["params"]["headers"]));
			break;
		case "REST_POST": 
			$i = 0;
			$data = $conf["host_type"][$host]["params"]["params"];
			foreach ($ids as $id) $data = str_replace('$'.$i++,$id,$data);
			if (is_array($data)) $data = json_encode($data);
			$output = http_post_data($conf["host_type"][$host]["params"]["url"],$data,
			array('headers' => $conf["host_type"][$host]["params"]["headers"]));
			break;
		}

	$data = http_parse_message($output)->body;
	return map_data($conf["data_mapping"][$type], $data);
}

function _fw_match($search,$string)
{
	list($pre, $post) = explode("?",$search);
	$i = strpos($string, $pre);
	if ($i == 0) return "";
	$i = $i+strlen($pre);
	$j = strpos($string, $post, $i);
	return substr($string, $i, $j-$i);
}

function _fw_json($array,$data){
	if (!is_array($data)) $data = json_decode($data, true);
	foreach($array as $item => $value){
			if ( $value == '?') $result = strval($data[$item]); else
			if ( is_array($value) || ($value == $data[$item])) $result= _fw_json($value,$data[$item]);
	};
	return $result;
};

function map_data($object, $data){
	foreach($object as $item => $structure)
	{
		if (strpos($structure[0], "_fw_") === 0 ) $data_structure[$item] = $structure[0]($structure[1],$data);
		else
			if (is_string ($structure)) $data_structure[$item] = $structure; 
			else
			if (is_array  ($structure)) $data_structure[$item] = map_data($structure, $data);
	};	
	return $data_structure;
};

/* Main function */
/*
$poi = file_get_contents("http://dev.cie.fi/FI-WARE/poi_dp_dyn/get_pois?poi_id=".$poi_id);
$poi_data = json_decode($poi,true);

echo json_encode(get_dyn_pois($poi_data["pois"][$poi_id]["fw_dynamic"]));
*/
?>