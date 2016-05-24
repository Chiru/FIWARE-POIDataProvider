/*
  This requires:
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp"></script>
    <script src="string_html.js"</script>

  In order to use the menu the followin are needed. The names beginning with
  the prefix "my_" are placeholders for user-defined names.  
    
    // The callback routine to be called, when a menu item is selected

    function my_menu_callback(menu_id, option, lat, lng, data) {
      // menu_id - the id string of the menu - used as a html element id
      // option - the key of the menu item selected
      // lat - latitude
      // lng - longitude
      // data - as given in my_overlay.show(,,,data)

      // Your code to handle the the selected menu item comes here!
    }

    // The definition of the menu
    
    var my_menu = {
      id: "my_menu_id",
      options: { // Menu items - as many as needed
        my_option_1: "Option 1",
        my_option_2: "Second Option" 
      },
      cancel: "Cancel", // The item to dismiss the menu
      select: my_menu_callback // Called, when a menu item is selected
    };
    
    // Creating the menu object my_overlay connected to the Google map object 
    // my_map and showing the menu my_menu.
    
    my_overlay = new Overlay_menu_gm(my_map, my_menu);

    // Showing the menu on rightclick
    
    google.maps.event.addListener(my_map, 'rightclick', function(mouseEvent){
      // If you want to do something with the latitude and longitude, you get
      // them like this:
      //   var lat = mouseEvent.latLng.lat();
      //   var lng = mouseEvent.latLng.lng();
      
      // Show the menu at the position of rightclick
      my_overlay.show(mouseEvent.latLng, title, data);
      //   latLng - must be mouseEvent.latLng to position the menu correctly
      //   title  - A string to be shown at the top of the menu, if given
      //   data   - Passed as such to the selected callback
    });

*/
"use strict";
/*
  menu = {
    id: "my_menu_id",
    options: {
      option_1: "Option 1",
      option_2: "Second Option"  
    },
    cancel: "Cancel",
    select: my_item_selected // (menu_id, option)
  };

*/
var olmgm_context = {};
/*  {
      "list_id_1": {
        "menu": menu,
        "overlay": Overlay_menu_gm_object
      ...
    }
*/

function olmgm_htmlToElement(html) {
    var template = document.createElement('template');
    template.innerHTML = html;
    return template.content.firstChild;
};

function Overlay_menu_gm(map, menu) {

  // Initialize all properties.
  this.menu_ = menu;
  this.map_ = map;
  this.div_ = null; // onAdd() sets this
  this.lat_lng_ = new google.maps.LatLng(65.0, 25.5);
  
  olmgm_context[menu.id] = {};
  olmgm_context[menu.id].menu = menu; // save for further reference
  olmgm_context[menu.id].overlay = this;

  // Explicitly call setMap on this overlay.
  this.setMap(map);
};

function olmgm_mouse_down(menu_item) {
  if ((menu_item.getAttribute("class") == "sl_option") ||
      (menu_item.getAttribute("class") == "sl_option_focus"))
    menu_item.setAttribute("class", "sl_option_selected");
}

function olmgm_mouse_over(menu_item) {
  if (menu_item.getAttribute("class") == "sl_option")
    menu_item.setAttribute("class", "sl_option_focus");
}

function olmgm_mouse_up(menu_item) {
  if (menu_item.getAttribute("class") == "sl_option_selected") {
    menu_item.setAttribute("class", "sl_option");
    // confirmed click
    var action = menu_item.getAttribute("data");
    var this_menu = menu_item.parentElement;
    var menu_id = this_menu.getAttribute("id");
    
    var cx = olmgm_context[menu_id];
    var data = cx.overlay.data_;
    var lat = cx.overlay.lat_lng_.lat();
    var lng = cx.overlay.lat_lng_.lng();
    cx.menu.select(menu_id, action, lat, lng, data); // callback
    menu_item.setAttribute("class", "sl_option");
    cx.overlay.hide();
  }
}

function olmgm_mouse_out(menu_item) {
  if ((menu_item.getAttribute("class") == "sl_option_selected") ||
      (menu_item.getAttribute("class") == "sl_option_focus"))
    menu_item.setAttribute("class", "sl_option");
}


Overlay_menu_gm.prototype = new google.maps.OverlayView();

/**
 * onAdd is called when the map's panes are ready and the overlay has been
 * added to the map.
 */
Overlay_menu_gm.prototype.onAdd = function() {
  var action;
  var text;
  var div;
  var div_html = "<div class=\"sl_popup_menu\"" +
      " id=\"" + this.menu_.id + "\" " + 
      "onmousedown=\"olmgm_mouse_down(" +
      "event.target || " +
      "event.srcElement)\"" + "onmouseup=\"olmgm_mouse_up(" + 
      "event.target || event.srcElement)\"" +
      "onmouseover=\"olmgm_mouse_over(event.target || " + 
      "event.srcElement)\"onmouseout=\"olmgm_mouse_out(" +
      "event.target || event.srcElement)\"" +
      ">" +
      "<div class=\"sl_options_title\"><strong id=\"" + this.menu_.id + 
      "_title\" ></strong></div>";

  for ( action in this.menu_.options ) {
    text = this.menu_.options[action];
    div_html += "<p class=\"sl_option\" data=\"" + action +"\">" + text + 
        "</p>";
  };
  div_html +=            
      "<hr></hr>" +
      "<p style=\"font-style:italic\" class=\"sl_option\" data=\"\">" + 
      this.menu_.cancel + "</p>" + "</div>";
      
  div = olmgm_htmlToElement(div_html);
  div.style.position = 'absolute';
  div.style.visibility = 'hidden';
    
  this.div_ = div;

  // Add the element to the "overlayLayer" pane.
  var panes = this.getPanes();
  panes.floatPane.appendChild(div);
};

Overlay_menu_gm.prototype.draw = function() {

  // We use the south-west and north-east
  // coordinates of the overlay to peg it to the correct position and size.
  // To do this, we need to retrieve the projection from the overlay.
  var overlayProjection = this.getProjection();

  // Retrieve the south-west and north-east coordinates of this overlay
  // in LatLngs and convert them to pixel coordinates.
  // We'll use these coordinates to resize the div.
  var ne = overlayProjection.fromLatLngToDivPixel(this.lat_lng_);

  var div = this.div_;
  div.style.left = "" + Math.floor(ne.x) + 'px';
  div.style.top = "" + Math.floor(ne.y) + 'px';
};

// The onRemove() method will be called automatically from the API if
// we ever set the overlay's map property to 'null'.
Overlay_menu_gm.prototype.onRemove = function() {
  this.div_.parentNode.removeChild(this.div_);
  this.div_ = null;
};

// Set the visibility to 'hidden' or 'visible'.
Overlay_menu_gm.prototype.hide = function() {
  this.div_.style.visibility = 'hidden';
};

Overlay_menu_gm.prototype.show = function(latLng, title, data) {
  this.div_.style.visibility = 'visible';
  this.div_.style.display = 'block';
  this.lat_lng_ = latLng;
  if (title && (title != "")) {
    document.getElementById(this.menu_.id + "_title").innerHTML = "<p>" + 
        safe_html(title) + "</p><hr></hr>";
  }
  this.data_ = data;
  this.draw();
};

  