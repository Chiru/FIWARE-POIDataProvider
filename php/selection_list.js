/* selection_list.js v1.0 2016-02-24 Ari Okkonen

  Selection list package
  
  Developed in FIWARE POI project 
  http://catalogue.fiware.org/enablers/poi-data-provider
  
  Copyright 2016 Adminotech Oy, Finland

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.  
  
  
Setup for using the selection list package
------------------------------------------

A.  HTML Page

    These items go to the <head> section.

    1.  Link to the style sheet
    
          <link rel="stylesheet" type="text/css" href="selection_list.css">
          
    2.  Include this script
    
          <script type="text/javascript" src="selection_list.js"></script>

    This item goes to the <body> section

    3.  Space for the selection list in the html page

        Space is reserved for the selection list using a <div> tag with a 
        suitable id. E.g.:
        
          <div id="my_selection_list_id"></div>

B.  These items go to your script
          
    1.  Data for the selection list

        The data for the list is prepared to an object containing strings. The 
        strings are visible in the page and the keys will identify the string
        that is selected. E.g.:
        
          var my_list_data = {
            key1: "First line",
            key2: "Second choice"
          };
          
    2.  Data for the popup menu

        The data for the popup menu has the same format as the selection list. 
        E.g.:
        
          var my_menu_choices = {
            action1: "Do action 1",
            action2: "Operate otherwise",
            action3: "Third menu item"
          };

    3.  Callback for the menu actions

        Menu actions are acted upon in a callback routine. The selection list 
        framework calls the routine, when a menu item is selected. The signature 
        of the callback routine:
      
          my_callback_routine(key, action) 
          //  key    - string - key of the selected data line
          //  action - string - key of the selected menu item

        E.g.: 
        
          my_callback_routine(key, action) {
            alert("You selected to " + action + " the " + key);
          }
          
    4.  Populating and activating the selection list
    
        Call the selection_list_fill routine to populate and activate the
        selection list.

        See the parameter explanations of the selection_list_fill routine. E.g.:
        
          selection_list_fill(my_selection_list_id, my_list_data, 
              my_menu_choices, my_callback_routine, "Cancel");
      
*/

"use strict"; // reveal bad scripting

var sl_sellist_context = {};

/*
selection_list_fill
-------------------
  
This fills a selection list with data
    
*/    

function selection_list_fill(list_id, data, menu, callback, canc_text) {
/*  
    list_id - id of the <div> tag specifying the place for the selection list
              <div id="list_id">...</div>
    data    - an object of strings. The keys will be the values of "key" 
              attributes of <p> tag delimited selection items
              <p key="key" ...>string</p>
    menu    - an object of strings.
    callback  - routine to call upon clicking a menu item
    canc_text - text to display in the generated last menu item meaning 
                "cancel". Clicking it just closes the menu.
*/
  var list = "";
  var text = "";
  var id;
  var cont_el = document.getElementById(list_id);
  var menu_id = list_id + "_menu";

  for ( id in data ) {
    text = data[id];
    list += "<p class=\"sl_option\" key=\"" + id +"\">" + text + "</p>";

  }
  cont_el.innerHTML = list;
  cont_el.setAttribute("class", "sl_sellist");
  cont_el.setAttribute("oncontextmenu", 
      "sl_rc(\"" + list_id + "\", \"" + menu_id + "\", event, " + 
      "event.target || event.srcElement); return false");
  cont_el.setAttribute("onmouseover", 
      "popup_mouse_over(event.target || event.srcElement)");
  cont_el.setAttribute("onmouseout", 
      "popup_mouse_out(event.target || event.srcElement)");

  sl_sellist_context[list_id] = {cb: callback};
  
  popup_fill(menu_id, menu, canc_text);
}

function popup_fill(menu_id, menu, canc_text) {
  var menu_div_el;
  var action;
  var text;
  var menu_div_html = "<div class=\"sl_popup_menu\" id=\"" + menu_id + "\"" +
        "onmousedown=\"popup_mouse_down(event.target || event.srcElement)\"" +
        "onmouseup=\"popup_mouse_up(event.target || event.srcElement)\"" +
        "onmouseover=\"popup_mouse_over(event.target || event.srcElement)\"" +
        "onmouseout=\"popup_mouse_out(event.target || event.srcElement)\">";
  for ( action in menu ) {
    text = menu[action];
    menu_div_html += "<p class=\"sl_option\" data=\"" + action +"\">" + text + 
        "</p>";
  }
  menu_div_html +=            
      "<hr></hr>" +
      "<p style=\"font-style:italic\" class=\"sl_option\" data=\"\">" + 
      canc_text + "<p>" + "</div>";

  menu_div_el = htmlToElement(menu_div_html);
  
  document.body.appendChild(menu_div_el);
}

function htmlToElement(html) {
    var template = document.createElement('template');
    template.innerHTML = html;
    return template.content.firstChild;
}

function sl_menu_cb(list_id, el, action) {
  var list_context = sl_sellist_context[list_id];
  var key = el.getAttribute("key");
  el.setAttribute("class", "sl_option");
  if (action != "") list_context.cb(key, action);
}

function sl_rc(list_id, menu_id, event, el) {
  el.setAttribute("class", "sl_choice_selected");
  popup_show(list_id, el, sl_menu_cb, menu_id, event.clientX, event.clientY);
  
  return false;
}

var sl_popup_context = {};

function popup_show(list_id, target, cb, menu_id, x, y) {
  var menu_el = document.getElementById(menu_id);
  var style = menu_el.style;
  style.top = "" + y + "px";
  style.left = "" + x + "px";
  style.display = "block";
  
  var cx = sl_popup_context[menu_id];
    
  if(cx)  cx.cb(list_id, cx.el, ""); // callback - cancel
  
  sl_popup_context[menu_id] = {list_id: list_id, el: target, cb: cb};
}

function popup_hide(menu_id) {
  var el = document.getElementById(menu_id);
  var style = el.style;
  style.top = "";
  style.left = "";
  style.display = "none";
  sl_popup_context[menu_id] = null;
}

function popup_mouse_down(menu_item) {
  if ((menu_item.getAttribute("class") == "sl_option") ||
      (menu_item.getAttribute("class") == "sl_option_focus"))
    menu_item.setAttribute("class", "sl_option_selected");
}

function popup_mouse_over(menu_item) {
  if (menu_item.getAttribute("class") == "sl_option")
    menu_item.setAttribute("class", "sl_option_focus");
}

function popup_mouse_up(menu_item) {
  if (menu_item.getAttribute("class") == "sl_option_selected") {
    menu_item.setAttribute("class", "sl_option");
    // confirmed click
    var action = menu_item.getAttribute("data");
    var menu_id = menu_item.parentElement.getAttribute("id");
    var cx = sl_popup_context[menu_id];
    
    cx.cb(cx.list_id, cx.el, action); // callback
    menu_item.setAttribute("class", "sl_option");
    popup_hide(menu_id);
  }
}

function popup_mouse_out(menu_item) {
  if ((menu_item.getAttribute("class") == "sl_option_selected") ||
      (menu_item.getAttribute("class") == "sl_option_focus"))
    menu_item.setAttribute("class", "sl_option");
}
