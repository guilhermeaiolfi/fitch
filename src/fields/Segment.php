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

  public function getRelationByName($relation_name) {
    $relations = $this->getListOf("\\fitch\\fields\\Relation");
    foreach ($relations as $relation) {
      if ($relation->getName() == $relation_name || $relation->getAlias() == $relation_name) {
        return $relation;
      }
    }
    return NULL;
  }

  public function getFieldByName($field_name, $relation_name) {
    $field = $this->getListOf("\\fitch\\fields\\Field");
    foreach ($fields as $field) {
      $relation = $field->getParent();
      if ($field->getName() == $field_name
          && ($relation->getName() == $relation_name || $relation->getAlias() == $relation_name)
         ) {
        return $field;
      }
    }
    return NULL;
  }

  public function getFieldByFullname($fullname) {
    $relation = NULL;
    $parts = explode(".", $fullname);
    $field_name = $parts[count($parts) - 1];
    if (count($parts) == 1) {
      $relation = $this;
    } else {
      $relation = $this->getRelationByName($parts[count($parts) - 2]);
    }

    if ($relation) {
      foreach ($relation->getChildren() as $child) {
        if ($child->getName() == $field_name) {
          return $child;
        }
      }
    }
    return NULL;
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

  public function __construct ($meta, $data = null) {
    parent::__construct($meta, $data);

    $this->conditions = $data["conditions"];
    //print_r($data["conditions"]);

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