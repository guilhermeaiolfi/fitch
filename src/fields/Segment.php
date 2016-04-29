<?php
namespace fitch\fields;

use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;

class Segment extends Relation {
  protected $functions = array();
  public function getFunctions() {
    return $this->functions;
  }
  public function getFunction($name) {
    return $this->functions[$name];
  }
  public function __construct ($data = null) {
    parent::__construct($data);
    for($i = 0; $i < count($data["functions"]); $i++) {

      $function = $data["functions"][$i];
      if ($function["name"] == "sort") {
        $this->functions["sort"] = array();
        for($y = 0; $y < count($function["arguments"]); $y++) {
           $this->functions["sort"][] = array (
            "column" => $function["arguments"][$y][0],
            "direction" => $function["arguments"][$y][1]
          );
        }
      }
    }
  }
}

?>