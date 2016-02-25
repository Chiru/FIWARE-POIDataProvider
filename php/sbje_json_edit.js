/* sbje_json_edit.js 0.2 2014-09-18 Ari Okkonen

  Schema Based JSON Edit
  
  This facilitates editing data in a web page. Data needs to be described
  using JSON Schema.
  
  For the editor put an element with id to the web page. E.g.

    <div id="editor1">
    </div>
  
  The outer structure of the data to be edited must be object. 
  Minumum requirement for a new data item is an empty object: {}.
  
  To create the editor use JavaScript function call
    sbje.make_form(element_id, schema, data_object);
      where
        element_id is the id of the html element for the editor e.g. "editor1"
        schema is the JSON Schema describing the data
        data_object is the data object to be edited.
        
  To see the data during editing include an element having id="sbje.dump" to
  the web page. 
  
  Example:
  
    <body>
    Data Editor
    <div id="my_editor">
    </div>
    </body>
    <script>
      var test_data = {}; // data to be edited
      // Create and start the editor
      sbje.make_form("my_editor", schema, test_data);
    </script>
    
    This uses global names. All global names begin with "sbje.".
    
  Restrictions
    
    Only minimum functionality is currently available.
    
  Author: Ari Okkonen, University of Oulu, CIE
*/

"use strict"; // to find undeclared variables etc.
var sbje = {}; // Namespace object
// NOTE: Edit this, when moved to new location!

{
  var endpath = document.URL.lastIndexOf("/");
  sbje.path = document.URL.slice(0,endpath + 1);
}

sbje.icons = {
  block_closed: sbje.path + "Left_triangle_icon.gif",
  block_open: sbje.path + "Right_low_triangle_icon.gif",
  del: sbje.path + "delete_icon.gif",
  info: sbje.path + "info_icon.gif",
  add: sbje.path + "add_icon.gif"
};
  

sbje.str2html_table = {
        "<": "&lt;",
        "&": "&amp;",
        "\"": "&quot;",
        "'": "&apos;",
        ">": "&gt;"
};

sbje.str2html = function  (rawstr) {
  var result = "";
  for (var i = 0; i < rawstr.length; i++) {
    result = result + (sbje.str2html_table[rawstr[i]] ? 
        (sbje.str2html_table[rawstr[i]]) : (rawstr[i]));
  }
  return result;
}

sbje.section_open = {}; // indicates open sections by "path": true
      
sbje.split_path = function (path, head_tail) { // sets tail == null for last item
// because empty string is a valid key
  var period_pos, l_brac_pos, r_brac_pos, pos, fchar;
  
  period_pos = path.indexOf(".");
  l_brac_pos = path.indexOf("[");
  r_brac_pos = path.indexOf("]");
  
  if(period_pos < 0) period_pos = 3000;
  if(l_brac_pos < 0) l_brac_pos = 3000;
  if(r_brac_pos < 0) r_brac_pos = 3000;
  pos = period_pos;
  if(l_brac_pos < pos) pos = l_brac_pos;
  if(r_brac_pos < pos) pos = r_brac_pos;
  
  if (pos > 2900) {
    head_tail.head = path;
    head_tail.tail = null;
  } else {
    head_tail.head = path.slice(0, pos);
    head_tail.tail = path.substr(pos + 1);
    
    fchar = head_tail.tail.charAt(0);
    while ((fchar == ".") || (fchar == "[") || (fchar == "]")) {
      head_tail.tail = head_tail.tail.substr(1);
    }
  }
}

sbje.get_doc = function(path) {
  var head_tail = {};
  var doc;
  sbje.split_path(path, head_tail);
  doc = sbje.form_bindings[head_tail.head].doc;
  return doc;
}

