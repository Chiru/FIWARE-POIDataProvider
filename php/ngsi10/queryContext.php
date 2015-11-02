<?php
header('Content-Type: application/json');
//ini_set('display_errors',1);

$post_data = file_get_contents("php://input");
$data = json_decode($post_data, true);
$path = $_SERVER['SERVER_NAME'].dirname(dirname($_SERVER['SCRIPT_NAME']));

/* Matching functions */

$found = true;
function matching($array,$search) {
	global $found;
	foreach ($search as $key => $value)
		{
//		var_dump($key,$value,$array); echo "<br>------<br>";
		if ($key == "location" || $key == "categories") continue;
		if (!isset($array[$key])) {$found = false; break;}
		if (is_string($value)) if ( !preg_match('"'.$value.'"',$array[$key]) ) {$found = false; break;};
		if (is_array($value)) matching($array[$key],$value);
		}
	return $found;
	}
	
/* Get POST data and parse POI/location info */

if (isset($data["pois"]["*"]))
{
	$Lat = $data["pois"]["*"]["fw_core"]["location"]["wgs84"]["latitude"];
	$Lon = $data["pois"]["*"]["fw_core"]["location"]["wgs84"]["longitude"];
	$Rad = $data["pois"]["*"]["fw_core"]["location"]["radius"];
	if (isset($data["pois"]["*"]["fw_core"]["categories"])) 
		$Cat = "&category=".implode(",",$data["pois"]["*"]["fw_core"]["categories"]);
	else
		$Cat ="";

	/* Get POIs via radial search */

	$response = file_get_contents("http://".$path."/radial_search?lat=".$Lat."&lon=".$Lon."&radius=".$Rad.$Cat);
	$pois = json_decode($response, true);
}
else /* Get POIs via get_pois */
{
	$poi_ids = array_keys($data["pois"]);
 	$response = file_get_contents("http://".$path."/get_pois?poi_id=".implode(",",$poi_ids));
	$pois = json_decode($response, true);
}

/* Search through POIs and delete non matching ones */

foreach ( $pois["pois"] as $poi => $components )
	{
	foreach ( $data["pois"] as $search_poi => $search_components )
		{
		if (!matching($components, $search_components)) {unset($pois["pois"][$poi]); $found = true;};
		}
	}
echo json_encode($pois);
?>

