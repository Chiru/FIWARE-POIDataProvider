<?php

//function state_until(start_event, schedule, end_event) { // : {}
  /*
    start_event - begin of analysis period
    schedule
    end_event - end of analysis period
    
    *return - {"open": boolean, "end": event}
  */

function simultaneous($event1, $event2) { // boolean
  /* mostly for testing purposes
     return true, if event1 and event2 represent the same point of time
  */
  
  $result = true; 
  for($i = 0; (($i < count($event1)) || ($i < count($event2))) && $result; $i++) {
    if(($i < count($event1)) && ($i < count($event2))) { 
//     console.log("e1=" + event1[i] + " e2=" + event2[i]);
      if($event1[$i] != $event2[$i]) {
        $result = false;
      }
	} elseif ($i < count($event1)) {
      if ($event1[$i] > 0) {
        $result = false;
      }
    } else { // i < event2.length
      if ($event2[$i] > 0) {
        $result = false;
      }
    }
  }
  return $result;
}
    
function later_than($event1, $event2) { // boolean
  /*
    return true, iff event1 is later than event2 (not simultaneous)
  */
  $resolved = false;
  
  $result = false; // simultaneous means not later
  for($i = 0; (($i < count($event1)) || ($i < count($event2))) && !$resolved; $i++) {
    if(($i < count($event1)) && ($i < count($event2))) { 
      if($event1[$i] < $event2[$i]) {
        $result = false;
        $resolved = true;
      } elseif($event1[$i] > $event2[$i]) {
        $result = true;
        $resolved = true;
      }
	    } elseif ($i < count($event1)) {
      if ($event1[$i] > 0) {
        $result = true;
        $resolved = true;
      }
    } else { // i < event2.length
      if ($event2[$i] > 0) {
        $result = false;
        $resolved = true;
      }
    }
  }
  return $result;
}

