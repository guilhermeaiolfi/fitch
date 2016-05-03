<?php
namespace fitch\fields;

use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;

class Segment extends Relation {
  protected $functions = array();
  protected $conditions = NULL;

  public function getFunctions() {
    return $this->functions;
  }

  public function getFunction($name) {
    return $this->functions[$name];
  }

  public function getConditions() {
    return $this->conditions;
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

    $this->conditions = $data["conditions"];
    for($i = 0; $i < count($data["functions"]); $i++) {

      $function = $data["functions"][$i];
      if ($function["name"] == "sort") {
        $this->functions["sort"] = array();
        for($y = 0; $y < count($function["params"]); $y++) {
           $this->functions["sort"][] = array (
            "column" => $function["params"][$y][0],
            "direction" => $function["params"][$y][1]
          );
        }
      }
      if ($function["name"] == "limit") {
        $this->functions["limit"] = array (
            "limit" => $function["params"][0],
            "offset" => $function["params"][1]
        );
      }
    }
  }
}

?>