sbje.analyze_id = function (id) {
  var result;
  var path;
  var id_body;
  var head_tail = {
    head: null,
    tail: null
  };
  var cur_node;
  var cur_schema;
  var binding;
  var node_type;
  var form_slot;
  var parent_type;
  var parent_path;
  var parent_schema;
  var parent_node;
  var parent_key;
  var specifier;
  var id_body;

  /* remove tag at the beginning "xyz:" */
  id_body = id.slice(id.indexOf(":") + 1);
  specifier = id.slice(0, id.indexOf(":") - 1);
  path = sbje.html_id2path(id);
  var doc = sbje.get_doc(path);
  var add_icon_el = doc.getElementById("ai:" + id_body);
  var field_el = doc.getElementById("f:" + id_body);
  var input_field = doc.getElementById("i:" + id_body);
  /* find data and schema */
  sbje.split_path(path, head_tail);
  parent_path = head_tail.head;
  binding = sbje.form_bindings[head_tail.head];
  cur_node = binding.data;
  cur_schema = binding.schema;
  /* find the correct field */
  sbje.split_path(head_tail.tail, head_tail);
  while (head_tail.tail != null) {
    parent_path = parent_path + "." + head_tail.head;
    parent_node = cur_node;
    cur_node = cur_node[head_tail.head];
    cur_schema = sbje.get_effective_schema(cur_schema, binding.schema);
    cur_schema = sbje.get_sub_schema(cur_schema, head_tail.head);
//    cur_schema = cur_schema.properties[head_tail.head];
    parent_key = head_tail.head;
    sbje.split_path(head_tail.tail, head_tail);
  }
  cur_schema = sbje.get_effective_schema(cur_schema, binding.schema);
  parent_type = sbje.get_schema_type(cur_schema);
  parent_schema = cur_schema;
  cur_schema = sbje.get_sub_schema(cur_schema, head_tail.head);
  //  cur_schema = cur_schema.properties[head_tail.head];
  cur_schema = sbje.get_effective_schema(cur_schema, binding.schema);
  node_type = sbje.get_schema_type(cur_schema);
  
  result = {};
  result.binding = binding;
  result.doc = doc;
  result.field_id = id_body;
  result.specifier = specifier;
  
  result.elements = {
    add_icon: add_icon_el,
    field: field_el,
    input: input_field
  };
    
  result.target = {
    node: cur_node,
    path: path,
    key: head_tail.head,
    schema: cur_schema,
    type: node_type
  };
  
  result.parent = {
    node: parent_node,
    path: parent_path,
    key: parent_key,
    schema: parent_schema,
    type: parent_type
  }

  return result;
}

sbje.field_changed = function(id, value) {
  var p = sbje.analyze_id(id);
  var type_handler;
// alert("field changed");
  type_handler = sbje.type_handler[p.target.type];

  if(type_handler) {
    p.target.node[p.target.key] = type_handler.parse_input(value);
//    console.log("field_changed -> " + cur_node[p.target.key]);
  }
};

sbje.get_schema_type = function(schema) {
  var type;
  
  if (schema.fw_type) {
    type = schema.fw_type; // for special editing fields
  } else if (schema.enum) {
    type = "enum";
  } else {
    type = schema.type;
  };
  
  return type;
}

sbje.get_sub_schema = function(schema, key) {
  var result = null;
  var type;
  
  if((key != undefined) && schema) {
    type = sbje.get_schema_type(schema);
    if(type == "object") {
      result = schema.properties[key];
    } else if (type == "array") {
      result = schema.items;
    }
  }
  
  return result;
}