$days_in_month = array(0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
function next_date($date) { // date
  global $days_in_month;
  $result = array($date[0],$date[1],$date[2]);
  
  $result[2] += 1;
  $dmax = $days_in_month[$result[1]];
  if ($result[2] > $dmax) {
    if ($result[1] == 2) { // February!
      if (
           (($result[0] % 4) == 0) && 
           (
             (($result[0] % 100) != 0) || 
             (($result[0] % 400) == 0)
           )
         ) { // leap year 
        $dmax = 29;
      }
    }
    if ($result[2] > $dmax ) {
      $result[2] = 1;
      $result[1] += 1;
    }
    if ($result[1] > 12) { // 1th of January
      $result[0] += 1;
      $result[1] = 1;    
    }
  }
  return $result;
}

function open_until($start_event, $schedule, $end_limit) { // : {}
  /*
    start_event - begin of analysis period
    schedule
    end_event - end of analysis period
    
    *return - new end event
  */
  $result = $start_event; // Do NOT modify elements! Replace as the whole.
  $end_event = $end_limit;
  //echo gettype($schedule);
  //echo "<br>";
    foreach($schedule as $key => $operands){ // implicit AND
      //$operands = $schedule[$key];
    switch($key) {
      case "or":  // open, if any of the subschedules provides open
        $next_event = $start_event; // end of open time found so far
        $res = array(0,0,0); // default, if nothing found
        $prev = array();
        do { // repeat until end_event stops advancing
          $prev = $next_event;
          for($i = 0; ($i < count($operands)) && later_than($end_event, $next_event); 
              $i++) {
            // try to find continuation for open time already found  
            $next_a = open_until($next_event, $operands[$i], $end_event);
            if (later_than($next_a, $next_event)) {$next_event = $next_a;}
            // update res, if something found
            if (later_than($next_a, $res)) {$res = $next_a;}
          } // TODO: or-loop can be broken, if next_event == end_event
	} while(later_than($next_event,$prev) && later_than($end_event, $next_event));
	      $end_event = $res;
      break;
      case "and": 
        for ($i = 0; ($i < count($operands)) && later_than($end_event, $start_event);
            $i++) {
          $end_event = open_until($start_event, $operands[$i], $end_event);
        
        }
      break;
      case "not": 
        $end_event = closed_until($start_event, $operands, $end_event);
      break;
      case "wd": 
        $next_event = $start_event;
        do {  // TODO: detect all-pass as in closed_until!
          $prev = $next_event;
          //$wd = $weekday($prev[0], $prev[1], $prev[2]); //If using weekday functions
	  $stringtime = (strval($prev[0]) . '-' . strval($prev[1]) . '-' . strval($prev[2]));
          $timestamp = strtotime($stringtime);
          $wd = date("N", $timestamp);
          for($i = 0; ($i < count($operands)) ; $i++) {
            if ($wd == $operands[$i]) {
              $next_event = next_date(array($prev[0], $prev[1], $prev[2]));
              $wd = (($wd - 1) % 7) + 1; // next weekday
            }
          }
       } while(later_than($next_event,$prev));
        if(simultaneous($start_event, $next_event)) {
          $end_event = array(0, 0, 0); // closed
        } else {
          $end_event = $next_event;
        }
      break;
      case "ehr": 
        $nexthr = array(0, 0, 0); // 0 as default for hh, mm, and ss
        for ($i = 3; ($i < count($start_event)) && ( $i < 6); $i++) {
          $nexthr[$i - 3] = $start_event[$i];
        }
        if (!later_than($nexthr, $operands)) {
        if (count($operands) == 1){
          $operands[] = 0;
          }
        if (count($operands) == 2){
          $operands[] = 0;
                }

          //echo var_dump($start_event);
          //echo var_dump($operands);
          $end_event = array(
              $start_event[0], // date from start_event
              $start_event[1],
              $start_event[2],
              $operands[0],
              $operands[1],
              $operands[2]
			     );
        } else {
// *          end_event = start_event; // old code
          $end_event = array(0,0,0);
        }
      break;
      case "bhr": 
        $nexthr = array(0, 0, 0); // 0 as default for hh, mm, and ss
        for ($i = 3; ($i < count($start_event)) && ( $i < 6); $i++) {
          $nexthr[$i - 3] = $start_event[$i];
        }
        if (! later_than($operands, $nexthr)) {
          $end_event = next_date($start_event);
        } else {
// *          end_event = start_event; // old code
          $end_event = array(0, 0, 0);
        }
      break;

      case "eev": 
        if (! later_than($start_event, $operands)) {
          $end_event = $operands;
        } else {
// *          end_event = start_event; // old code
          $end_event = array(0,0,0);
        }
      break;
      case "bev": 
        if (! later_than($operands, $start_event)) {
//          end_event = end_event; // not needed
        } else {
// *          end_event = start_event; // old code
          $end_event = array(0, 0, 0);
        }
      break;

      }
	if (! later_than($end_event, $start_event)) {break;}
	if (later_than($end_event, $end_limit)) {$end_event = $end_limit;}
  }
  /* Result is never later than end_limit */
    return ((later_than($end_limit, $end_event)) ? $end_event : $end_limit);
}
    
function closed_until($start_event, $schedule, $end_limit) { // : {}
  /*
    start_event - begin of analysis period
    schedule
    end_event - end of analysis period
    
    *return - new end event
    
    This has been converted from open_until function mainly by changing and/or
    end/begin closed/open logic.
  */
  $result = $start_event; // Do NOT modify elements! Replace as the whole.
  $end_event = $end_limit;
    
  // OR (union) logic in main loop
  do {
    $prev_0 = $start_event;
    
    foreach($schedule as $key => $contents) { 
      $operands = $schedule[$key];
      switch($key) {
        case "and":  // closed, if any of the subschedules provides closed
          $next_event = $start_event; // ond of closed time found so far
          $res = array(0,0,0);
          do {
            $prev = $next_event;
            for($i = 0; ($i < count($operands)) && later_than($end_event, $next_event); 
                $i++) {
              // try to find continuation for closed time already found
              $next_a = closed_until($next_event, $operands[$i], $end_event);
              if (later_than($next_a, $next_event)) $next_event = $next_a;
              // update res, if something found
              if (later_than($next_a, $res)) $res = $next_a;
            } // TODO: or-loop can be broken, if next_event == end_event
          } while(later_than($next_event,$prev) && later_than($end_event, $next_event));
          $end_event = $res;
        break;
        case "or": 
          for ($i = 0; ($i < count($operands)) && later_than($end_event, $start_event);
              $i++) {
            $end_event = closed_until($start_event, $operands[$i], $end_event);
          }
        break;
        case "not": 
          $end_event = open_until($start_event, $operands, $end_event);
        break;
        case "wd":
          $next_event = $start_event;
          $open_wd = array(false,false,false,false,false,false,false,false);
          $one_day_open = false;
          for ($i = 0; $i < count($operands); $i++) {
            $j = $operands[$i];
            if (($j > 0) && ($j < 8)) {
              $open_wd[$j] = true;
              $one_day_open = true;
            }
          }
          if ($one_day_open) {
            do {
              $prev = $next_event;
	      $stringtime = (strval($prev[0]) . '-' . strval($prev[1]) . '-' . strval($prev[2]));
              $timestamp = strtotime($stringtime);
              $wd = date("N", $timestamp);
              //$wd = weekday($prev[0], $prev[1], $prev[2]); //if using own weekday functions
              if (!$open_wd[$wd]) {
                $next_event = next_date(array($prev[0], $prev[1], $prev[2]));
                $wd = (($wd - 1) % 7) + 1; // next weekday
              }
            } while(later_than($next_event,$prev));
          } else { // always closed
            $next_event = $end_event;
          }
          if(simultaneous($start_event, $next_event)) {
            $end_event = array(0, 0, 0); // closed
          } else {
            $end_event = $next_event;
          }
        break;
        case "bhr": 
          $nexthr = array(0, 0, 0);
          for ($i = 3; ($i < count($start_event)) && ( $i < 6); $i++) {
            $nexthr[$i - 3] = $start_event[$i];
          }
          if (!later_than($nexthr, $operands)) {
          if (count($operands) == 1){
            $operands[] = 0;
            }
          if (count($operands) == 2){
            $operands[] = 0;
                }
            $end_event = array(
                $start_event[0], // date from start event
                $start_event[1],
                $start_event[2],
                $operands[0],
                $operands[1],
                $operands[2]
			       );
          } else {
  // *          end_event = start_event; // old code
            $end_event = array(0,0,0);
          }
        break;
        case "ehr": 
          $nexthr = array(0, 0, 0);
          for ($i = 3; ($i < count($start_event)) && ( $i < 6); $i++) {
            $nexthr[$i - 3] = $start_event[$i];
          }
          if (! later_than($operands, $nexthr)) {
            $end_event = next_date($start_event);
          } else {
  // *          end_event = start_event;
            $end_event = array(0, 0, 0);
          }
        break;

        case "bev": 
          if (!later_than($start_event, $operands)) {
            $end_event = $operands;
          } else {
  // *          end_event = start_event;
            $end_event = array(0,0,0);
          }
        break;
        case "eev":
          if (! later_than($operands, $start_event)) {
//            end_event = end_event;
          } else {
  // *          end_event = start_event;
            $end_event = array(0, 0, 0);
          }
        break;
      }
      /*
        Here:
          end_event - closed_until of this key
      */
    if (later_than($end_event, $end_limit)) {$end_event = $end_limit;}
    if (later_than($end_event, $start_event)) {$start_event = $end_event;}
      
    if (! later_than($end_limit, $start_event)){ break;}

    }
  }
  while(later_than($start_event, $prev_0));

  /* Result is never later than end_limit */
return ((later_than($end_limit, $start_event)) ? $start_event : $end_limit);
}

function find_open_time($schedule, $min_length_s, $start_event, $end_limit, 
    &$res_begintime, &$res_endtime) {
  /*
    schedule 
    min_length_s
    start_event - beginning of inspection period
    end_limit - end of inspection period
    * res_begintime - array elements get the begin time of fitting open time
    * res_endtime - array elements get the end time of fitting open time
    
    *result - true=time found, false= time not found
  */

  $found = false;
  /*
    Algorithm searches alternative closed and open times until either long
    enough open time is found or end limit is reached.
  */
  do {
    $current_begin = closed_until($start_event, $schedule, $end_limit);
    $current_end = open_until($current_begin, $schedule, $end_limit);
    while (count($current_begin) < 6) { $current_begin[] = 0; }
    while (count($current_end) < 6) { $current_end[] = 0; }
    $timestamp_begin = mktime($current_begin[3],$current_begin[4],$current_begin[5],$current_begin[1],$current_begin[2],$current_begin[0]);
    $timestamp_end = mktime($current_end[3],$current_end[4],$current_end[5],$current_end[1],$current_end[2],$current_end[0]);
    
//     echo "current_begin: " . json_encode($current_begin);
//     echo " " . "<html> <br> </html>";   
//     echo "timestamp begin: " . date("c", $timestamp_begin);
//     echo " " . "<html> <br> </html>";
//     echo "current end: " . json_encode($current_end);
//     echo " " . "<html> <br> </html>";
//     echo "timestamp end: " . date("c", $timestamp_end);
//     echo " " . "<html> <br> </html>";
//     
//     echo "End - begin: " . ($timestamp_end - $timestamp_begin);
//     echo " " . "<html> <br> </html>";
//     echo "Min length: " . $min_length_s;
//     echo " " . "<html> <br> </html>";
//     echo " " . "<html> <br> </html>";
    
    if ($current_end == array(0,0,0,0,0,0))
        return false;
    
    if ($timestamp_end - $timestamp_begin >= $min_length_s) {
	$found = true;
      }

    $start_event = $current_end;
    
  
  } while((!$found ) && later_than($end_limit, $start_event));
  
  if(gettype($res_begintime) == "array" && gettype($res_endtime) == "array") {
    if($found) { // copy results
      for ($i = 0; $i < 6; $i++) {
        $res_begintime[$i] = (($current_begin[$i]) ? $current_begin[$i] : 0);
        $res_endtime[$i] = (($current_end[$i]) ? $current_end[$i] : 0);
      }
    } else {
      for ($i = 0; $i < 6; $i++) {
         $res_begintime[$i] = 0;
        $res_endtime[$i] = 0;
      }
    }
  }
  //echo "before return: " . json_encode($res_begintime);
  return $found;
}

?>