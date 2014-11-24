/* POI Demo 3 modified for Component POI data */

// "use strict"; // useful in debugging

/* HTML elements */

var elemCategoriesBox = document.getElementById("categories_box");
var elemCategories = document.getElementById("categories");

/* Ontology in use */

var ontology = "fw_osm";

/* Local POI database indexed by UUID */

var miwi_poi_pois = {}; // ["UUID": {<POI data>},...]
var miwi_poi_xml3d_models = {}; // ["UUID": {<xml3d data>},...]
var miwi_poi_xml3d_requests = {}; // ["UUID": {lon: <lon>, 
                                  // lat: <lat>},...]
var miwi_poi_xhr = null; // http request
var miwi_3d_xhr = null; // http request

var miwi_lang_sel_1 = null;
var miwi_lang_sel_2 = null;
var miwi_languages;
var miwi_poi_pois_by_category = {}; // "schema" follows
/*  {
      <category>: {
        "label": { en:"xxx", fi:"yyy", ... } // translations of category label
        "supercategories: [<category...>]
        "subcategories: [<category...>]
        "expanded": <category> // subcategories shown under specified category
        "selected": <boolean> // desired visibility of the markers
        "visible": <boolean> // current visibility of the markers
        "markers": [<markers on map, use setVisible(<boolean>)>]
        "deprecated": true // optional, typically absent
      }
    }
*/

/**/

/*  str2html - convert any string for safe display in html
    ========
   
    This converts characters not allowed in html strings to well behaving
    html character entities.           
    
    str2html(rawstr: string): string;
        rawstr - string not controlled for contents
        
        *result - safe, well behaving html representation of the input string
        
    Example: "Rat & Arms" -> "Rat &amp; Arms"    
*/        
        
        
        
var str2html_table = {
	"<": "&lt;",
	"&": "&amp;",
	"\"": "&quot;",
	"'": "&apos;",
	">": "&gt;",
};

function str2html (rawstr) {
	var result = "";
    var code;
    if (!rawstr) {
        rawstr = "";
    }
	for (var i = 0; i < rawstr.length; i++) {
        code = rawstr.charCodeAt(i);
        if (code < 0x7f) {
            result = result + (str2html_table[rawstr[i]] ? 
				(str2html_table[rawstr[i]]) : (rawstr[i]));
        } else {
            result = result + "&#x" + code.toString(16) + ";";
        }
	}
	return result;
}
/**/

