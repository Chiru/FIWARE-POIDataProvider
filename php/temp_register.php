<?php // temp_register.php 5.1.3.1 2016-02-03 ariokkon
/*
* Project: FI-WARE
* Copyright (c) 2014 Center for Internet Excellence, University of Oulu, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/

// This PHP code modifies the html page below.
// ===========================================

// Value of REGISTRATION_KEY is fetched from the key parameter

define('SERVICE_NAME', 'temp_register');

// POST https://www.example.com/poi_dp/register_user?key=12a7188211b5058525fbbcd
// 017d2bb52b243c107&auth_p=google
//
// Posted document depends on the authorization provider. In the case of google
// it is bare authentication token.

if ($_SERVER['REQUEST_METHOD'] == 'GET' )
{
  if (isset($_GET['key']))
  {
    $key = pg_escape_string($_GET['key']);
  } else {
    $key = '*key_missing*';
  }
}
// END OF PHP CODE
// =============== ?>

<html>
<head>
  <title>POI DP User Registration</title>
  <meta name="google-signin-scope" content="profile email" />
  <meta name="google-signin-client_id" content="1032522970282-gtsgnea0phqlo86osef1jg1oo8dhficg.apps.googleusercontent.com" />
  <script src="https://apis.google.com/js/platform.js" async="async" defer="defer"></script>

</head>
<body>
<!--  Registration key: <input id="key" type="text" width="660" ><br> -->
  <h2>Select POI DP Authentication</h2>
  Select the authentication method that you will use to sign-in to this POI DP.</h2><br>
      <!-- Google login stuff Begin -->
      <div id="google_signin_b" style="" class="g-signin2" data-onsuccess="onSignIn" data-theme="dark"></div>
<!--      <a href="#" onclick="signOut();"><b>Sign out</b></a> -->
      <!-- Google login stuff End -->
      <br>
      <span id="user_name"></span>&nbsp;
      <img id="user_photo" src="data:image/gif;base64,R0lGODlhAQABAAAAACwAAAAAAQABAAA=" width="32" height="32"><br>
      <span id="result"></span>

<script>
var REGISTRATION_KEY = <?php echo '"' . $key . '"' ?>;
// var REGISTRATION_KEY = "12a7188211b5058525fbbcd017d2bb52b243c107";
var BACKEND_ADDRESS_POI = "";

var user_id_token; // To be used in server calls
var user_id_info = {}; // {name: string, photo: url_string}
function onSignIn(googleUser) {
  // Useful data for your client-side scripts:
  var profile = googleUser.getBasicProfile();
  console.log("ID: " + profile.getId()); // Don't send this directly 
                                         // to your server!
  user_id_info.name = profile.getName(); // AOk
  user_id_info.photo = profile.getImageUrl();  // AOk
  
  console.log("Name: " + profile.getName());
  console.log("Image URL: " + profile.getImageUrl());
  console.log("Email: " + profile.getEmail());

  // The ID token you need to pass to your backend:
  user_id_token = googleUser.getAuthResponse().id_token;
  console.log("ID Token: " + user_id_token);
  
  go_register(user_id_token, signInResp); // AOk
  
};

function signInResp(response) {

console.log("SignIn response: \"" + JSON.stringify(response) + "\"");
  var auth2 = gapi.auth2.getAuthInstance();

  if (response.registration) {
    document.getElementById("user_name").innerHTML = user_id_info.name;
    document.getElementById("user_photo").setAttribute("src", 
        user_id_info.photo);
    document.getElementById("result").innerHTML = "Registration succeeded. " +
        "That's all.";
    auth2.signOut();
  } else {
    auth2.signOut().then(function() {
        alert("Sorry to inform you that\n" +
              "the user is not known by this service.");});
  }
  document.getElementById("google_signin_b").style.display = "none";
}

  var go_register = function(id_token, callback) {
    var xhr = new XMLHttpRequest();
//    var reg_key = document.getElementById("key").value;
//    alert(reg_key);
    var restQueryURL = BACKEND_ADDRESS_POI + "register_user?key=" + 
//        reg_key +
        REGISTRATION_KEY +
        "&auth_p=google";
    
    xhr.open('POST', restQueryURL, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      var response, err;
      try {
        response = JSON.parse(xhr.responseText);
        if (response.login) { // We're in!
          poi_user_tok = Sha1.hash(id_token);
        }
      } catch(err) {
        response = {"login":false,"message":"Bad response: " + err.message};
      }
      callback(response);
    };
    xhr.onerror = function() {
        response = {"login":false,"message":"Login to server failed"};
        callback(response);
    };
    xhr.send(id_token);
  }

</script>
</body>
</html>
