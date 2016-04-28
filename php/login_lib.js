/*  login_lib.js  v1.0  2016-04-26  Ari Okkonen

    A short JavaScript library to help utilizing the FIWARE POI Access Control
    in application web pages.

    NOTE: This library reserves several global names beginning LOGIN_, login_, 
          and logout_.
    
    Usage
    =====

    1.  Copy following user interface elements and the library link to a proper
        location of your application page. You may have to edit the library
        link, if located separately from your application.
        ----
          <!-- Begin access control elements: buttons, name, and image -->
          <button id="login_b" style="" type="button" 
              onclick="login_click();"><b>Log In</b></button>
          <button id="logout_b" style="display:none" type="button" 
              onclick="logout_click();"><b>Log Out</b></button>
          <span id="login_user_name"></span>&nbsp;
          <img id="login_user_image" 
              src="data:image/gif;base64,R0lGODlhAQABAAAAACwAAAAAAQABAAA=" 
              width="32" height="32"><br>
          <!-- End access control elements -->
          <!-- Include login library -->
          <script type="text/javascript" src="login_lib.js"></script>
        ----
      
    2.  Copy the following code template to the script part of your application. 
        Edit as needed. You may rename those my_logged_in and my_logged_out.
        ----
          // var LOGIN_SERVER = "http://www.example.org/poi_dp/"; // Note 
                                                             // trailing slash!
          var LOGIN_SERVER = ""; // can be left blank if in the same location 

          var login_user_token = ""; // to be used in subsequent requests
                                     // as the auth_t parameter
          var login_user_info = {}; // {name: string, image: url_string}

          var login_completed = my_logged_in; // called when login completed
          var logout_completed = my_logged_out; // called when logout completed

          function my_logged_in() {
            // Here comes your code that is executed on login
          }

          function my_logged_out() {  
            // Here comes your code that is executed on logout
          }

          // Ensure that the login button is enabled if the page is reloaded.
          document.getElementById("login_b").disabled = false;
        ----

* Project: FI-WARE
* Copyright (c) 2016 Adminotech Oy, All Rights Reserved
* For conditions of distribution and use, see copyright notice in LICENSE
*/
var login_popup;
var login_poll_timer;

function login_done(succeeded, login_data) {
  if (succeeded) {
    // Switch to logout button and show the user information.
    // Finally tell the application that the user has logged in
    document.getElementById("login_b").style.display = "none";
    document.getElementById("logout_b").style.display = "inline";
    login_user_token = login_data.token; // proof of identity for subsequent ops
    login_user_info = login_data.user_info; // get user info to show
    document.getElementById("login_user_name").innerHTML =
        login_user_info.name;
    document.getElementById("login_user_image").setAttribute("src", 
        login_user_info.image);
    
    if(login_completed) login_completed(); // Tell it to the application
  } else {
    // Login failed, enable the login button
    document.getElementById("login_b").disabled = false;
  }
}


function login_poll() {
  // Polls when the login window finishes
  try {
    if(login_popup.done) {
      // finished, move to the next phase: login_done
      clearInterval(login_poll_timer);
      if(login_popup.logged_in) {
        login_done( true,
            {
              token: login_popup.user_token,
              user_info: login_popup.user_id_info
            }
        );
      } else {// else didn't succeed
        login_done(false, null, null);
      }

      login_popup.close();
    }
  }
  catch(e) {}
    
}

function login_click() {
  // disable login button, open login window and start polling it
  document.getElementById("login_b").disabled = true;
  login_popup = window.open(LOGIN_SERVER + "login.html",
      "_blank", "width=500, height=610");
  login_poll_timer = setInterval(login_poll, 1000);
}

function logout_click_done() {
  // clear user name
  document.getElementById("login_user_name").innerHTML = "";
  // set empty image
  document.getElementById("login_user_image").setAttribute("src",
      "data:image/gif;base64,R0lGODlhAQABAAAAACwAAAAAAQABAAA=");
  // switch to enabled login button
  document.getElementById("login_b").style.display = "inline";
  document.getElementById("login_b").disabled = false;
  document.getElementById("logout_b").style.display = "none";
  
  if(logout_completed) logout_completed(); // Tell it to the application
}

function logout_click() {
/*
    Signs out from the (POI) service. Sends a logout request to the server and
    on the response calls the logout_click_done for the next phase.
*/
  var xhr = new XMLHttpRequest();
  var restQueryURL = LOGIN_SERVER + "logout?auth_t=" + login_user_token;
  
  login_user_token = "";
  xhr.open('GET', restQueryURL, true);
  xhr.onload = function() {
      logout_click_done();
    };
  xhr.send();
  
}

