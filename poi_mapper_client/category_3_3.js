/*
  category
  ----
*/
category_type_handler = {

  ontology: "fw_osm",

  default_value: function(schema) {
    for (var cat in poi_categories[ontology]) {
        if (cat.charAt(0) != '_') {
            return cat;
        }
    }
  },
  
  set_input_field: function(input_field, value) {
    if (typeof(value) == 'string') {
      value = [value];
    }
    input_field.value = value;
    console.log("Setting category field to " + JSON.stringify(value));
    for(var i = 0; i < input_field.options.length; i++) {
      for (var j = 0; j < value.length; j++) {
        if(input_field.options[i].value == value[j]) {
          input_field.options[i].selected = true;
          console.log(value + " selected");
        }
      }
    }
  },
  
  parse_input: function(value) {
    return value;
  },
  
  make_category_options: function(data_item, translate, languages, parent, indent) {
    var result = '';
    if (!indent) indent = '';
    for (var cat in poi_categories[ontology]) {
      if (cat.charAt(0) != '_' && indent.length < 30) {
        var c = poi_categories[ontology][cat];
        if ( c._deprecated ? c._deprecated.indexOf(parent) >= 0
           : c._parents ? c._parents.indexOf(parent) >= 0 : !parent)
        {
          var label = poi_categories[ontology][cat]._label;
          var name = ( translate ? translate(label, languages) 
                                 : label.en || label[''] ) || cat;
          result += '<option value="' + cat + '"'
                    + ( !poi_categories[ontology][cat]._deprecated ? ''
                        : ' style="text-decoration: line-through;"' )
                    + (data_item == cat ? ' selected="true"' : '')
                    + '>' + indent + name + '</option>'
                    + this.make_category_options(data_item, translate, languages,
                            cat, indent + "\u00a0\u00a0");
        }
      }
    }
    return result;
  },
  
  make_form_field: function(input_id, path, data_item, eff_schema) {
    return '<select type="text" id="' + input_id + 
            '" value="' + data_item + '" onchange="sbje.field_changed(' +
            'this.id, this.value)" ' + 
            (eff_schema.title ? 'title="' + eff_schema.title + '" ' : '') + '/>' +
            this.make_category_options(data_item) +
            '</select>';
  }
}

