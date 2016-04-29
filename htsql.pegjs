{
  function extractList(list, index) {
    var result = new Array(list.length), i;

    for (i = 0; i < list.length; i++) {
      result[i] = list[i][index];
    }

    return result;
  }

  function buildList(first, rest, index) {
    return [first].concat(extractList(rest, index));
  }
}

start
  = Segment
  / FieldBlock

Segment
  = segment:"/" segment:Identifier ids:("[" LocatorList "]")? functions:FunctionList? whitespaces? fields:FieldBlock? conditions:ConditionList? { return { name: segment, type: 'Segment', ids: ids? ids[1] : null, functions: functions, fields: fields, conditions: conditions }; }

ConditionList
  = "?" first:Condition rest:("&" Condition)* { return buildList(first, rest, 1); }

Condition
  = left:DottedIdentifier operator:Operator right:Value { return { left: left, operator: operator, right: right } }

FieldBlock
  = "{" fields:FieldList "}" { return fields; }

FunctionList
  = functions:("." Function)* { return extractList(functions, 1); }

Function
  = name:Identifier "(" params:ArgumentList? ")" { return { type: 'Function', name: name, arguments: params }; }

Locator 
  = Value
  / Identifier
 
LocatorList
  = first:Locator rest:(whitespaces? "," whitespaces? Locator)* {
       return buildList(first, rest, 1);
     }
  
ArgumentList
  = first:Identifier rest:("," Identifier)* {
       return buildList(first, rest, 1);
     }

FieldList
  = first:(Field) rest:("," whitespaces? Field)* {
      return buildList(first, rest, 1);
    }

SegmentField
  = name:Identifier

Field
  = name:DottedIdentifier alias:(_ ":as" _ alias:string)? fields:(FieldBlock)? {
    var result = { name: name }
    if (alias) {
      result["alias"] = alias[3];
    }
    if (fields) {
      result["fields"] = fields
    }
    return result;
  }
  / Segment


DottedIdentifier
  = prefix:$"-"? start:varstart chars:dottedchar* {
    return prefix + start + chars.join("");
  }

Identifier
  = prefix:$"-"? start:varstart chars:alphanumeric* {
    return prefix + start + chars.join("");
  }

Operator
  = "="
  / "=~"

varstart
  = [_a-z]i

dottedchar
  = [_a-z0-9-.]i

alphanumeric
  = [_a-z0-9-]i


string
  = string1
  / string2

string1
  = '"' chars:([^\n\r\f\\"] / "\\" nl:nl { return ""; } / escape)* '"' {
      return chars.join("");
    }

string2
  = "'" chars:([^\n\r\f\\'] / "\\" nl:nl { return ""; } / escape)* "'" {
      return chars.join("");
    }

unicode
  = "\\" digits:$(hexDigit hexDigit? hexDigit? hexDigit? hexDigit? hexDigit?) ("\r\n" / [ \t\r\n\f])? {
      return String.fromCharCode(parseInt(digits, 16));
    }

escape
  = unicode
  / "\\" ch:[^\r\n\f0-9a-f]i { return ch; }

chars
  = chars:char+ { return chars.join(""); }

char
  // In the original JSON grammar: "any-Unicode-character-except-"-or-\-or-control-character"
  = [^"\\\0-\x1F\x7f]
  / '\\"'  { return '"';  }
  / "\\\\" { return "\\"; }
  / "\\/"  { return "/";  }
  / "\\b"  { return "\b"; }
  / "\\f"  { return "\f"; }
  / "\\n"  { return "\n"; }
  / "\\r"  { return "\r"; }
  / "\\t"  { return "\t"; }
  / "\\u" digits:$(hexDigit hexDigit hexDigit hexDigit) {
      return chr_unicode(intval($digits, 16));
    }

Value
  = false
  / null
  / true
  / num
  / string

false
  = "false"

true
  = "true"

null
  = "null"

num
  = [+-]? ([0-9]+ / [0-9]* "." [0-9]+) ("e" [+-]? [0-9]+)? {
      return parseFloat(text());
    }

int
  = digit19 digits
  / digit
  / "-" digit19 digits
  / "-" digit

frac
  = "." digits

exp
  = e digits

digits
  = digit+

e
  = [eE] [+-]?

/*
 * The following rules are not present in the original JSON gramar, but they are
 * assumed to exist implicitly.
 *
 * FIXME: Define them according to ECMA-262, 5th ed.
 */

digit
  = [0-9]

digit19
  = [1-9]

hexDigit
  = [0-9a-fA-F]

/* ===== Whitespace ===== */

_ "whitespace"
  = whitespace*

// Whitespace is undefined in the original JSON grammar, so I assume a simple
// conventional definition consistent with ECMA-262, 5th ed.
whitespace
  = [ \t\n\r]

whitespaces
  = whitespace*
nl
  = "\n"
  / "\r\n"
  / "\r"
  / "\f"