sbje.add_field = function (id) {
  var p = sbje.analyze_id(id);
  var form_slot;

  /* Store default value here */
// Todo: real default values must be taken from schema cur_schema.default
  switch (p.target.type) {
    case "object": {
      p.target.node[p.target.key] = {};
      form_slot =   p.doc.getElementById("sf:" + p.field_id);
      sbje.section_open[p.target.path] = true; // open, when created
      form_slot.innerHTML = sbje.make_object_form_internals(p.target.path, 
        p.target.schema, p.target.node,
        p.target.key, p.binding.schema);
    } break;
    case "array": {
      p.target.node[p.target.key] = [];
      form_slot = p.doc.getElementById("sf:" + p.field_id);
      sbje.section_open[p.target.path] = true; // open, when created
      form_slot.innerHTML = sbje.make_array_form_internals(p.target.path, 
        p.target.schema, p.target.node, p.target.key, p.binding.schema);
    } break;
    default: {
      var type_handler = sbje.type_handler[p.target.type];
      var value;

      if(type_handler) {
        value = type_handler.default_value(p.target.schema);
        p.target.node[p.target.key] = value;
        type_handler.set_input_field(p.elements.input, value);
          setTimeout(function(){p.elements.input.focus();},100);      
      }
    }
  }
  if(p.elements.field) p.elements.field.style.display = "inline";
  if(p.elements.add_icon) p.elements.add_icon.style.display = "none";
  
  if(p.parent.type == "array") {
    form_slot =   p.doc.getElementById("sf:" + sbje.path2id_body(p.parent.path));
    form_slot.innerHTML = sbje.make_array_form_internals(p.parent.path, p.parent.schema, 
      p.parent.node, p.parent.key, p.binding.schema);
  }
};


sbje.delete_field = function (id) {
  var p = sbje.analyze_id(id);
  var form_slot;

  /* check, if allowed */
  if (p.parent.schema.type == "array") {
    if (p.parent.schema.minItems && 
        (p.parent.schema.minItems >= p.target.node.length)) {
      alert("This array requires at least " + p.parent.schema.minItems + 
          " items.");
      return;
    }
  } else if (p.parent.schema.type == "object") {
    if (p.parent.schema.required) {
      var req_i;
      
      for(req_i = 0; req_i < p.parent.schema.required.length; req_i++) {
        if(p.parent.schema.required[req_i] == p.target.key) {
          alert("The field " + p.target.key + " is required.");
          return;
        }
      }
    }
  }
  /* remove field */
  console.log(JSON.stringify(p));
  p.target.node[p.target.key] = undefined;
  if (p.target.type == "object") {
    form_slot =   p.doc.getElementById("sf:" + p.field_id);
    form_slot.innerHTML = sbje.make_object_form_internals(p.target.path, 
        p.target.schema, 
        p.target.node, p.target.key);
  } else if (p.target.type == "array") {
    form_slot =   p.doc.getElementById("sf:" + p.field_id);
    form_slot.innerHTML = sbje.make_array_form_internals(p.target.path, 
        p.target.schema, 
        p.target.node, p.target.key);
  }
  if(p.elements.field) p.elements.field.style.display = "none";
  if(p.elements.add_icon) p.elements.add_icon.style.display = "inline";
  
  if(p.parent.type == "array") {
    p.target.node.splice(parseInt(p.target.key),1);
  
    form_slot =   p.doc.getElementById("sf:" + sbje.path2id_body(p.parent.path));
    form_slot.innerHTML = sbje.make_array_form_internals(p.parent.path, 
        p.parent.schema, 
        p.parent.node,  p.parent.key, p.binding.schema);
  }
};

sbje.infowin = null;

sbje.description = function (id) {
  var p = sbje.analyze_id(id);

  
  var t_string = p.target.schema.title;
  var d_string = p.target.schema.description;

  if (sbje.infowin && !sbje.infowin.closed) {
    sbje.infowin.focus();
  } else {
    sbje.infowin=window.open('','description_popup','toolbar=no,dialog=yes,' + 
      'location=no,directories=no,status=no,menubar=no,resizable=yes,' + 
      'copyhistory=no,scrollbars=yes,width=480,height=320');
  }
  sbje.infowin.document.head.innerHTML = "<title>" + (t_string ? t_string  :
      p.target.key) + "</title>";
  sbje.infowin.document.body.innerHTML = "<code>" + p.target.path + "</code><br/>" +
      (t_string ? ("<h2>" + t_string + "</h2>") : "") +
      d_string;
};