(function ( namespace ) {

  // MarkerOps class for marker events
    function MarkerOps(uuid) {
      this.luokka = "MarkerOps";
      this.uuid = uuid;
      this.rightclick = function(mouseEvent){
        /*
          Right click shows context menu
        */
        var my = this; // 'this' does not seem to behave well in closure
        POIMenu.show(mouseEvent.latLng);
        google.maps.event.clearListeners(POIMenu, 'menu_item_selected');
        google.maps.event.addListener(POIMenu, 'menu_item_selected', function(latLng, eventName){
          my.menu_item_selected(latLng, eventName);
        });

      };
      this.menu_item_selected = function(latLng, eventname){
        switch (eventname) {
          case 'toggle_poi_select_click': {
          
          } break;
          case 'edit_poi_click': {
            POI_edit(uuid);
          
          } break;
          case 'delete_poi_click': {
            POI_delete(uuid);
          
          } break;
 
        };
      };
    };

var POIEditWindow = null;

        
function OpenPOIEditWindow(title, heading, cancel_callback) { // false, if not to use
  var result;
  
console.log('entering OpenPOIEditWindow');
  if (POIEditWindow && !POIEditWindow.closed) {
    POIEditWindow.focus();
    POIEditWindow.alert("Only one POI under editing allowed!");
    result = false;
  } else {
    POIEditWindow=window.open('','editor_popup','toolbar=no,dialog=yes,' + 
      'location=no,directories=no,status=no,menubar=no,resizable=yes,' + 
      'copyhistory=no,scrollbars=yes,width=500,height=600');
    POIEditWindow.document.head.innerHTML = "<title>" + title + "</title>";
    POIEditWindow.document.body.innerHTML = "<h3>" + heading + "</h3>" +
        '<div id="poi_editor"></div><button id="poi_edit_cancel"' +
        ' onclick="poi_edit_cancel()">Cancel' +
        '</button> ';
    POIEditWindow.poi_edit_cancel = cancel_callback;
    result = true;
  }
  return result;
}; 

function EditInPOIEditWindow(title, heading, poi_data, uuid, cancel_callback, 
      ok_callback) {

  POIEditWindow.document.head.innerHTML = "<title>" + title + "</title>";
  POIEditWindow.document.body.innerHTML = "<h3>" + heading + "</h3>" +
      '<div id="poi_editor"></div><button id="poi_edit_cancel"' +
      ' onclick="poi_edit_cancel()">Cancel' +
      '</button> <button id="poi_edit_ok"' +
      ' onclick="poi_edit_ok(window.poi_data, window.uuid)">OK</button>';
  POIEditWindow.sbje = sbje;
  POIEditWindow.poi_data = poi_data;
  POIEditWindow.uuid = uuid;
  POIEditWindow.poi_edit_cancel = cancel_callback;
  POIEditWindow.poi_edit_ok = ok_callback;
  sbje.make_form("poi_editor", poi_schema, poi_data, POIEditWindow.document);
  window.setTimeout(function(){POIEditWindow.focus()}, 100);

}; 

function OpenAndEditInPOIEditWindow(title, heading, poi_data, uuid, cancel_callback, 
    ok_callback) { // false if does not succeed
  var result;
  
  result = OpenPOIEditWindow(title, heading, cancel_callback);
  if (result) {
    EditInPOIEditWindow(title, heading, poi_data, uuid, cancel_callback, 
        ok_callback);
  };
  return result;
};

function POI_edit_cancel() {
    if (POIEditWindow.marker) {
        POIEditWindow.marker.setOptions({draggable: false});
        POIEditWindow.marker.setPosition(POIEditWindow.markerOldPos);
        POIEditWindow.marker = null;
        google.maps.event.removeListener(POIEditWindow.draglistener);
        POIEditWindow.draglistener = null;
    }
    POIEditWindow.close();
}

function isValidPOI(poi_data) {
    try {
        if (!poi_data.fw_core.categories[0]) return false;
        for (var lang in poi_data.fw_core.name) {
            if (lang.length > 0 && lang.charAt(0) != '_') {
                if (poi_data.fw_core.name[lang]) return true;
            }
        }
        return false;
    } catch (e) {
        return false;
    }
}

function checkPOI(poi_data) {
    var ans = isValidPOI(poi_data);
    if (!ans) {
        var w = window;
        if (POIEditWindow && !POIEditWindow.closed) {
            POIEditWindow.focus();
            w = POIEditWindow;
        }
        w.alert("Insufficient POI data!\n"+
              "Category, location, and name are needed.");
    }
    return ans;
}

function updatePOI( poi_data, uuid ) {
    var restQueryURL;
    var updating_data = {};

    if (!checkPOI(poi_data)) return;

/* build updating structure like
    { 
      "30ddf703-59f5-4448-8918-0f625a7e1122": {
        "fw_core": {...},
        ...
      }
    }
*/

    console.log('Updating ' + uuid);
    
    updating_data[uuid] = poi_data;
    
    restQueryURL = BACKEND_ADDRESS_POI + "update_poi";
    miwi_poi_xhr = new XMLHttpRequest();
    
    miwi_poi_xhr.overrideMimeType("application/json");
    
    miwi_poi_xhr.onreadystatechange = function () {
        var data;
        var poiMarker;
        if(miwi_poi_xhr.readyState === 4) {
            if(miwi_poi_xhr.status  === 200) { 
//                alert( "success: " + miwi_poi_xhr.responseText);
                removePOI_UUID_FromMap(uuid);
                POI_edit_cancel();
                delete miwi_poi_pois[uuid];
                unstorePoi(uuid);
                data = {pois:{}};
                data.pois[uuid] = poi_data;
                processPoiData(data);
                poiMarker = getPoiLocal(uuid, 'marker');
                poiMarker.setVisible(true);
                show_POI_window(poiMarker, uuid);
            }
            else if (miwi_poi_xhr.status === 404) { 
                alert("failed 404: " + miwi_poi_xhr.responseText);
            }
        }
    }

    miwi_poi_xhr.onerror = function (e) {
        alert("error" + JSON.stringify(e));
    };

    miwi_poi_xhr.open("POST", restQueryURL, true);
//        miwi_poi_xhr.setRequestHeader('Content-Type', 'application/json');
console.log(JSON.stringify(updating_data));
    miwi_poi_xhr.send(JSON.stringify(updating_data));
    
    console.log('Update sent ' + uuid);

}

    var field_id_location = "i:poi_editor.fw_core.location.wgs84";
    var field_id_lat = field_id_location+".latitude";
    var field_id_lng = field_id_location+".longitude";

    function POI_moved_callback(newLocation) {
        if (newLocation && POIEditWindow && !POIEditWindow.closed) {
            var doc = POIEditWindow.document;
            var lat_field = doc.getElementById(field_id_lat);
            var lng_field = doc.getElementById(field_id_lng);
            var lat = newLocation.latLng.lat();
            var lng = newLocation.latLng.lng();
            var msg = lat+"\n"+lng;
            if (lat_field) {
                sbje.number_type_handler.set_input_field(lat_field, lat);
            }
            if (lng_field) {
                sbje.number_type_handler.set_input_field(lng_field, lng);
            }
            if (lat_field) {
                sbje.field_changed(field_id_lat, lat);
            }
            if (lng_field) {
                sbje.field_changed(field_id_lng, lng);
            }
            POIEditWindow.focus();
        }
    }
    
    function POI_edit(uuid) {
        var restQueryURL, poi_data, poi_core;
        var poiMarker = getPoiLocal(uuid, 'marker');

        if (POIEditWindow && !POIEditWindow.closed) {
            POIEditWindow.focus();
            POIEditWindow.alert("Only one POI under editing allowed!");
            return;
        }

        show_POI_window(poiMarker, uuid);
        
        restQueryURL = BACKEND_ADDRESS_POI + "get_pois?poi_id=" + uuid +
            "&get_for_update=true";
        
        miwi_3d_xhr = new XMLHttpRequest();
        
        miwi_3d_xhr.onreadystatechange = function () {
            if(miwi_3d_xhr.readyState === 4) {
                if(miwi_3d_xhr.status  === 200) { 
                    var json = JSON.parse(miwi_3d_xhr.responseText);
                    var poi_edit_buffer = json.pois[uuid];
                    
                    EditInPOIEditWindow("update poi "  + uuid, "Edit POI data", 
                        poi_edit_buffer, uuid, POI_edit_cancel, updatePOI);
 
                    POIEditWindow.marker = poiMarker;
                    POIEditWindow.draglistener =
                        google.maps.event.addListener(
                                poiMarker, 'dragend', POI_moved_callback);
                    POIEditWindow.markerOldPos = poiMarker.getPosition();
                    poiMarker.setOptions({draggable: true});
                }
                else if (miwi_3d_xhr.status === 404) { 
                    log("failed: " + miwi_3d_xhr.responseText);
                }
            }
        };

        miwi_3d_xhr.onerror = function (e) {
            log("failed to get 3d");
        };
        OpenPOIEditWindow("Updating POI", "Wait, POI data requested", miwi_3d_xhr.abort);
        miwi_3d_xhr.open("GET", restQueryURL, true);
        miwi_3d_xhr.send();

   }
    
    function POI_delete(uuid) {
        var poiMarker = getPoiLocal(uuid, 'marker');
        show_POI_window(poiMarker, uuid);
        setTimeout(function(){POI_delete_2(uuid);},100);
    }
    
    function POI_delete_2(uuid) {
        var restQueryURL, poi_data, poi_core;
      
        var cfm = confirm("Confirm to delete POI " + uuid);

        if (cfm) {
            restQueryURL = BACKEND_ADDRESS_POI + "delete_poi?poi_id=" + uuid;

            miwi_3d_xhr = new XMLHttpRequest();

            miwi_3d_xhr.onreadystatechange = function () {
                if(miwi_3d_xhr.readyState === 4) {
                    if(miwi_3d_xhr.status  === 200) {
                        poiWindow.setMap(null);
                        removePOI_UUID_FromMap(uuid);
                        delete miwi_poi_pois[uuid];
                        unstorePoi(uuid);
//                        alert("Success: " + miwi_3d_xhr.responseText);
                    } else {
                        alert("Failed: "+miwi_3d_xhr.status+" "+miwi_3d_xhr.responseText);
                    }
                }
            }

            miwi_3d_xhr.onerror = function (e) {
                log("failed to delete POI " + JSON.stringify(e));
            };

//        miwi_3d_xhr.open("GET", restQueryURL, true);
            miwi_3d_xhr.open("DELETE", restQueryURL, true);
            miwi_3d_xhr.send();
        }

   }
    
    
   
    var log = wex.Util.log, map, geocoder, homeMarker, positionMarker, 
        poiWindow, i,
        poiStorage = {},
        poiStorageLocal = {},
        markers = [],
        oldSearchPoints = {},
        queries = {},
        queryID = 0, //Running number to identify POI search areas, and to 
                     //track search success
        webSocket_POI = null,
        webSocket_3D = null,
        centerChangedTimeout,
        oldMapCenter,
        CENTER_CHANGED_THRESHOLD = 130,
//        BACKEND_ADDRESS_POI = "http://localhost:8000/poi_demo_5/",
        BACKEND_ADDRESS_POI = "http://dev.cie.fi/FI-WARE/poi_dp_new/",
//        BACKEND_ADDRESS_POI = "http://130.231.12.82/FI-WARE/php/deployment/",
//        BACKEND_ADDRESS_3D = "http://localhost:8000/poi_demo_5/",
//        BACKEND_ADDRESS_3D = "http://chiru.cie.fi:8085/",

        searchRadius = 600,
        searchRadiusScaling = 2.0;
    var poi_edit_buffer;
    var POIMenu;

//        languages = ["fi", "sv"];

    window.WebSocket = (window.WebSocket || window.MozWebSocket);
    miwi_lang_sel_1 = document.getElementById( "lang_sel_1" );
    miwi_lang_sel_2 = document.getElementById( "lang_sel_2" );
    miwi_languages = [miwi_lang_sel_1, miwi_lang_sel_2];

    // This function is called by Google API when it has been loaded
    // Initialises the demo
    namespace.initialize = function () {
        log( "Initialising the demo." );

function addPOI( poi_data, dummy ) {
    var restQueryURL;
    var responseText;

    if (!checkPOI(poi_data)) return;

    restQueryURL = BACKEND_ADDRESS_POI + "add_poi";
    miwi_poi_xhr = new XMLHttpRequest();
    
    miwi_poi_xhr.overrideMimeType("application/json");

    miwi_poi_xhr.onreadystatechange = function () {
        var json;
        var uuid;
        var data;
        var poiMarker;
        if(miwi_poi_xhr.readyState === 4) {
            responseText = miwi_poi_xhr.responseText;
            if(miwi_poi_xhr.status  === 200) {
                if (responseText.substring(0,6) != "Error:") { 
                    POIEditWindow.close();
                    json = JSON.parse(miwi_poi_xhr.responseText);
                    uuid = json.created_poi.uuid;
                    data = {pois:{}};
                    data.pois[uuid] = poi_data;
                    processPoiData(data);
                    poiMarker = getPoiLocal(uuid, 'marker');
                    poiMarker.setVisible(true);
                    show_POI_window(poiMarker, uuid);
//                    alert( "success: " + responseText);
                } else {
                    alert(responseText);
                }
            } else { 
                alert("failed "+miwi_poi_xhr.status+": " + responseText);
            }
            if (POIEditWindow && !POIEditWindow.closed) {
                POIEditWindow.focus();
            }
        }
    }

    miwi_poi_xhr.onerror = function (e) {
        alert("error" + JSON.stringify(e));
        if (POIEditWindow && !POIEditWindow.closed) {
            POIEditWindow.focus();
        }
    };

    miwi_poi_xhr.open("POST", restQueryURL, true);
//        miwi_poi_xhr.setRequestHeader('Content-Type', 'application/json');
//alert("### "+JSON.stringify(poi_data));
    miwi_poi_xhr.send(JSON.stringify(poi_data));

}
        
 


/* ContextMenu.js copied below 
   =========================== */
/*
	ContextMenu v1.0
	
	A context menu for Google Maps API v3
	http://code.martinpearman.co.uk/googlemapsapi/contextmenu/
	
	Copyright Martin Pearman
	Last updated 21st November 2011
	
	developer@martinpearman.co.uk
	
	This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

	You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

function ContextMenu(map, options) {
	options=options || {};
  var key;
	this.setMap(map);
	
	this.classNames_=options.classNames || {};
	this.map_=map;
	this.mapDiv_=map.getDiv();
	this.menuItems_=options.menuItems || [];
	this.pixelOffset=options.pixelOffset || new google.maps.Point(10, -5);
}

ContextMenu.prototype=new google.maps.OverlayView();

ContextMenu.prototype.draw=function(){
	if(this.isVisible_){
		var mapSize=new google.maps.Size(this.mapDiv_.offsetWidth, this.mapDiv_.offsetHeight);
		var menuSize=new google.maps.Size(this.menu_.offsetWidth, this.menu_.offsetHeight);
		var mousePosition=this.getProjection().fromLatLngToDivPixel(this.position_);
		
		var left=mousePosition.x;
		var top=mousePosition.y;
		
		if(mousePosition.x>mapSize.width-menuSize.width-this.pixelOffset.x){
			left=left-menuSize.width-this.pixelOffset.x;
		} else {
			left+=this.pixelOffset.x;
		}
		
		if(mousePosition.y>mapSize.height-menuSize.height-this.pixelOffset.y){
			top=top-menuSize.height-this.pixelOffset.y;
		} else {
			top+=this.pixelOffset.y;
		}
		
		this.menu_.style.left=left+'px';
		this.menu_.style.top=top+'px';
	}
};

ContextMenu.prototype.getVisible=function(){
	return this.isVisible_;
};

ContextMenu.prototype.hide=function(){
	if(this.isVisible_){
		this.menu_.style.display='none';
		this.isVisible_=false;
	}
};

ContextMenu.prototype.onAdd=function() {
	function createMenuItem(values){
		var menuItem=document.createElement('div');
		menuItem.innerHTML=values.label;
		if(values.className){
			menuItem.className=values.className;
		}
		if(values.id){
			menuItem.id=values.id;
		}
		menuItem.style.cssText='cursor:pointer; white-space:nowrap';
		menuItem.onclick=function(){
			google.maps.event.trigger($this, 'menu_item_selected', $this.position_, values.eventName);
		};
		return menuItem;
	}
	function createMenuSeparator(){
		var menuSeparator=document.createElement('div');
		if($this.classNames_.menuSeparator){
			menuSeparator.className=$this.classNames_.menuSeparator;
		}
		return menuSeparator;
	}
	var $this=this;	//	used for closures
	
	var menu=document.createElement('div');
	if(this.classNames_.menu){
		menu.className=this.classNames_.menu;
	}
	menu.style.cssText='display:none; position:absolute';
	
	for(var i=0, j=this.menuItems_.length; i<j; i++){
		if(this.menuItems_[i].label && this.menuItems_[i].eventName){
			menu.appendChild(createMenuItem(this.menuItems_[i]));
		} else {
			menu.appendChild(createMenuSeparator());
		}
	}
	
	delete this.classNames_;
	delete this.menuItems_;
	
	this.isVisible_=false;
	this.menu_=menu;
	this.position_=new google.maps.LatLng(0, 0);
	
	google.maps.event.addListener(this.map_, 'click', function(mouseEvent){
		$this.hide();
	});
	
	this.getPanes().floatPane.appendChild(menu);
};

ContextMenu.prototype.onRemove=function(){
	this.menu_.parentNode.removeChild(this.menu_);
	delete this.mapDiv_;
	delete this.menu_;
	delete this.position_;
};

ContextMenu.prototype.show=function(latLng){
	if(!this.isVisible_){
		this.menu_.style.display='block';
		this.isVisible_=true;
	}
	this.position_=latLng;
	this.draw();
};


/* end ContextMenu.js
   ================== */

        document.querySelector( '#button1' ).onclick = locate;
        document.querySelector( '#button2' ).onclick = codeAddress;
 //       document.querySelector( '#categories' ).onchange = category_changed;
 //       document.querySelector( '#button4' ).onclick = updateMap;

        geocoder = new google.maps.Geocoder();

        var mapOptions = {
            zoom: 16,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            center: new google.maps.LatLng( 65.0610432, 25.468170099999952 ) 
                //Initial location Oulu University
        };

        map = new google.maps.Map( document.getElementById( 'map-canvas' ),
            mapOptions );

        homeMarker = new google.maps.Marker( {
            icon: "http://chart.apis.google.com/chart?chst=d_map_pin_icon&" + 
                "chld=home|FEFE00",
            title: "Current position"
        } );

        positionMarker = new google.maps.Marker( {
            icon: "http://chart.apis.google.com/chart?chst=d_map_pin_letter&" + 
                "chld=L|00FFFF",
            title: "Found location"
        } );

        poiWindow = new google.maps.InfoWindow( {
            content: '<div class="infoTitle">DefaultName</div>' +
                '<div class="infoText">' +
                "<p>Category: DefaultCategory </p>" +
                '</div>'
        } );

        oldMapCenter = map.getCenter();

        google.maps.event.addListener( map, 'zoom_changed', function () {
            var zoomLevel = map.getZoom();

            if ( zoomLevel <= 10 ) {
                searchRadius = 5000;
            } else if ( zoomLevel <= 14 && zoomLevel > 10 ) {
                searchRadius = 5000 + (10 - zoomLevel) * 1000;
            } else if ( zoomLevel > 5 && zoomLevel < 20 ) {
                searchRadius = 1000 - (zoomLevel - 10) * 100;
            } else if ( zoomLevel >= 20 ) {
                searchRadius = 50;
            }
            searchRadius *= searchRadiusScaling; // Scaling factor
        } );

        google.maps.event.addListener( map, 'center_changed', function () {
            var mapCenter = map.getCenter(), dist, minDist = Infinity, i, len,
                searchPoints = oldSearchPoints[searchRadius + ''];

            //TODO: Experimental feature. Reducing amount of queries to backend.
            // (wip)
            dist = distHaversine( mapCenter, oldMapCenter );
            // Center has to move enough before looking through old search 
            // points. Reduces processing amount.
            if ( dist > CENTER_CHANGED_THRESHOLD ) {

                // Now we check if the new search point is far enough from old 
                // query points.
                if ( searchPoints ) {
                    len = searchPoints.length;

                    for ( i = len; i--; ) {
                        dist = distHaversine( mapCenter, 
                                searchPoints[i]['center'] );
                        if ( dist < minDist ) {
                            minDist = dist;
                        }

                    }

                    if ( minDist <= searchRadius * 0.8 ) {
                        return;
                    }
                }

                // Initiate new search after small timeout, so the search is 
                // not constantly triggered while moving the map
                clearTimeout( centerChangedTimeout );
                centerChangedTimeout = window.setTimeout( 
                        function ( lat, lng ) { searchPOIs( lat, lng );
                }, 800, mapCenter.lat(), mapCenter.lng() );

                oldMapCenter = mapCenter;

            }
        } );

        // set up miwi_poi_pois_by_category
        {
          var by_category = miwi_poi_pois_by_category;
          var category;
          var parents;
          var n;
          var i;
          var subst;
          var j;
          var supercategory;
          var deprecation_check_needed = false;
          
          // first identify ontology
          if ('_def' in poi_categories) {
            ontology = poi_categories._def;
          } else {
            for (var ont in poi_categories) {
              if (ont.charAt(0) != '_') {
                ontology = ont;
                break;
              }
            }
          }
          category_type_handler.ontology = ontology;
/*  {
      <category>: {
        "label": { en:"xxx", fi:"yyy", ... } // translations of category label
        "supercategories: [<category...>]
        "subcategories: [<category>]
        "expanded": <category> // subcategories shown under specified category
        "selected": <boolean> // desired visibility of the markers
        "visible": <boolean> // current visibility of the markers
        "markers": [<markers on map, use setVisible(<boolean>)>]
        "deprecated": true // optional, typically absent
      }
    }
*/
          by_category._ALL = {
            label: (poi_categories._ALL && poi_categories._ALL._label) || "-- ALL --",
            supercategories: [],
            subcategories: ['_OTHER'],
            selected: false,
            expanded: '_ALL',
            visible: false,
            markers: []
          }
          for (category in poi_categories[ontology]) {
            if (category.charAt(0) != '_') {
              by_category[category] = {
                label: (poi_categories[ontology][category]._label || category),
                supercategories: [],
                subcategories: [],
                selected: false,
                expanded: null,
                visible: false,
                markers: []
              }
            }
          }
          by_category._OTHER = {
            label: (poi_categories._OTHER && poi_categories._OTHER._label) 
                   || "-- OTHER --",
            supercategories: ['_ALL'],
            subcategories: [],
            selected: false,
            expanded: null,
            visible: false,
            markers: []
          }
          for (category in poi_categories[ontology]) {
            if (category.charAt(0) != '_') {
              parents = poi_categories[ontology][category]._parents;
              if (!parents || parents.length == 0) parents = ['_ALL'];
              if (poi_categories[ontology][category]._deprecated) {
                deprecation_check_needed = true;
                by_category[category].deprecated = true;
                if (poi_categories[ontology][category]._deprecated.length) {
                  parents = parents.concat(
                                poi_categories[ontology][category]._deprecated );
                  parents.push('_OTHER');
                }
              }
              n = parents.length;
              for (i = 0; i < n; i++) {
                supercategory = parents[i];
                if (!by_category[supercategory]) supercategory = '_OTHER';
                if ( by_category[supercategory].subcategories
                     .indexOf(category) < 0 )
                {
                  by_category[supercategory].subcategories.push(category);
                }
                if ( by_category[category].supercategories
                     .indexOf(supercategory) < 0 )
                {
                  by_category[category].supercategories.push(supercategory);
                }
              }
            }
          }
          while (deprecation_check_needed) {
            deprecation_check_needed = false;
            for (category in by_category) {
              if (category.charAt(0) != '_') {
                if (by_category[category].deprecated) {
                  for (supercategory in by_category[category].supercategories)
                  {
                    for (subcategory in by_category[category].subcategories) {
                      if ( by_category[supercategory].subcategories.
                           indexOf(subcategory) < 0 )
                      {
                        by_category[supercategory].subcategories
                            .push(subcategory);
                        deprecation_check_needed = true;
                      }
                      if ( by_category[subcategory].supercategories.
                           indexOf(supercategory) < 0 )
                      {
                        by_category[subcategory].supercategories
                            .push(supercategory);
                        deprecation_check_needed = true;
                      }
                    }
                  }
                }
              }
            }
          }

// test
/*
      var story = "Alustus: ";
      var item;
      
      for(category in by_category) {
        item = by_category[category];
        if ( item.selector.selected ) {
          story += "," + category;
        }
      }
      alert(story);
*/
// end_test
        }
        
        // fill category list
        try {
            namespace.fill_category_list();
        } catch (e) {
            alert("fill_category_list FAILED: "+e+"\n"+e.toSource());
        }

/*  Context menu setup
    ==================
*/
	//	create the ContextMenuOptions object
	var contextMenuOptions={};
	contextMenuOptions.classNames={menu:'context_menu', menuSeparator:'context_menu_separator'};

	//	create an array of ContextMenuItem objects
	var menuItems=[];
//	menuItems.push({className:'context_menu_item', eventName:'zoom_in_click', label:'Zoom in'});
//	menuItems.push({className:'context_menu_item', eventName:'zoom_out_click', label:'Zoom out'});
    if (fw_editAllowed) {
        menuItems.push({className:'context_menu_item', eventName:'add_poi_click', label:'Add POI'});
    }
	//	a menuItem with no properties will be rendered as a separator
	menuItems.push({});
	menuItems.push({className:'context_menu_item', eventName:'center_map_click', label:'Center map here'});
	contextMenuOptions.menuItems=menuItems;
	
	//	create the ContextMenu object
	var contextMenu=new ContextMenu(map, contextMenuOptions);
	
	//	display the ContextMenu on a Map right click
	google.maps.event.addListener(map, 'rightclick', function(mouseEvent){
		contextMenu.show(mouseEvent.latLng);
	});
	
	//	listen for the ContextMenu 'menu_item_selected' event
	google.maps.event.addListener(contextMenu, 'menu_item_selected', function(latLng, eventName){
		//	latLng is the position of the ContextMenu
		//	eventName is the eventName defined for the clicked ContextMenuItem in the ContextMenuOptions
		switch(eventName){
/*    
			case 'zoom_in_click':
				map.setZoom(map.getZoom()+1);
				break;
			case 'zoom_out_click':
				map.setZoom(map.getZoom()-1);
				break;
 */
			case 'add_poi_click':
                poi_edit_buffer = {
                    "fw_core": {
                        "location": {
                            "wgs84": {
                                "latitude": latLng.lat(),
                                "longitude": latLng.lng()
                            }
                        },
                        "categories": [ "_undefined" ],
                        "name": {}
                    }
                };        
                OpenAndEditInPOIEditWindow("Add POI", "Fill-in values for new POI", 
                    poi_edit_buffer, null, POI_edit_cancel, addPOI);
				break;

			case 'center_map_click':
				map.panTo(latLng);
				break;
		}
	});

/* end context menu setup */

/*  POI menu setup
    ==================
*/

	//	create the ContextMenuOptions object
	var POIMenuOptions={};
	POIMenuOptions.classNames={menu:'context_menu', menuSeparator:'context_menu_separator'};
	
	//	create an array of ContextMenuItem objects
	var POImenuItems=[];
	POImenuItems.push({className:'context_menu_item', eventName:'toggle_poi_select_click', label:'Toggle selection'});
	//	a menuItem with no properties will be rendered as a separator
	POImenuItems.push({});
    if (fw_editAllowed) {
        POImenuItems.push({className:'context_menu_item', eventName:'edit_poi_click', label:'Edit this POI'});
        POImenuItems.push({className:'context_menu_item', eventName:'delete_poi_click', label:'DELETE this POI'});
    }
	POIMenuOptions.menuItems=POImenuItems;
	
	//	create the ContextMenu object
	POIMenu=new ContextMenu(map, POIMenuOptions);
	
	//	display the ContextMenu on a Map right click
/*
 	google.maps.event.addListener(map, 'rightclick', function(mouseEvent){
		POIMenu.show(mouseEvent.latLng);
	});
*/	
	//	listen for the ContextMenu 'menu_item_selected' event
	google.maps.event.addListener(POIMenu, 'menu_item_selected', function(latLng, eventName){
		//	latLng is the position of the ContextMenu
		//	eventName is the eventName defined for the clicked ContextMenuItem in the ContextMenuOptions
    
		switch(eventName){
			case 'toggle_poi_select_click': {
				map.setZoom(map.getZoom()+1);
			} break;
			case 'edit_poi_click': {
				map.setZoom(map.getZoom()-1);
			} break;
  	}
	});

/* end POI menu setup */



    
        // HTML5 Geolocation
        
        locate();
        searchPOIs();

    };

    function rad( x ) {
        return x * Math.PI / 180;
    }

    // Distance between two points on a sphere
    function distHaversine( p1, p2 ) {
        var R, dLat, dLong, a, c;

        R = 6378137; // earth's mean radius in m
        dLat = rad( p2.lat() - p1.lat() );
        dLong = rad( p2.lng() - p1.lng() );

        a = Math.sin( dLat / 2 ) * Math.sin( dLat / 2 ) +
            Math.cos( rad( p1.lat() ) ) * Math.cos( rad( p2.lat() ) ) * 
            Math.sin( dLong / 2 ) * Math.sin( dLong / 2 );
        c = 2 * Math.atan2( Math.sqrt( a ), Math.sqrt( 1 - a ) );

        return R * c;
    }


    function parsePOIMsg( msg ) {
        var json = JSON.parse( msg );

        if ( json["Error"] ) {
            log( "Error: " + json["Error"]["msg"] + encodeURIComponent( 
                    json["Error"]["query"] ) );
        } else {
            processPoiData( json );
        }
    }

    function searchPOIs( lat, lng ) {
        var center, searchPoint;
        var restQueryURL;

        if ( !lat || !lng ) {
            center = map.getCenter();
            lat = center.lat();
            lng = center.lng();
        }

        restQueryURL = BACKEND_ADDRESS_POI + "radial_search?" +
           "lat=" + lat + "&lon=" + lng + "&radius=" +
            searchRadius + "&component=fw_core"/* + 
            "&category=pub,cafe,restaurant,cinema"*/;
        miwi_poi_xhr = new XMLHttpRequest();
        
        miwi_poi_xhr.overrideMimeType("application/json");
        
        miwi_poi_xhr.onreadystatechange = function () {
            if(miwi_poi_xhr.readyState === 4) {
                if(miwi_poi_xhr.status  === 200) { 
                    var json = JSON.parse(miwi_poi_xhr.responseText);
                    processPoiData(json);
                }
                else if (miwi_poi_xhr.status === 404) { 
                    console.log("failed: " + miwi_poi_xhr.responseText);
                }
            }
        }

        miwi_poi_xhr.onerror = function (e) {
            log("failed to get POIs" + JSON.stringify(e));
        };

        miwi_poi_xhr.open("GET", restQueryURL, true);
        set_accept_languages(miwi_poi_xhr, [miwi_lang_sel_1.value, 
            miwi_lang_sel_2.value]);
        miwi_poi_xhr.send();
        searchPoint = new google.maps.LatLng( lat, lng );

/*  // Circle removed
        var circle = new google.maps.Circle( {
            strokeWeight: 1,
            fillColor: '#FF0000',
            fillOpacity: 0.10,
            radius: searchRadius,
            center: searchPoint,
            map: map
        } );
*/
        if ( !oldSearchPoints.hasOwnProperty( searchRadius + '' ) ) {
            oldSearchPoints[searchRadius + ''] = [];
        }

        queries[queryID + ''] = {id: queryID, center: searchPoint, 
                radius: searchRadius, ready: false/*, debugShape: circle*/};
        oldSearchPoints[searchRadius + ''].push( queries[queryID + ''] );

        queryID++;


    }

    function findSearchPoint( id ) {
        if ( queries.hasOwnProperty( id + '' ) ) {
            return queries[id + ''];
        }

        return false;
    }

    function storePoi( uuid, poiData ) {
        if ( !poiStorage.hasOwnProperty( uuid ) ) {
            poiStorage[uuid] = poiData;
        }
    }

    function unstorePoi(uuid) {
        if (poiStorage.hasOwnProperty(uuid)) {
            delete poiStorage[uuid];
        }
        if (poiStorageLocal.hasOwnProperty(uuid)) {
            delete poiStorageLocal[uuid];
        }
    }
    
    function setPoiLocal( uuid, key, value ) {
        if ( !poiStorageLocal.hasOwnProperty( uuid )) {
            poiStorageLocal[uuid] = {};
        }
        poiStorageLocal[uuid][key] = value;
    }

    function getPoiLocal( uuid, key ) {
        if ( poiStorageLocal.hasOwnProperty(uuid) ) {
            return poiStorageLocal[uuid][key];
        } else {
            return undefined;
        }
    }
    
    function updateMarker( pos, marker ) {

        var markerOptions =
        {
            //zIndex: 200,
            optimized: false
        };

        marker.setMap( map );
        marker.setPosition( pos );
        marker.setOptions( markerOptions );
    }


    // Geolocation
    function locate() {
        if ( navigator.geolocation ) {
           navigator.geolocation.getCurrentPosition( handleFoundLocation, 
                function () {
                    handleNoGeolocation( true );
                } );
        } else {
            // Browser doesn't support Geolocation
            handleNoGeolocation( false );
        }
    }

    function handleFoundLocation( position ) {
        var pos = new google.maps.LatLng( position.coords.latitude,
            position.coords.longitude );

        updateMarker( pos, homeMarker );
        map.setCenter( pos );

    }

    function handleNoGeolocation( errorFlag ) {
        if ( errorFlag ) {
            alert( 'Error: The Geolocation service failed.' );
        } else {
            alert( 'Error: Your browser doesn\'t support geolocation.' );
        }
        map.setCenter( new google.maps.LatLng( 65.0610432, 
                25.468170099999952 ) );
    }

    function codeAddress() {

        var address = document.querySelector( '#address' ).value;
        geocoder.geocode( { 'address': address}, function ( results, status ) {
            if ( status === google.maps.GeocoderStatus.OK ) {
                map.setCenter( results[0].geometry.location );
                updateMarker( results[0].geometry.location, positionMarker );
            } else {
                alert( 'Geocode was not successful: ' + status );
            }
        } );
    }

    function category_changed() {
        var uuid;
        var categories;
        var visible;
        var marker;
        var cat_item;
        var i;
        poiWindow.setMap(null);

        for (uuid in miwi_poi_pois) {
            categories = getPoiLocal(uuid, 'categories');
            marker = getPoiLocal(uuid, 'marker');
            visible = false;
            for (i = 0; i < categories.length && !visible; i++) {
                cat_item = miwi_poi_pois_by_category[categories[i]];
                visible = cat_item.selected;
            }
            marker.setVisible(visible);
        }
    }

    function processPoiData( data ) {

        var counter = 0, jsonData, poiData, pos, i, uuid, pois,
            contents, locations, location, searchPoint, poiCore,
            wgs84;

        if ( !data ) {
            return;
        }

        if ( !data.hasOwnProperty( "pois" ) ) {
            log( "Error: Invalid POI data." );
            return;
        }

        pois = data['pois'];

        for ( uuid in pois ) {
            poiData = pois[uuid];
            poiCore = poiData.fw_core;
if (poiCore && poiCore.hasOwnProperty("category") && !poiCore.hasOwnProperty("categories")) {
  poiCore.categories = [poiCore.category];
  delete poiCore.category;
}
            if ( poiCore && poiCore.hasOwnProperty( "location" ) ) {
                location = poiCore['location'];
                wgs84 = location.wgs84;
                if ( wgs84 ) {
                    pos = new google.maps.LatLng( wgs84['latitude'], 
                        wgs84['longitude'] );
                    miwi_poi_pois[uuid] = poiData;
                    addPOI_UUID_ToMap( pos, poiCore, uuid );
                    counter++;
               }
            }

            storePoi( uuid, poiCore );
        }

        if ( data.hasOwnProperty( "queryID" ) ) {
            searchPoint = findSearchPoint( data['queryID'] );
            searchPoint['debugShape'].setOptions( {fillColor: "#76EE00"} );
            searchPoint['ready'] = true;
        }

    }

    function findPOIs( pos, radius ) {
        var posString = pos.lat() + "," + pos.lng();

        //TODO: Find POIs from client POI storage using location and radius
    }


    function getPOI( uuid ) {
        if ( poiStorage.hasOwnProperty( uuid ) ) {
            return poiStorage[uuid];
        } else {
            return false;
        }
    }


    function text_by_langs(text_intl, langs) { // : string
/* 
    text_intl - internationalized string with language variants
    langs - array of accepted language codes in descending priority
            "*" for any language.
*/
      var 
        resstring = null,
        deflang, i,
        reslang = "",
        anylang = false;
      
      if(text_intl) {
        if(langs.length == 0) {
          anylang = true;
        } else {
          for(i = 0; (i < langs.length) && (resstring == null); i++) {
            reslang = langs[i];
            resstring = text_intl[reslang] || text_intl[''] || null;
            if(reslang == "*") {
              anylang = true;
              break;
            }
          }
        }
        /*
          Now: we may have resstring, or anylang or neither
        */  
        if (resstring == null) {
          deflang = text_intl["_def"];
          if (deflang) {
            reslang = deflang;
            resstring = text_intl[reslang] || null;
            if(!anylang) reslang = "";
          }
        }
        if ((resstring == null) && anylang) {
          for (i in text_intl) {
            if (i.charAt(0) != '_') {
              resstring = text_intl[i];
              reslang = i;
              if (resstring != null) break;
            }
          }
        }
      } 
      return resstring;
    }

    function categories_by_langs(categories, langs) {
        var result = '';
        for (var i = 0; i < categories.length; i++) {
            try {
                var category = categories[i];
                for (var j = 0; j < categories.length && category != null; j++) {
                    var sub_item = poi_categories[ontology][categories[j]];
                    if (sub_item._parents && sub_item._parents.indexOf(category) >= 0 ) {
                        category == null;
                    }
                }
                if (category) {
                    var category_name 
                        = text_by_langs(poi_categories[ontology][category]._label, langs);
                    if (category_name) {
                        result += ", " + category_name;
                    }
                }
            } catch (e) {
            }
        }
        if (result.length > 2) {
            result = result.substring(2);
        } else {
            result = null;
        }
        return result;
    }
    
/*            
    function set_accept_languages(http_request) {
        var languages = [miwi_lang_sel_1.value, miwi_lang_sel_2.value];
        
        http_request.setRequestHeader('Accept-Language', languages[0]);
        if (languages[1] != "") {
            http_request.setRequestHeader('Accept-Language', languages[1] +
                ';q=0.8');
        }
    }
*/
    function set_accept_languages(http_request, languages) {
        /*
           This function creates an Accept-Languages header to the HTTP request. This
           must be called between http_request.open() and http_request.send() .
        
           http_request - an instance of XMLHttpRequest
           languages    - string array containing the codes of the languages 
                          accepted in the response in descending priority. 
                          The ISO 639-1 language codes are used. If any language 
                          texts are accepted in case of none of the listed 
                          languages are found, an asterisk is used as the last 
                          code.
                          Example: ["en","fi","de","es","*"]
        */
        var i, q;
        
        q = 9;
        for (i = 0; i < languages.length; i++) {
          if (i == 0) {
            http_request.setRequestHeader('Accept-Language', languages[0]);
          } else {
            if (languages[i] != "") {
              http_request.setRequestHeader('Accept-Language', languages[i] +
                  ';q=0.' + q);
              if (q > 1) {
                q--;
              }
            }
          }
        }
    }

    function show_POI_window(poiMarker, uuid) {
        var poi_data, poi_core, name, label, category, icon_string,
            description, url,
            thumbnail, found_label, found_thumbnail;
        var languages = [miwi_lang_sel_1.value, miwi_lang_sel_2.value];
        
        poi_data = miwi_poi_pois[uuid] || {"label": "No information available"};
        poi_core = poi_data.fw_core;
        name = text_by_langs(poi_core["name"], languages);

        category = categories_by_langs(poi_core["categories"], languages) || "";
        thumbnail = poi_core["thumbnail"] || "";
        label = text_by_langs(poi_core["label"], languages) || "";
        description = text_by_langs(poi_core["description"], languages);
        url = text_by_langs(poi_core["url"], languages);
        // Default icon is star !
        icon_string = miwi_poi_icon_strings[category] || "star";
        found_label = (label != "");
        found_thumbnail = (thumbnail != "");
        //map.setZoom(15);
        poiWindow.content
                = '<div id="infoCategory">' + str2html(category) + ' &#32;</div>'
                + '<div id="infoTitle">' + str2html(name) + ' &#32;</div>'
                + '<div id="infoText">'
                + ((found_thumbnail || found_label) ? "<p>" : "")
                + (found_thumbnail ? ('<img src="'
                    + str2html(thumbnail) + '" height="120px"/>') : "")
                + ((found_thumbnail && found_label) ? "<br/>" : "")
                + (found_label ? str2html(label) : "")
                + ((found_thumbnail || found_label) ? "</p>" : "")
                + ((description != "") ? ("<p>" + str2html(description) + "</p>") : "")
                + ((url != "") ?
                    ("<p><a target=\"_blank\" href=\"" + str2html(url) + "\">"
                        + str2html(url) + "</a></p>") : "")
                + '</div>';
        poiWindow.open( map, poiMarker );
    }
        
    function POI_onClick(poiMarker, uuid) {
        var restQueryURL, poi_data, poi_core;
        
        show_POI_window(poiMarker, uuid); // Show existing data immediately
    }


    function addPOI_UUID_ToMap_addListener(poiMarker, op, uuid) {
        /* Anonymous function declaration here creates a closure that binds
           the data in the call stack. An attempt is made to keep the amount of 
           data bound to the closure small.
        */
        google.maps.event.addListener( poiMarker, op, function () {
            POI_onClick(poiMarker, uuid);
        });
    }	
        
    function addPOI_UUID_ToMap( pos, data, uuid ) {
        var poiMarker, contents, content, i, len, poi_data,
            name, label, categories, icon_string, description;
        var poi_data = miwi_poi_pois[uuid];
        var local_name, local_categories;
        var languages = [miwi_languages[0].value, miwi_languages[1].value, "*"];
        var true_category, true_categories;
        var cat_item;
        removePOI_UUID_FromMap(uuid);
        data = data || {};
        name = data["name"] || "N.N.";
        categories = data["categories"] || [data["category"]] || ["_OTHER"];
        // Default icon is star !
        icon_string = (categories.length == 1 && miwi_poi_icon_strings[categories[0]])
                      || "star";
        poi_data.icon_string = icon_string;
        local_name = text_by_langs(name, languages);
        local_categories = (categories && categories_by_langs(categories, languages)) || null;
        poi_data.title = (local_categories ? (local_categories + ": ") : "") + local_name;
        poiMarker = new google.maps.Marker(
            {
                icon: (icon_string == "") ? ("http://" + 
                "chart.apis.google.com/chart?chst=d_map_pin_letter&" + 
                "chld=P|7CFF00|000000") : 
                ("http://chart.apis.google.com/chart?chst=" + 
                "d_map_pin_icon&chld=" + icon_string + 
                "|7CFF00|000000F"),
                title: poi_data.title,
                visible: false,
            } );

        /*
          Add poi to category list for visibility control
        */
        true_categories = [];
        for (i = 0; i < categories.length; i++) {
            true_category = categories[i];
            if (!miwi_poi_pois_by_category[true_category]) {
                true_category = '_OTHER';
            }
            if (true_categories.indexOf(true_category) < 0) {
                true_categories.push(true_category);
            }
        }
        if (true_categories.length == 0) {
            true_categories = ['_OTHER'];
        }
        setPoiLocal(uuid, 'categories', true_categories);

        for (i = 0; i < true_categories.length; i++) {
            true_category = true_categories[i];
            cat_item = miwi_poi_pois_by_category[true_category];

            if (cat_item) { // recognised category
                if (cat_item.markers.indexOf(poiMarker) < 0) {
                    cat_item.markers.push(poiMarker);
                }
                if (cat_item.selected) {
                    poiMarker.setVisible(true);
                }
            }
        }        

        google.maps.event.addListener( poiMarker, "click", function () {
            POI_onClick(poiMarker, uuid);
        });

        var thismarkerOps = new MarkerOps(uuid);

        google.maps.event.addListener(poiMarker, 'rightclick', 
          function(mouseEvent){console.log("rightclick luokka=" + thismarkerOps.luokka);
              thismarkerOps.rightclick(mouseEvent);});

        updateMarker( pos, poiMarker );
        setPoiLocal(uuid, 'marker', poiMarker);
        
        poi_data.selected = false;

    }

    function removePOI_UUID_FromMap( uuid ) {
        var poiMarker = getPoiLocal(uuid, 'marker');
        var true_categories;
        var markers;
        var i;
        var j;
        if (poiMarker) {
            poiMarker.setVisible(false); // hide
            google.maps.event.clearInstanceListeners(poiMarker);
            true_categories = getPoiLocal(uuid, 'categories');
            for (j = 0; j < true_categories; j++) {
                markers = miwi_poi_pois_by_category[true_categories[j]].markers;
                for (i = markers.length; i-- > 0;) {
                    if (markers[i] == poiMarker) {
                        markers.splice(i,1); // remove from visibility control
                    }
                }
            }
            poiMarker.setMap(null); // remove from map
        }
    }


function updateMap() {
        searchPOIs();
    }

    /* Handling of 3D data */
    /* =================== */
       
    function parse3DMsg( msg ) {
        var json = JSON.parse( msg );

        if ( json["Error"] ) {
            log( "Error: " + json["Error"]["msg"] + encodeURIComponent( 
                    json["Error"]["query"] ) );
        } else {
            parse3DData( json );
        }
    }

    function parse3DData( data ) {

        var counter = 0, jsonData, poi_data, pos, i, uuid, pois,
            contents, locations, location, searchPoint, el, comp;

        if ( !data ) {
            return;
        }

        if ( !data.hasOwnProperty( "pois" ) ) {
            log( "Error: Invalid POI data." );
            return;
        }

        pois = data['pois'];

        for ( uuid in pois ) {
            poi_data = pois[uuid];
            if (!miwi_poi_pois[uuid]) {
                miwi_poi_pois[uuid] = poi_data;
            } else {
                for (comp in poi_data) {
                    miwi_poi_pois[uuid][comp] = poi_data[comp];
                }
            }
        }
        
        el = document.getElementById( "log" );
                if ( el !== null ) {
                    el.innerHTML += "<br/><pre>" + 
                    str2html(JSON.stringify(data, null, "  ")) +
                    "</pre><br/>";
                    var d = el.scrollHeight - el.clientHeight;
                    el.scrollTop = d;
//                    el.setAttribute("scrollTop",d);
                };
                

    }

    function show3DOnMap(lon, lat, poi3DData) {
        log("show3DOnMap: lon: " + lon + " lat: " + lat + 
            " poi3DData: " + JSON.stringify(poi3DData, null, "2"));
    }
    
    function category_options(supercategory, indent, category, languages) {
        var newindent = '';
        var result = '';
        var cat_item = miwi_poi_pois_by_category[category];
        var subcategories = cat_item.subcategories;
        var n = subcategories.length;
        var i;
        var subcategory;
        var expansion = '';
        var expandable
            = (category != '_ALL' && category != '_OTHER');
        if (expandable) {
            expandable = false;
            for (i = 0; i < n && !expandable; i++) {
                if (!miwi_poi_pois_by_category[subcategories[i]].deprecated) {
                    expandable = true;
                }
            }
            newindent = indent + "&#xa0;&#xa0;";
            expansion 
                = ( expandable
                    ? indent + (cat_item.expanded == supercategory? "-" : "+")
                      + "&#xa0;"
                    : newindent );
        }
        result = '<p class="'
                 + (cat_item.selected ? 'option_selected' : 'option')
                 + '" data-value="' + category
                 + '" data-parent="' + supercategory + '">'
                 + '<span class="suboption_control">' + expansion + '</span>'
                 + str2html( text_by_langs(cat_item.label, languages)
                             || category )
                 + '</p>';
        if (cat_item.expanded == supercategory) {
            for (i = 0; i < n; i++) {
                subcategory = subcategories[i];
                if ( subcategory.charAt(0) != '_'
                     && !miwi_poi_pois_by_category[subcategory].deprecated )
                {
                    result += category_options(category, newindent,
                                               subcategory, languages);
                }
            }
        }
        return result;
    }
    
    function setCategorySelected(category, selected, all) {
        var cat_item = miwi_poi_pois_by_category[category];
        var i;
        var n;
        if (cat_item.selected != selected) {
            cat_item.selected = selected;
            if (selected || all || !cat_item.expanded) {
                n = cat_item.subcategories.length;
                for (i = 0; i < n; i++) {
                    setCategorySelected(cat_item.subcategories[i], selected, all);
                }
            }
            if (!selected) {
                n = cat_item.supercategories.length;
                for (i = 0; i < n; i++) {
                    setCategorySelected(cat_item.supercategories[i], selected, all);
                }
            }
        }
    }

    var touching = false;

    function mouse_action_none(event) {
        event = event || window.event;
        event.preventDefault();
        event.stopPropagation();
        return false;
    }

    function mouse_action_change_expansion(event) {
        event = event || window.event;
        var category = this.parentNode.getAttribute('data-value');
        var supercategory = this.parentNode.getAttribute('data-parent');
        var cat_item;
        if (category) {
            cat_item = miwi_poi_pois_by_category[category];
            if (cat_item.expanded == supercategory) {
                cat_item.expanded = null;
            } else {
                cat_item.expanded = supercategory;
            }
            namespace.fill_category_list();
        }
        event.preventDefault();
        event.stopPropagation();
        touching = false;
        return false;
    }

    function mouse_action_change_selection(event) {
        event = event || window.event;
        var category = this.getAttribute('data-value');
        var n = elemCategories.childNodes.length;
        var i;
        var fst;
        var nxt;
        var i_elem;
        var i_cat;
        var cat_item;
        if (category) {
            if (touching || event.ctrlKey || event.button == 2) {
                cat_item = miwi_poi_pois_by_category[category];
                setCategorySelected(category, !cat_item.selected);
            } else if (event.shiftKey) {
                fst = n;
                nxt = n;
                for (i = 0; i < nxt; i++) {
                    i_elem = elemCategories.childNodes[i];
                    i_cat = i_elem.getAttribute('data-value');
                    cat_item = miwi_poi_pois_by_category[i_cat];
                    if (cat_item.selected) {
                        fst = i + 1;
                    }
                    if (i_elem == this) {
                        nxt = i + 1;
                        if (fst > i) fst = i;
                    }
                }
                for (i = n; i-- > fst;) {
                    i_elem = elemCategories.childNodes[i];
                    i_cat = i_elem.getAttribute('data-value');
                    cat_item = miwi_poi_pois_by_category[i_cat];
                    setCategorySelected(i_cat, i < nxt, true);
                }
            } else {
                for (i_cat in miwi_poi_pois_by_category) {
                    miwi_poi_pois_by_category[i_cat].selected = false;
                }
                setCategorySelected(category, true);
            }
            namespace.fill_category_list();
            category_changed();
        }
        event.preventDefault();
        event.stopPropagation();
        touching = false;
        return false;
    }
    
    namespace.fill_category_list = function() {
        var option_elems;
        var option_elem;
        var n;
        var i;
        var category;
        var cat_item;
        var parts;
        var part;
        var m;
        var j;
        var languages = [miwi_lang_sel_1.value, miwi_lang_sel_2.value];
        var options = category_options('_ALL', '', '_ALL', languages)
                    + category_options('_ALL', '', '_OTHER', languages);
        elemCategories.innerHTML = options;
        option_elems = elemCategories.childNodes;
        n = option_elems.length;
        for (i = 0; i < n; i++) {
            option_elem = option_elems[i];
            category = option_elem.getAttribute('data-value');
            if (category) {
                cat_item = miwi_poi_pois_by_category[category];
                option_elem.onmousedown = mouse_action_change_selection;
                option_elem.onmouseup = mouse_action_none;
                option_elem.onmousemove = mouse_action_none;
                parts = option_elem.childNodes;
                m = parts.length;
                for (j = 0; j < m; j++) {
                    part = parts[j];
                    if (part.className == 'suboption_control') {
                        part.onmousedown = mouse_action_none;
                        if (cat_item.subcategories.length > 0) {
                            part.onmouseup = mouse_action_change_expansion;
                        } else {
                            part.onmouseup = mouse_action_none;
                        }
                        part.mousemove = mouse_action_none;
                        break;
                    }
                }
            }
        }
    }

    function getStyleKey(list) {
        var i;
        var docEl = document.documentElement;
        for (i = 0; i < list.length; i++) {
            if (list[i] in docEl.style) {
                return list[i];
            }
        }
    }

    var transformKey = getStyleKey(['transform','mozTransform','webkitTransform','oTransform']);

    var zoomState = { idA: null, idB: null, fstDist: 0, curDist: 0, value: 1.0 }; // zoom
    
    function touch_action(event) {
        var touches = event.touches;
        var touchCnt = touches.length;
        var dist;
        var a = 0;
        var b = 1;
        var scaleImg;
        var category;
        touching = true;
        if (touchCnt == 2) {
            dist = touches[0].screenY - touches[1].screenY;
            if (dist < 0) {
                dist = -dist;
                a = 1;
                b = 0;
            }
            if (dist > 20) {
                zoomState.curDist = dist;
                if (zoomState.idA != touches[a].identifier || zoomState.idB != touches[b].identifier) {
                    // start zooming
                    zoomState.idA = touches[a].identifier;
                    zoomState.idB = touches[b].identifier;
                    zoomState.fstDist = dist / zoomState.value;
                } else {
                    // zoom
                    zoomState.value = dist / zoomState.fstDist;
                    if (zoomState.value < 1.0) {
                        zoomState.value = 1.0;
                    } else if (zoomState.value > 16.0) {
                        zoomState.value = 16.0;
                    }
                    scaleImg = zoomState.value.toFixed(3);
                    elemCategories.style[transformKey] = "scale("+scaleImg+","+scaleImg+")";
                }
            }
        } else {
            zoomState.idA = null;
            zoomState.idB = null;
            if (touchCnt == 0) setTimeout(function(){touching = false;}, 100);
        }
    }

    elemCategoriesBox.addEventListener("touchstart", touch_action, false);
    elemCategoriesBox.addEventListener("touchend", touch_action, false);
    elemCategoriesBox.addEventListener("touchcancel", touch_action, false);
    elemCategoriesBox.addEventListener("touchleave", touch_action, false);
    elemCategoriesBox.addEventListener("touchmove", touch_action, false);

    document.getElementById("languages").onchange = function() {
        namespace.fill_category_list();
    }

    namespace.getMap = function() {
        return map;
    }
    
  //window.onload = loadScript;
 
}( window['demo4'] = window.demo4 || {} ));

// alert(document.URL);
