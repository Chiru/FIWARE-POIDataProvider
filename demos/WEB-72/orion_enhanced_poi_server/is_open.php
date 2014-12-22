<?php

function is_open($event, $schedule)
{
  $result = true;
  foreach($schedule as $key => $contents) {
    $operands = $schedule[$key];
    switch($key) {
    case 'or':
      $result = false; // one must succeed
      for ($i = 0; ($i < count($operands)) && (!$result); $i++) {
        $result |= is_open($event, $operands[$i]);
      }
      break;

    case 'and':
      for ($i = 0; $i < count($operands) && $result; $i++) {
	$result &= is_open($event, $operands[$i]);
      }
      break;

    case 'not':
      $result = !is_open($event, $operands);
      break;
    case 'wd':
        //$wd = weekday($event[0], $event[1], $event[2]); if using weekday.js
	$stringtime = (strval($event['year']) . '-' . strval($event['month']) . '-' . strval($event['day']));
        $timestamp = strtotime($stringtime);
        $wd = date("N", $timestamp);
        $result = false;
        for ($i = 0; $i < count($operands) && !$result; $i++) {
	  $result |= $wd == $operands[$i];
        }
       break;
    case 'ehr':
      $resolved = false;
      $j = 3; //hour in event
      $i = 0;
      $result = true; // if event just on end limit
      for($j = 3; ($j < count($event)) && !$resolved; $j++){
	if($i < count($operands[$i])) {
	  if ($event['hour'] < $operands[$i]){
	  $result = true;
	  $resolved = true;
          }
          elseif($event['hour'] > $operands[$i]) {
	  $result = false;
	  $resolved = true;
          }
        }elseif($event[$j] > 0){
            $result = false;
            $resolved = true;
	  }
        $i++;
      }       
      break;
    case 'bhr':
      $resolved = false;
      $j = 3; //hour in event
      $i = 0;
      for($í = 0; ($i < count($operands)) && !$resolved; $i++){
	if($j < count($event)) {
	  if($event['hour'] < $operands[$i]) {
	    $result = false;
	    $resolved = true;
	  }elseif($event['hour'] > $operands[$i]) {
	    $result = true;
	    $resolved = true;
	  }
	}elseif($operands[$i] > 0) {
	  $result = false;
	  $resolved = true;
	}
	$j++;
      }
      break;
    }
    if (!$result) {break;
    }
  }
    
return $result;

}

?>