sbje.toggle_section = function (path) {
  var head_tail = {};
  sbje.split_path(path, head_tail);
  var doc = sbje.get_doc(path);
  var field_id = sbje.path2id_body(path);
  var image = doc.getElementById( "oi:" + field_id );
  var block_style = doc.getElementById("b:" + field_id).style;
  var block_closed_style = doc.getElementById("bc:" + field_id).style;
  
  if (sbje.section_open[path]) {
    image.src = sbje.icons.block_closed;
    sbje.section_open[path] = false;
    block_style.display = "none";
    block_closed_style.display = "inherit";
/*
    block_style.visibility = "hidden";
    block_closed_style.visibility = "visible";
*/
  } else {
    image.src = sbje.icons.block_open;
    sbje.section_open[path] = true;
    block_style.display = "inherit";
    block_closed_style.display = "none";
  };
};

/* BEGIN form stuff 
   ================
*/

sbje.make_object_form_internals = function (path, schema, data, key, schema_root) { // : string
  var result = "";
  var data_exists = (data[key] != undefined) && (data[key] != null);
  var subkey;
  var shown_data;
  var info_icon = "";
  var is_open = sbje.section_open[path];
  var field_id = sbje.path2id_body(path);

  if ( schema.description ) {
    info_icon = ' <img src="' + sbje.icons.info + '" alt="info" ' + 
      'onclick="sbje.description(\'i:' + field_id + '\')" />'; 
  };

  
  if (data_exists) {
 
    // info icon for description

    
    shown_data = data[key];
    result += 
        '<span id = "f:' + field_id + '" style="display:inline">' +
          '<span style="position:relative;left:-20">' + 
            '<img id="oi:' + field_id + 
              '" src="' + (is_open ? sbje.icons.block_open : sbje.icons.block_closed) + '" ' +
              'onclick="sbje.toggle_section(\'' + path + '\')"/>' +
            key + ':  {' +
            '<span id="bc:' + field_id + '" style="display:' + 
            (is_open ? 'none' : 'inherit') + '">' + '...}' +
            info_icon + '</span>' +
         '</span>' +
          '<span id="b:' + field_id + '" style="display:' + 
          (is_open ? 'inherit' : 'none') + '">' +
            '<span style="position:relative;left:-20">' +
            '<img src="' + sbje.icons.del + '" alt="delete field" ' +
              'onclick="sbje.delete_field(\'i:' + field_id + '\')" ' + 
              'title="delete field" />' +
            info_icon + '</span><div style="position:relative;left:20">';
    for ( subkey in schema.properties ) {
      result += sbje.make_sub_form(path + "." + subkey, schema.properties[subkey], 
          shown_data, subkey, schema_root);
    }
    result += '</div>' +
              '}' +
          '</span>' +
        '</span>';
  } else {
    result += 
        '<span id="ai:' + field_id + '" style="display:inline">' +
          key + ':' +
          '<img src="' + sbje.icons.add + '" alt="add field" ' +
            'onclick="sbje.add_field(\'i:' + field_id + '\')" title="add field"/>' +
          info_icon;
    // info icon for description
              
            
    result += '</span>';
      
  };
  
  return result;
}

