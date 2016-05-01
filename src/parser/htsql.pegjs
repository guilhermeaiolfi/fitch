{
  if (!function_exists('\fitch\parser\extractList')) {
    function extractList($list, $index) {
      $len = count($list);
      $result = array();

      for ($i = 0; $i < $len; $i++) {
        $result[$i] = $list[$i][$index];
      }

      return $result;
    }
  }



  if (!function_exists('\fitch\parser\buildList')) {
    function buildList($first, $rest, $index) {
      return array_merge(array($first),extractList($rest, $index));
    }
  }
}

start
  = Segment
  / FieldBlock

Segment
  = segment:"/" segment:DottedIdentifier ids:("[" LocatorList "]")? functions:FunctionList? whitespaces? fields:FieldBlock? conditions:ConditionList? { return array( "name" => $segment, "type" => 'Segment', "ids" => $ids? $ids[1] : null, "functions" => $functions, "fields" => $fields, "conditions" => $conditions ); }

ConditionList
  = "?" first:Condition rest:("&" Condition)* { return buildList($first, $rest, 1); }

Condition
  = left:DottedIdentifier operator:Operator right:Value { return array( "left" => $left, "operator" => $operator, "right" => "right" ); }

FieldBlock
  = "{" fields:FieldList "}" { return $fields; }

FunctionList
  = functions:("." Function)* { return extractList($functions, 1); }

Function
  = name:"sort" "(" params:SortList? ")" { return array( "type" => 'Function', "name" => $name, "arguments" => $params ); }
  / name:Identifier "(" params:ArgumentList? ")" { return array( "type" => 'Function', "name" => $name, "arguments" => $params ); }

SortDirection
  = "-"
  / "+"

Locator
  = Value
  / Identifier

LocatorList
  = first:Locator rest:(whitespaces? "," whitespaces? Locator)* {
       return buildList($first, $rest, 1);
     }

SortList
  = first:(ColumnIdentifier SortDirection?) whitespaces? rest:("," whitespaces? (ColumnIdentifier SortDirection?))* {
       return buildList($first, $rest, 2);
     }

ArgumentList
  = first:Locator rest:(whitespaces? "," Locator whitespaces?)* {
       return buildList($first, $rest, 3);
     }

FieldList
  = first:(Field) rest:("," whitespaces? Field)* {
      return buildList($first, $rest, 2);
    }

SegmentField
  = name:Identifier

Field
  = name:DottedIdentifier alias:(_ ":as" _ alias:string)? fields:(FieldBlock)? {
    $result = array( "name" => $name );
    if ($alias) {
      $result["alias"] = $alias[3];
    }
    if (!empty($fields)) {
      $result["fields"] = $fields;
    }
    return $result;
  }
  / Segment


DottedIdentifier
  = prefix:$"-"? start:varstart chars:dottedchar* {
    return $prefix . $start . join("", $chars);
  }

Identifier
  = prefix:$"-"? start:varstart chars:dashedalphanumeric* {
    return $prefix . $start . join("", $chars);
  }

ColumnIdentifier
  = prefix:$"-"? start:varstart chars:alphanumeric* {
    return $prefix . $start . join("", $chars);
  }

Operator
  = "="
  / "=~"

varstart
  = [_a-z]i

dottedchar
  = [_a-z0-9-.]i

dashedalphanumeric
  = [_a-z0-9-]i

alphanumeric
  = [_a-z0-9]i

string
  = string1
  / string2

string1
  = '"' chars:([^\n\r\f\\"] / "\\" nl:nl { return ""; } / escape)* '"' {
      return join("", $chars);
    }

string2
  = "'" chars:([^\n\r\f\\'] / "\\" nl:nl { return ""; } / escape)* "'" {
      return join("", $chars);
    }

unicode
  = "\\" digits:$(hexDigit hexDigit? hexDigit? hexDigit? hexDigit? hexDigit?) ("\r\n" / [ \t\r\n\f])? {
      return String.fromCharCode(parseInt(digits, 16));
    }

escape
  = unicode
  / "\\" ch:[^\r\n\f0-9a-f]i { return ch; }

chars
  = chars:char+ { return join("", $chars); }

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
