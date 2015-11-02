<?php
header('Content-Type: application/json');

$args = ($_SERVER["PATH_INFO"]);
list($p0,$Lat,$Lon,$Rad,$Cat,$Comp) = explode("/",$args);
list($p0,$p1,$p2,$p3,$p4,$p5) = explode("/",$args);

$path = $_SERVER['SERVER_NAME'].dirname(dirname($_SERVER['SCRIPT_NAME']));
if(!isset($p2) || $p2 == "component")
	{
	if(isset($p3) && $p3 != "") $Comp = "&component=".$p3; else $Comp = "";
	$response = file_get_contents("http://".$path."/get_pois?poi_id=".$p1.$Comp);
	}
	else
	{
	if(isset($p4) && $p4 != "") $Cat = "&category=".$p4; else $Cat = "";
	if(isset($p5) && $p5 != "") $Comp = "&component=".$p5; else $Comp = "";
	$response = file_get_contents("http://".$path."/radial_search?lat=".$p1."&lon=".$p2."&radius=".$p3.$Cat.$Comp);
	}

echo ($response);
?>