sbje.make_array_form_internals = function (path, schema, data, key, schema_root) { // : string
  var result = "";
  var data_exists = (data[key] != undefined) && (data[key] != null);
  var i;
  var shown_data;
  var info_icon = "";
  var is_open = sbje.section_open[path];
  var field_id = sbje.path2id_body(path);

  if ( schema.description ) {
    info_icon = ' <img src="' + sbje.icons.info + '" alt="info" ' + 
      'onclick="sbje.description(\'i:' + field_id + '\')" />'; 
  };

  
  if (data_exists) {
 
    // info icon for description

    
    shown_data = data[key];
    result += 
        '<span id = "f:' + field_id + '" style="display:inline">' +
          '<span style="position:relative;left:-20">' + 
            '<img id="oi:' + field_id + '" src="' + 
            (is_open ? sbje.icons.block_open : sbje.icons.block_closed) + 
            '" ' + 'onclick="sbje.toggle_section(\'' + path + '\')"/>' +
            key + ':  [' +
            '<span id="bc:' + field_id + '" style="display:' + 
            (is_open ? 'none' : 'inherit') + '">' + '...]' +
            info_icon + '</span>' +
         '</span>' +
          '<span id="b:' + field_id + '" style="display:' + 
          (is_open ? 'inherit' : 'none') + '">' +
            '<span style="position:relative;left:-20">' +
            '<img src="' + sbje.icons.del + '" alt="delete field" ' +
              'onclick="sbje.delete_field(\'i:' + field_id + '\')" ' + 
              'title="delete field" />' +
            info_icon + '</span><div style="position:relative;left:20">';
            
    for ( i = 0; i < shown_data.length; i++ ) {
      result += sbje.make_sub_form(path + "." + i, schema.items, 
          shown_data, /*"[" +*/ i /*+ "]"*/, schema_root);
    }
    result += sbje.make_sub_form(path + "." + shown_data.length, schema.items, 
        shown_data, /*"[" +*/ shown_data.length /*+ "]"*/, schema_root);

/*
    for ( subkey in schema.properties ) {
      result += sbje.make_sub_form(path + "." + subkey, schema.properties[subkey], 
          shown_data, subkey, schema_root);
    }
*/
    
    result += '</div>' +
              ']' +
          '</span>' +
        '</span>';
  } else {
    result += 
        '<span id="ai:' + field_id + '" style="display:inline">' +
          key + ':' +
          '<img src="' + sbje.icons.add + '" alt="add field" ' +
            'onclick="sbje.add_field(\'i:' + field_id + '\')" title="add field"/>' +
          info_icon;
    // info icon for description
              
            
    result += '</span>';
      
  };
  
  return result;
}

sbje.make_object_form = function (path, schema, data, key, schema_root) { // : string
  var field_id = sbje.path2id_body(path);
  var result = '<div id="sf:' + field_id + '">';
  
  result += sbje.make_object_form_internals(path, schema, data, key, schema_root);
  result += '</div>';
  
  return result;
};

sbje.make_array_form = function (path, schema, data, key, schema_root) { // : string
  var field_id = sbje.path2id_body(path);
  var result = '<div id="sf:' + field_id + '">';
  
  result += sbje.make_array_form_internals(path, schema, data, key, schema_root);
  result += '</div>';
  
  return result;
};

/*
  Elementary field editor generators
  ==================================

  boolean
  -------
*/
sbje.boolean_type_handler = {
  default_value: function(schema) {
    return false;
  },

  set_input_field: function(input_field, value) {
    input_field.checked = value;
  },
  
  parse_input: function(value) {
    return value;
  },
  
  make_form_field: function(input_id, path, data_item, eff_schema) {
    var field_id = sbje.path2id_body(path);
    var  result = '<input type="checkbox" id="' + input_id + '"'+ 
        ' name="n:' + field_id + '"' +
        ( data_item ? ' checked="' + data_item + '"' : '' ) + 
         
        ' onchange="sbje.field_changed(this.id, this.checked)" ' + 
        (eff_schema.title ? 'title="' + eff_schema.title + '" ' : '') + '/>';
        
    return result;
  }
}

/*
  enum
  ----
*/
sbje.enum_type_handler = {
  default_value: function(schema) {
    return schema.enum[0];
  },
  
  set_input_field: function(input_field, value) {
    input_field.value = value;
    console.log("Setting enum field to " + value);
    for(var i = 0; i < input_field.options.length; i++) {
      if(input_field.options[i].value == value) {
        input_field.options[i].selected = true;
        console.log(value + " selected");
      }
    }
  },
  
  parse_input: function(value) {
    return value;
  },
  
  make_form_field: function(input_id, path, data_item, eff_schema) {
    var result = '<select type="text" id="' + input_id + 
      '" value="' + data_item + '" onchange="sbje.field_changed(' +
      'this.id, this.value)" ' + 
      (eff_schema.title ? 'title="' + eff_schema.title + '" ' : '') + '/>';
      for ( var i = 0; i < eff_schema.enum.length; i++ ) {
        result += '<option value="' + eff_schema.enum[i] + '"' + 
        (data_item == eff_schema.enum[i] ? ' selected="true"' : '' ) + '>' + 
        eff_schema.enum[i] + '</option>';
      }
      result += '</select>';

    return result;
  }
}

