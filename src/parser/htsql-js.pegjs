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

SegmentExtra
  = "." "sort" "(" SortList? ")"
  / "." Identifier "(" ArgumentList? ")"
  / "." Identifier

Segment
  = segment:"/" segment:Identifier segment_right:SegmentExtra* ids:("[" LocatorList "]")? __ fields:FieldBlock? conditions:ConditionBlock {
  var functions = [], item;
  while(item = segment_right.pop()) {
    if (item[2] == "(") {
      functions.push({ type: "Function", name: item[1], params: item[3]});
    } else {
      segment += "." + item[1];
    }
  }
  return {
    name: segment,
    type: 'Segment',
    functions: functions,
    ids: ids? ids[1] : null,
    fields: fields,
    conditions: conditions
  };
}

ConditionBlock
  = "?" expr:ConditionExpression { return expr; }
  / ("?" expr:ConditionExpression)?

ConditionJoin
 = "&"
 / "|"

ConditionExpression
  = head:ConditionTerm tail:(ConditionJoin ConditionTerm)* {
     var result = [head], i;

    for (i = 0; i < tail.length; i++) {
      result.push(tail[i][0]);
      result.push(tail[i][1]);
    }
    return result;
  }

ConditionTerm
   = "(" __ expr:ConditionExpression __ ")" {
    return expr;
  }
  / Condition

Condition
  = left:DottedIdentifier operator:Operator right:Value { return { field: left, operator: operator, value: right } }

FieldBlock
  = "{" fields:FieldList "}" { return fields; }

FunctionList
  = functions:("." Function)* { return extractList(functions, 1); }

Function
  = name:"sort" "(" params:SortList? ")" { return { type: 'Function', name: name, arguments: params }; }
  / name:Identifier "(" params:ArgumentList? ")" { return { type: 'Function', name: name, arguments: params }; }

SortDirection
  = "-"
  / "+"

Locator
  = Value
  / Identifier

LocatorList
  = first:Locator rest:(__ "," __ Locator)* {
       return buildList(first, rest, 1);
     }

SortList
  = first:(ColumnIdentifier SortDirection?) __ rest:("," __ (ColumnIdentifier SortDirection?))* {
       var result = [], i;
       result.push( [ first[0], first[1] ]);

      for (i = 0; i < rest.length; i++) {
        result.push( rest[i][2] );
      }
      return result;
     }

ArgumentList
  = first:Locator rest:(__ "," __ Locator __)* {
       return buildList(first, rest, 3);
     }

FieldList
  = first:(Field) rest:("," __ Field)* {
      return buildList(first, rest, 2);
    }

SegmentField
  = name:Identifier

RelationType
  = "!"
  / "<"
  / ">"

Field
  = type:RelationType? name:DottedIdentifier alias:(__ ":as" __ field:Identifier)? fields:(FieldBlock)? {
    var result = { name: name }
    if (type) {
      result["type"] = type;
    }
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
  = prefix:$"-"? start:varstart chars:dashedalphanumeric* {
    return prefix + start + chars.join("");
  }

ColumnIdentifier
  = prefix:$"-"? start:varstart chars:alphanumeric* {
    return prefix + start + chars.join("");
  }

Operator
  = "="
  / "~"
  / "!="
  / ">="
  / "<="
  / "!~"

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

__
  = whitespace*

whitespaces
  = whitespace*
nl
  = "\n"
  / "\r\n"
  / "\r"
  / "\f"