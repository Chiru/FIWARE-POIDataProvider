<!DOCTYPE html>
<html>
<head>
<?php 
//	ini_set('display_errors',1);
//	error_reporting(E_ALL);

  $url   = $_REQUEST["url"];
  $data  = $_REQUEST["data"];
  $method= $_REQUEST["method"];
  $json  = $_REQUEST["json"];
  if ($json != "" && $json != "null") $data = $json;
?>
</head>
<body>
<h1>FIWARE POI_DP API demo interface:</h1>
<b>
  <form action="index.php">
  Select/edit URL: <br>
  <input list="url" type="text" name="url" size="150" value='<?php echo $url; ?>' autofocus><br>
  <datalist id="url">
    <option value="http://dev.cie.fi/FI-WARE/poi_dp_dyn/ngsi10/queryContext">
	<option value="http://dev.cie.fi/FI-WARE/poi_dp_dyn/ngsi10/contextEntities/65/24.5/20000">
    <option value="http://dev.cie.fi/FI-WARE/poi_dp_dyn/ngsi10/contextEntities/65/24.5/20000/fuel">
    <option value="http://dev.cie.fi/FI-WARE/poi_dp_dyn/ngsi10/contextEntities/09cf0de1-2a56-430b-8fb8-81b4950fdc57">
    <option value="http://dev.cie.fi/FI-WARE/poi_dp_dyn/ngsi10/contextEntities/09cf0de1-2a56-430b-8fb8-81b4950fdc57/component/fw_core">
  </datalist>
  <br>
  Select/edit POST data: <br>
  <input id="data" list="datas" type="text" name="data" size="150" value='<?php echo $data; ?>' onchange="document.getElementById('json').value=JSON.stringify(JSON.parse(document.getElementById('data').value),null,4);"><br>
  <datalist id="datas">
    <option value='{"pois":{"36a5e867-64a4-447b-ac16-094154513447":{},"91834e4b-b465-446b-9e4a-5af9eeafe5f1":{}}}'>
	<option value='{"pois":{"*":{"fw_core":{"location":{"wgs84":{"latitude":65.0192047,"longitude":24.7410111},"radius":15000}}}}}'>
    <option value='{"pois":{"*":{"fw_core":{"location":{"wgs84":{"latitude":65.0192047,"longitude":24.7410111},"radius":15000},"categories":["fuel"]}}}}'>
    <option value='{"pois":{"*":{"fw_core":{"location":{"wgs84":{"latitude":65.0192047,"longitude":24.7410111},"radius":15000},"categories":["fuel","sensor"]}}}}'>
    <option value='{"pois":{"*":{"fw_core":{"location":{"wgs84":{"latitude":65.0192047,"longitude":24.7410111},"radius":15000}},"fw_dynamic":{"host_type":"kelikamerat"}}}}'>
  </datalist>
  <br>
  Select Method:
  <input type="submit" name="method" formmethod="post" value="GET">
  <input type="submit" name="method" formmethod="post" value="POST">
  <input type="submit" name="method" formmethod="post" value="PUT">
  <input type="submit" name="method" formmethod="post" value="DELETE">
  <br><br>
  POST data as JSon: 
  <br>
  <textarea id="json" rows="10" cols="133" name="json"><?php echo json_encode(json_decode($data),JSON_PRETTY_PRINT); ?></textarea>
</form>
<br>
<button onclick="document.getElementById('json').value=''">Clear JSon</button>
<br>
<br>
<?php echo $method; ?>
 Host Response:<br>
</b>
<pre>
<?php 
	
// Main function:

switch ($method)
	{
	case "GET":	 $response = http_get($url); break;
	case "POST": $response = http_post_data($url,$data); break;
	case "PUT":  $response = http_put_data($url,$data); break;
	case "DELETE": echo("DELETE NOT SUPPORTED: $url"); exit;
	}
//list($head,$body) = explode("{",$response);
echo $response;
echo "<b><h3><br>JSon pretty print:<br></h3></b>";
$json = http_parse_message($response)->body;
echo json_encode(json_decode($json), JSON_PRETTY_PRINT);
?>
</pre>
</body>
</html>