/*
  string
  ------
*/
sbje.string_type_handler = {
  default_value: function(schema) {
    return "";
  },

  set_input_field: function(input_field, value) {
    input_field.value = value;
  },
  
  parse_input: function(value) {
    return value;
  },
  
  make_form_field: function(input_id, path, data_item, eff_schema) {
    var result = '<input type="text" id="' + input_id + '" width="400" ' + 
      'value="' + data_item + '" onchange="sbje.field_changed(this.id, this.value)" ' + 
      (eff_schema.title ? 'title="' + eff_schema.title + '" ' : '') + 
      (eff_schema.maxLength ? 'maxlength="' + eff_schema.maxLength + '" ' :
      '') + '/>';

    return result;
  }
};

/*
  number, integer
  ---------------
*/
sbje.numeric_type_handler = {
  default_value: function(schema) {
    return 0;
  },

  set_input_field: function(input_field, value) {
    input_field.value = value;
  },
  
  parse_input: function(value) {
    return parseFloat(value);
  },
  
  make_form_field: function(input_id, path, data_item, eff_schema) {
    var result = '<input type="text" id="' + input_id + '" width="400" ' + 
      'value="' + data_item + '" onchange="sbje.field_changed(this.id, this.value)" ' + 
      (eff_schema.title ? 'title="' + eff_schema.title + '" ' : '') + '/>';

    return result;
  }
};

sbje.number_type_handler = sbje.numeric_type_handler;

sbje.integer_type_handler = {
  default_value: sbje.numeric_type_handler.default_value,

  set_input_field: sbje.numeric_type_handler.set_input_field,
  
  parse_input: function(value) {
    return parseInt(value);
  },
  
  make_form_field: sbje.numeric_type_handler.make_form_field
};

sbje.type_handler  = {
  "string": sbje.string_type_handler,
  "integer": sbje.integer_type_handler,
  "number": sbje.number_type_handler,
  "boolean": sbje.boolean_type_handler,
  "enum": sbje.enum_type_handler,
}

/* path2input_id replaces empty key by '_' and doubles an underscore at the 
   beginning of the key. Moreover, it prepends the key with 'i:'.
*/
sbje.path2html_id = function(specifier, path) {
  var result;

  result = specifier + ':' + sbje.path2id_body(path);

  return result;
}

sbje.path2id_body = function(path) {
  var result;
  var parts = path.split('.');
  var i;
  
  for (i = 0; i < parts.length; i++) {
    if(parts[i] == '') {
      parts[i] = '_';
    } else {
      var c = parts[i].charAt(0);
      if((c  == '_') || (c == '[')) {
        parts[i] = '_' + parts[i];
      }
    }
  }
  result = parts.join('.');

  return result;
}

sbje.html_id2path = function(id) {
  var body = id.slice(id.indexOf(":")+1);
  var result;
  var parts = body.split('.');
  var i;
  
  for (i = 0; i < parts.length; i++) {
    if(parts[i].charAt(0)  == '_') {
      parts[i] = parts[i].slice(1);
    }
  }
  result = parts.join('.');

  return result;
};

