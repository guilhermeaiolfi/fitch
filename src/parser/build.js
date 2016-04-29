var pegjs = require("pegjs");
var phppegjs = require("php-pegjs");
var fs = require('fs')

var peg = "";

fs.readFile('htsql.pegjs', 'utf8', function (err,data) {
  if (err) {
    return console.log(err);
  }

  var parser = pegjs.buildParser(data, {
      plugins: [phppegjs],
      "phppegjs": {
        "parserNamespace": "fitch\\parser"
      }
  });

  fs.writeFile('Parser.php', parser);
});
