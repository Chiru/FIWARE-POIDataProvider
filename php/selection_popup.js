/* selection_popup.js */
/* Requires string_html.js */

var selection_popup_window;
var selection_popup_callback;
var selection_popup_context;
var selection_popup_key = "";
var selection_popup_timer;

function selection_popup(title, heading, choices, callback, context) {
  selection_popup_callback = callback;
  selection_popup_context = context;
  selection_popup_window=window.open('','selection_popup',
      'toolbar=no,dialog=yes,' + 
      'location=no,directories=no,status=no,menubar=no,resizable=yes,' + 
      'copyhistory=no,scrollbars=yes,width=500,height=600');
      
      
  selection_popup_window.document.head.innerHTML = "<title>" + 
      str2html(title) + 
      "</title>";
  selection_popup_window.document.body.innerHTML = "<h3>" + 
      str2html(heading) + 
      "</h3>\n" +
      '<div id="status"></div><br>';
  for (var key in choices){
    if (choices.hasOwnProperty(key)){
      selection_popup_window.document.body.innerHTML += 
          '<a href="" onclick="selected(\'' +
          key + '\');">' + str2html(choices[key]) + 
          '</a><br>\n';
    }
  }
  selection_popup_window.document.body.innerHTML += 
      '<button onclick="selected(\'\');">' + 
      '<emp>Cancel</emp></button><br>\n';
  selection_popup_window.selected = selection_popup_selected;
  selection_popup_timer = setInterval(selection_popup_check, 500);

}

function selection_popup_check() {
  if (selection_popup_window.closed) {
    selection_popup_callback(selection_popup_context, selection_popup_key);
    clearInterval(selection_popup_timer);
  }
}

function selection_popup_selected(key) {
  selection_popup_key = key;
  selection_popup_window.close();
}

