<?php
function cb_get_pois($params) {

  $base_addr = 'http://orion.lab.fi-ware.org:1026/v1/queryContext';

  $limit = 1000;

  try {
    $orion_key = json_decode(file_get_contents('../orion_key.txt'))[0];
  } catch (Exception $ex) {
    $orion_key = "";
  }

  $search_area = cb_search_area($params);
  $ans = array();
  $next = 0;
  $more = TRUE;
  try {
    while ($more) {
      $more = FALSE;
      $addr = $base_addr . '?';
      if ($next > 0) {
        $addr = $addr . 'offset=' . $next .'&';
      }
      $addr = $addr . 'limit=' . $limit;
      $next = $next + $limit;
      $http = new HttpRequest($addr, HTTP_METH_POST);
      $headers = array();
      $headers['Content-Type'] = 'application/json';
      $headers['Accept'] = 'application/json';
      if ($orion_key != "") {
        $headers['X-Auth-Token'] = $orion_key;
      }
      $http->setHeaders($headers);
      $body = '{"entities":[{"type":"cie_poi","isPattern":"true","id":"cie_poi_*'
            . '"}],"attributes":["data"],"restriction":{"scopes":[{"type":"FI'
            . 'WARE::Location","value":'.$search_area.'}]}}';
      $http->setBody($body);
      $respmsg = $http->send();
      $resp_str = $respmsg->getBody();
      $resp = json_decode($resp_str);
      if (property_exists($resp, 'contextResponses')) {
        $context_responses = $resp->contextResponses;
        foreach ($context_responses as $context_response) {
          $more = TRUE;
          $context_element = $context_response->contextElement;
          $id = $context_element->id;
          $uuid = substr($id, 8);
          $attributes = $context_element->attributes;
          foreach($attributes as $attribute) {
            $name = $attribute->name;
            if ($name == 'data') {
              $encoded_value = $attribute->value;
              $json_value = rawurldecode($encoded_value);
              $ans[$uuid] = json_decode($json_value, TRUE);
            }
          }
        }
      }
    }
  } catch (Exception $e) {
  }
  return $ans;
}

function cb_search_area($params) {
  if (array_key_exists('rad', $params)) {
    $ans = '{"circle":{"centerLatitude":"'.$params['lat'].'","centerLongitude"'
         . ':"'.$params['lon'].'","radius":"'.$params['rad'].'"}}';
  } else {
    $ans = '{"polygon":{"vertices":[{"latitude":"'.$params['north'].'","longit'
         . 'ude":"'.$params['east'].'"},{"latitude":"'.$params['south'].'","lo'
         . 'ngitude":"'.$params['east'].'"},{"latitude":"'.$params['south'].'"'
         . ',"longitude":"'.$params['west'].'"},{"latitude":"'.$params['north']
         . '","longitude":"'.$params['west'].'"}]}}';
  }
  return $ans;
}