sbje.make_generic_form = 
    function (path, schema, type_handler, data, key, schema_root) { // : string
  var result = key + ": ";
  var add_visibility = "inline";
  var field_visibility = "none";
  var shown_data;
  var eff_schema = sbje.get_effective_schema(schema, schema_root);
  var id_body = sbje.path2id_body(path);
  var input_id = 'i:' + id_body;
  
  // initial value by externally provided function 
 shown_data = type_handler.default_value(eff_schema);
  if ((data[key] != undefined) && (data[key] != null)) {
    add_visibility = "none";
    field_visibility = "inline";
    shown_data = data[key];
  }

  // add-field icon
  result += '<span id="ai:' + id_body + '" style="display:' + 
    add_visibility + '">' +
    '<img src="' + sbje.icons.add + '" alt="add field" onclick="sbje.add_field(\'i:' + 
    id_body + '\')" title="add field"/></span>';
  // begin field editor
  result += '<span id = "f:' + id_body + '" style="display:' + 
    field_visibility + '">';
  // edit box by externally provided function
  
  result += type_handler.make_form_field(input_id, path, shown_data, eff_schema);
  // delete icon
  result += ' <img src="' + sbje.icons.del + '" alt="delete field" ' + 
    'onclick="sbje.delete_field(\'i:' + id_body + '\')" title="delete field"/>';
  
  // end field editor
  result += '</span>';
  // info icon for description
  if ( eff_schema.description ) {
    result += ' <img src="' + sbje.icons.info + '" alt="info" ' + 
      'onclick="sbje.description(\'i:' + id_body + '\')" />'; 
  }
  result += '<br/>';
  return result;
}




sbje.get_reference = function (ref, schema_root) {
  var path = ref.split("/");
  var node = schema_root;
  var i;
  
  if(path[0] = "#") { // we process only internal references
    for ( i = 1; i < path.length; i++) {
      if(node) node = node[path[i]];
    }
  } else {
    node = undefined;
  }
  if(node == undefined) node = null;
  
  return node;
}

sbje.get_effective_schema = function (schema, schema_root) { // : schema
  var eff_schema = schema;
  var subkey;
  
  if(schema["$ref"]) {
    eff_schema = sbje.get_reference(schema["$ref"], schema_root);
    if ( !eff_schema ) console.log("Bad ref? " + JSON.stringify(schema["$ref"]));
    for ( subkey in schema ) {
      if ( subkey != "$ref" ) {
        eff_schema[subkey] = schema[subkey];
      }
    }
  }
  return eff_schema;
}


sbje.make_sub_form = function (path, schema, data, key, schema_root) { // : string
  var eff_schema = schema;
  var result = "";
  var node_type;
  var subkey;
  
  eff_schema = sbje.get_effective_schema(schema, schema_root);
  node_type = sbje.get_schema_type(eff_schema);
  if (node_type) {
    switch (node_type) {
      case "object": {
        result = sbje.make_object_form(path, eff_schema, data, key, schema_root);
      } break;
      case "array": {
        result = sbje.make_array_form(path, eff_schema, data, key, schema_root);
      } break;
      default: {
        if(sbje.type_handler[node_type]) {
          result = sbje.make_generic_form(path, eff_schema, 
              sbje.type_handler[node_type], data, key, schema_root);
        } else {
          result = "Data type " + node_type + " not supported by the editor.";
        }
      }
    }
  }
  return result;
}

sbje.form_bindings = {}; // {"id": {"data": DataObject1, "schema": Schema1,
                             // "document": Document},...}

sbje.make_form = function (id, schema, data, doc) {
  /*
     id     - the element Id of the form in the document
            - internally used to find the data from form_bindings
     schema - JSON Schema describing the data
     data   - the data to be shown and edited
     doc    - document object containing the editor
  */
  var div_generated = doc.getElementById( id );
  var form_string;
  var properties, key;

// console.log("schema = " + JSON.stringify(schema).slice(0, 15));  
  
  if(schema.type == "object") {
    sbje.form_bindings[id] = {"data": data, "schema": schema, "doc": doc};  
    form_string = '<div style="position:relative;left:20">';
    properties = schema.properties;

    for (key in properties) {
      form_string += sbje.make_sub_form(id + "." + key, properties[key], 
        data, key, schema);
    }
    
    form_string += "</div>";
  } else {
    form_string = "<pre>Internal ERROR: make_form requires object as topmost " +
      "data element. Got " + schema.type + ".</pre>";
  };
  div_generated.innerHTML = form_string;
}

sbje.remove_form = function (id) {
  var doc = sbje.get_doc(id);
  var div_generated = doc.getElementById( id );
  
  sbje.form_bindings[id] = undefined;
  div_generated = "";
}

/*
  0.2  2014-09-18  aok  delete_field does not delete required data.
  
*/

 
