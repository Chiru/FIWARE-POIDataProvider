<?php
header("Content-type: text/plain");
define("restBaseURL", "http://orion.lab.fi-ware.org:1026/v1/");
define("POI_SERVER", "http://localhost/FI-WARE/demos/WEB-72/vehicle_poi_server/radial_search");

function update_orion() {
  $now = date('Y-m-d G:i:s');
  $msg = $now . "\n";
  try {
    $orion_key = json_decode(file_get_contents('../orion_key.txt'))[0];
  } catch (Exception $ex) {
    $orion_key = "";
  }
  try {
    $oldpois = json_decode(file_get_contents('pois.txt'),TRUE);
  } catch (Exception $ex) {
    $oldpois = array();
  }
  $newpois = array();
  $content = file_get_contents (POI_SERVER);
  $response_structure = json_decode($content, TRUE);
  $pois = $response_structure['pois'];
  $poicnt = 0;
  $more = TRUE;
  while ($more) {
    $more = FALSE;
    $contextElements = '{"contextElements":['."\n";
    foreach ($pois as $uuid => $data) {
      if (!isset($newpois[$uuid])) {
        $json_data = json_encode($data);
        $encoded_data = rawurlencode($json_data);
        $lat = $data['fw_core']['location']['wgs84']['latitude'];
        $lon = $data['fw_core']['location']['wgs84']['longitude'];
        if (strlen($contextElements) < 1000000) {    
          $newpois[$uuid] = 1;
          $contextElements .= '{'
              . '"type": "cie_poi",'
              . '"isPattern": "false",'
              . '"id": "cie_poi_'.$uuid.'",'
              . '"attributes": ['
              . '{ "name": "data",'
              . '"type": "string",'
              . '"value": "'.$encoded_data.'"'
              . '},'
              . '{'
              . '"name": "position",'
              . '"type": "coords",'
              . '"value": "'.$lat.', '.$lon.'",'
              . '"metadatas": ['
              . '{'
              . '"name": "location",'
              . '"type": "string",'
              . '"value": "WGS84"'
              . '}]}]},'."\n";
          $poicnt++;
          $more = TRUE;
        }
      }
    }
    if ($more) {
      $contextElements = substr($contextElements, 0, strrpos($contextElements, ','))
          . '],"updateAction":"APPEND"}';
      $r = new HttpRequest(restBaseURL . 'updateContext', HttpRequest::METH_POST);
      $r->setContentType('application/json');
      $headers = array();
      $headers['Content-Type'] = 'application/json';
      $headers['Accept'] = 'application/json';
      if ($orion_key != "") {
        $headers['X-Auth-Token'] = $orion_key;
      }
      $r->setHeaders($headers);
      $r->addRawPostData($contextElements);
      try {
        $r->send();
        $status = $r->getResponseBody();
        if (strpos($status, '"errorCode"')) {
          $msg .= $status."\n";
        }
      } catch (HttpException $ex) {
        $msg .= "ERROR\n".$ex."\n";
      }
    }
  }

  if ($poicnt > 0) {

    // delete disappeared pois
    $deletecnt = 0;
    foreach ($oldpois as $uuid => $x) {
      if (!isset($newpois[$uuid])) {
        $deletecnt++;
        $r = new HttpRequest(restBaseURL . "contextEntities/cie_poi_"
            . $uuid, HttpRequest::METH_DELETE);
        $r->setContentType('application/json');
        $headers = array();
        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';
        if ($orion_key != "") {
          $headers['X-Auth-Token'] = $orion_key;
        }
        $r->setHeaders($headers);
        try {
          $r->send();
          $resp_str = $r->getResponseBody();
          $resp = json_decode($resp_str, TRUE);
          $code = "?";
          foreach ($resp as $key => $info) {
            if ($key == 'code') {
              $code = $info;
            } else if (isset($info['code'])) {
              $code = $info['code'];
            }
          }
          // OK if 200 (OK) or 404 (not found)
          if ($code != "200" && $code != "404") {
            $newpois[$uuid] = 2;
            $msg .= "Failed to delete $uuid\n($code) $resp_str\n";
          }
        } catch (Exception $ex) {
          $newpois[$uuid] = 3;
          $msg .= "Failed to delete $uuid\nException: $ex\n";
        }
      }
    }

    // save current poi list
    file_put_contents('pois.txt', json_encode($newpois));

  } else {
    $msg .= "No data\n".$content."\n";
  }
  file_put_contents('status.txt', $msg);
};

update_orion();
