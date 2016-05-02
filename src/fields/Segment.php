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
  public function getJoins() {
    // TODO: support multi level JOINs: school.department.another_table
    $joins = array();
    if ($this->hasDot()) {
      $parts = $this->getParts();
      $join = new \fitch\Join();
      $join->setName($this->getName());
      $join->setRelation($this);
      $join->setType("INNER");
      $join->setTable($parts[count($parts) - 1]);
      $joins[] = $join;
    }
    return $joins;
  }
  public function __construct ($meta, $data = null) {
    parent::__construct($meta, $data);

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