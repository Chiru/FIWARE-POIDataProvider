/* string_html.js v.1.0 2016-05-20 Ari Okkonen / Adminotech Oy

    A package for sanitizing strings before inserting to a html or xhtml page.
    
    html_ent2xml replaces html character entities with numeric representations
                 for xhtml (xml).
                 
    str2html     replaces characters interfering the html syntax or presented
                 badly with appropriate entities or tags.
                 
    safe_html    replaces the "<" character in order not to break the
                 html syntax, and the newline with "<br>" tag to allow
                 some formatting - html entities e.g. &deg; go through as such.
*/

/* NOTE: The following is needed to filter strings for Google InfoWindows. */

/*  html_ent2xml - convert html entities to xml
    ============
   
    This converts entities not allowed in xml to well behaving
    xml numeric character entities.           
    
    html_ent2xml(rawstr: string): string;
        rawstr - string possibly containing html character entities
        
        *result - input string where html entities converted to numeric
        
    Example: "Acme&reg;" -> "Acme&#174;"    
*/        
       
var html_ent2xml_table = {
  // NOTE: max length of the key is 6
  // these XML entities are not changed
  "quot": "quot", // quotation mark (APL quote)
  "amp": "amp", // ampersand
  "apos": "apos", // apostrophe (apostrophe-quote)
  "lt": "lt", // less-than sign
  "gt": "gt", // greater-than sign
  // these HTML entities are changed to numeric
  "nbsp": "#160", // no-break space (non-breaking space)[d]
  "iexcl": "#161", // inverted exclamation mark
  "cent": "#162", // cent sign
  "pound": "#163", // pound sign
  "curren": "#164", // currency sign
  "yen": "#165", // yen sign (yuan sign)
  "brvbar": "#166", // broken bar (broken vertical bar)
  "sect": "#167", // section sign
  "uml": "#168", // diaeresis (spacing diaeresis); see Germanic umlaut
  "copy": "#169", // copyright symbol
  "ordf": "#170", // feminine ordinal indicator
  "laquo": "#171", // left-pointing double angle quotation mark (left pointing guillemet)
  "not": "#172", // not sign
  "shy": "#173", // soft hyphen (discretionary hyphen)
  "reg": "#174", // registered sign (registered trademark symbol)
  "macr": "#175", // macron (spacing macron, overline, APL overbar)
  "deg": "#176", // degree symbol
  "plusmn": "#177", // plus-minus sign (plus-or-minus sign)
  "sup2": "#178", // superscript two (superscript digit two, squared)
  "sup3": "#179", // superscript three (superscript digit three, cubed)
  "acute": "#180", // acute accent (spacing acute)
  "micro": "#181", // micro sign
  "para": "#182", // pilcrow sign (paragraph sign)
  "middot": "#183", // middle dot (Georgian comma, Greek middle dot)
  "cedil": "#184", // cedilla (spacing cedilla)
  "sup1": "#185", // superscript one (superscript digit one)
  "ordm": "#186", // masculine ordinal indicator
  "raquo": "#187", // right-pointing double angle quotation mark (right pointing guillemet)
  "frac14": "#188", // vulgar fraction one quarter (fraction one quarter)
  "frac12": "#189", // vulgar fraction one half (fraction one half)
  "frac34": "#190", // vulgar fraction three quarters (fraction three quarters)
  "iquest": "#191", // inverted question mark (turned question mark)
  "Agrave": "#192", // Latin capital letter A with grave accent (Latin capital letter A grave)
  "Aacute": "#193", // Latin capital letter A with acute accent
  "Acirc": "#194", // Latin capital letter A with circumflex
  "Atilde": "#195", // Latin capital letter A with tilde
  "Auml": "#196", // Latin capital letter A with diaeresis
  "Aring": "#197", // Latin capital letter A with ring above (Latin capital letter A ring)
  "AElig": "#198", // Latin capital letter AE (Latin capital ligature AE)
  "Ccedil": "#199", // Latin capital letter C with cedilla
  "Egrave": "#200", // Latin capital letter E with grave accent
  "Eacute": "#201", // Latin capital letter E with acute accent
  "Ecirc": "#202", // Latin capital letter E with circumflex
  "Euml": "#203", // Latin capital letter E with diaeresis
  "Igrave": "#204", // Latin capital letter I with grave accent
  "Iacute": "#205", // Latin capital letter I with acute accent
  "Icirc": "#206", // Latin capital letter I with circumflex
  "Iuml": "#207", // Latin capital letter I with diaeresis
  "ETH": "#208", // Latin capital letter Eth
  "Ntilde": "#209", // Latin capital letter N with tilde
  "Ograve": "#210", // Latin capital letter O with grave accent
  "Oacute": "#211", // Latin capital letter O with acute accent
  "Ocirc": "#212", // Latin capital letter O with circumflex
  "Otilde": "#213", // Latin capital letter O with tilde
  "Ouml": "#214", // Latin capital letter O with diaeresis
  "times": "#215", // multiplication sign
  "Oslash": "#216", // Latin capital letter O with stroke (Latin capital letter O slash)
  "Ugrave": "#217", // Latin capital letter U with grave accent
  "Uacute": "#218", // Latin capital letter U with acute accent
  "Ucirc": "#219", // Latin capital letter U with circumflex
  "Uuml": "#220", // Latin capital letter U with diaeresis
  "Yacute": "#221", // Latin capital letter Y with acute accent
  "THORN": "#222", // Latin capital letter THORN
  "szlig": "#223", // Latin small letter sharp s (ess-zed); see German Eszett
  "agrave": "#224", // Latin small letter a with grave accent
  "aacute": "#225", // Latin small letter a with acute accent
  "acirc": "#226", // Latin small letter a with circumflex
  "atilde": "#227", // Latin small letter a with tilde
  "auml": "#228", // Latin small letter a with diaeresis
  "aring": "#229", // Latin small letter a with ring above
  "aelig": "#230", // Latin small letter ae (Latin small ligature ae)
  "ccedil": "#231", // Latin small letter c with cedilla
  "egrave": "#232", // Latin small letter e with grave accent
  "eacute": "#233", // Latin small letter e with acute accent
  "ecirc": "#234", // Latin small letter e with circumflex
  "euml": "#235", // Latin small letter e with diaeresis
  "igrave": "#236", // Latin small letter i with grave accent
  "iacute": "#237", // Latin small letter i with acute accent
  "icirc": "#238", // Latin small letter i with circumflex
  "iuml": "#239", // Latin small letter i with diaeresis
  "eth": "#240", // Latin small letter eth
  "ntilde": "#241", // Latin small letter n with tilde
  "ograve": "#242", // Latin small letter o with grave accent
  "oacute": "#243", // Latin small letter o with acute accent
  "ocirc": "#244", // Latin small letter o with circumflex
  "otilde": "#245", // Latin small letter o with tilde
  "ouml": "#246", // Latin small letter o with diaeresis
  "divide": "#247", // division sign (obelus)
  "oslash": "#248", // Latin small letter o with stroke (Latin small letter o slash)
  "ugrave": "#249", // Latin small letter u with grave accent
  "uacute": "#250", // Latin small letter u with acute accent
  "ucirc": "#251", // Latin small letter u with circumflex
  "uuml": "#252", // Latin small letter u with diaeresis
  "yacute": "#253", // Latin small letter y with acute accent
  "thorn": "#254", // Latin small letter thorn
  "yuml": "#255" // Latin small letter y with diaeresis
  // other entities are ignored 
};

