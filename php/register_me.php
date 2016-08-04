<?php // register_me.php v.5.4.2.1 ariokkon 2016-08-03
/*
* Project: FI-WARE
* Copyright (c) 2016 Center for Internet Excellence, University of Oulu, All
* Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET' )
{
  $site_info_s = file_get_contents("./site_info.json");
  $site_info = json_decode($site_info_s, true);
  $server_info_s = file_get_contents("./server_info.json");
  $server_info = json_decode($server_info_s, true);
  $owner = $site_info['owner'];
  $owner_website = $owner['website'];
  $support = $site_info['support'];
  $support_contact = $support['contact'];
  
  if (isset($_GET['key']))
  {
    $key = pg_escape_string($_GET['key']);
  }
  $db_opts = get_db_options();
  $mongodb = connectMongoDB($db_opts['mongo_db_name']);
  $_reg_calls = $mongodb->_reg_calls;
  $registration_call = $_reg_calls->findOne(array("_id" => $key), 
      array("_id" => false));
  $user_id = $registration_call['user_id'];
  $users = $mongodb->_users;
  $user = $users->findOne(array("_id" => $user_id), 
      array("_id" => false));

}  
?>
<html>
<head>
  <title>User Registration - <?php print $site_info['title']; ?></title>

</head>
<body>
  <h2>Select Your POI DP Authentication</h2>
  <span id="result">   <?php print $user['name']; ?>, please, register to this 
    <?php print $site_info['name']; ?> by logging in using your 
    authentication provider.
  </span><br>
  <!-- Begin access control elements: buttons, name, and image -->
  <button id="login_b" style="" type="button" 
      onclick="login_click();"><b>Register</b></button>
  <span id="login_user_name"></span>&nbsp;
  <img id="login_user_image" 
      src="data:image/gif;base64,R0lGODlhAQABAAAAACwAAAAAAQABAAA=" 
      width="32" height="32"><br>
  <!-- End access control elements -->

<script>
// <!--
// var LOGIN_SERVER = "http://www.example.org/poi_dp/"; // Note 
                                                   // trailing slash!
var LOGIN_SERVER = ""; // can be left blank if in the same location 

var auth_t = ""; // to be used in subsequent requests
                           // as the auth_t parameter

var query_parameters = get_query_parameters();
var key = query_parameters.key;

if (!key) {
  document.getElementById("result").innerHTML = 
      "Your registration key is missing in the URL. Consult the " +
      "administrator of this service.";
  document.getElementById("login_b").disabled = true;
}

// Ensure that the login button is enabled if the page is reloaded.
document.getElementById("login_b").disabled = false;

var login_popup;
var login_poll_timer;

function login_succeeded(name, login_user_info) {
  // Switch to logout button and show the user information.
  // Finally tell the application that the user has logged in
  document.getElementById("login_b").style.display = "none";
  document.getElementById("login_user_name").innerHTML =
      login_user_info.name;
  document.getElementById("login_user_image").setAttribute("src", 
      login_user_info.image);
  
  document.getElementById("result").innerHTML = 
      "Registration completed.<br>" +
      "You are registered as " + name + " to this service.";
}

function login_failed(msg) {
  document.getElementById("result").innerHTML = 
      "Registration failed.<br>" + msg;
  document.getElementById("login_b").style.display = "inline";
}

function login_authenticated(auth_data) {
  /*
    auth_data: {
      oauth2_token: string,
      auth_p:       string,
      user_id_info: {
        name: string,
        image: url_string
      }
    }
  */

  var succeeded = false;
  var name = "";
  var msg = "";
  var response, err;
  var xhr = new XMLHttpRequest();
  var restQueryURL = LOGIN_SERVER + "register_user?key=" + key +
      "&auth_p=" + auth_data.auth_p;

  xhr.open('POST', restQueryURL, true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onload = function() {
    try {
      console.log("register_user response: " + xhr.responseText);
      response = JSON.parse(xhr.responseText);
      if (response.registration) { // We're in!
        succeeded = true;
        name = response.name;
      } else {
        msg = response.msg;
      }
    } catch(err) {
      msg = err.message
    }
    if (succeeded) {
      login_succeeded(name, auth_data.user_id_info);
    } else {
      login_failed(msg);
    }
  };
  xhr.onerror = function() {
    login_failed("Nonspecific error in sending registration");
  };
  xhr.send(auth_data.oauth2_token);
}

function login_poll() {
  // Polls when the authentication window finishes
  try {
    if(login_popup.done) {
      // finished, move to the next phase: login_done
      clearInterval(login_poll_timer);
      login_authenticated(
        {
          oauth2_token: login_popup.oauth2_token,
          auth_p: login_popup.auth_p,
          user_id_info: login_popup.user_id_info
        }
      );
      login_popup.close();
    }
  }
  catch(e) {
    console.log("Error: " + e.message);
  }
    
}

function login_click() {
  // disable login button, open login window and start polling it
  document.getElementById("login_b").disabled = true;
  login_popup = window.open(LOGIN_SERVER + "authenticate.html",
      "_blank", "width=500, height=610");
  login_poll_timer = setInterval(login_poll, 1000);
}


function get_query_parameters() {

  var url_wo_fragment = document.URL.split("#")[0];
  var query_string = url_wo_fragment.split("?")[1];
  var query_params = {};
  if (query_string) {
    query_string = query_string.replace(/;/g, "&"); // to support ; separator
    query_arr = query_string.split('&');
    for (var i = 0, query_arr_len = query_arr.length; i < query_arr_len; i++)
    {
      var qpar = query_arr[i].split('=');
      query_params[qpar[0]] = qpar[1];
    }       
  }  
  return query_params;
}

// -->
</script>
</body>
</html>
