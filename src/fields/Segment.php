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

  public function fixCondition($condition) {
    if (is_array($condition)) {
      if (isset($condition["field"])) { //condition
        $field = $this->getFieldByFullname($condition["field"]);
        if (!$field) {
          throw new \Exception("No field(" . $condition["field"] . ") found" , 1);
        }
        $condition["field"] = $field;
        return $condition;
      } else { // parenthesis
        $parenthesis = array();
         foreach ($condition as $item) {
          $parenthesis[] = $this->fixCondition($item);
        }
        return $parenthesis;
      }
    } else { // SQL's 'AND' or 'OR'
      return $condition;
    }
    return NULL;
  }

  public function __construct ($meta, $data, $parent = NULL) {
    parent::__construct($meta, $data, $parent);

    $this->conditions = $data["conditions"];

    $this->conditions = $this->fixCondition($data["conditions"]);

    for($i = 0; $i < count($data["functions"]); $i++) {

      $function = $data["functions"][$i];
      if ($function["name"] == "sort") {
        $this->functions["sort"] = array();
        for($y = 0; $y < count($function["params"]); $y++) {
          $field = $this->getFieldByFullname($function["params"][$y][0]);
          if (!$field) {
            throw new \Exception("No field(" . $function["params"][$y][0] . ") found" , 1);
          }
          $this->functions["sort"][] = array (
            "field" => $field,
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