function html_ent2xml (rawstr) {
  var result = "";
  var code;
  var rawlen; // rawstr length
  var elen; // entity length
  var sename, tename; // source and target entity names
  var i;
  
  if (!rawstr) {
      rawstr = "";
  }
  
  rawlen = rawstr.length;
  i = 0;
  while ( i < rawlen) {
    code = rawstr.charCodeAt(i);
    if (code == 0x26) { // ampersand, possible html-only entity
      elen = rawstr.indexOf(";", i) - i - 1;
      if ((elen > 0) && (elen < 7)) { // max entity length == 6
        sename = rawstr.substr(i + 1, elen);
        tename = html_ent2xml_table[sename];
        if(tename != undefined) { // if replacement found
          result += "&" + tename + ";";
          i += elen + 1; // skip source entity
          code = -1; // mark processed
        }
      }
    }
    if (code > -1) {
      if (code < 0x7f) {
          result = result + (str2html_table[rawstr[i]] ? 
      (str2html_table[rawstr[i]]) : (rawstr[i]));
      } else {
          result = result + "&#x" + code.toString(16) + ";";
      }
    }
    i++;
  }
  return result;
}

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
  "\n": "<br></br>"
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

var safe_html_table = {
  "<": "&lt;",
  "\n": "<br>"
};

function safe_html(rawstr) {
  var result = "";
  var code;
  if (!rawstr) {
    rawstr = "";
  }
  for (var i = 0; i < rawstr.length; i++) {
    code = rawstr.charCodeAt(i);
    if (code < 0x7f) {
      result = result + (safe_html_table[rawstr[i]] ? 
          (safe_html_table[rawstr[i]]) : (rawstr[i]));
    } else {
      result = result + "&#x" + code.toString(16) + ";";
    }
  }
  return result;
}
