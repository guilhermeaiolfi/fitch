<?php

namespace fitch\fields;

use \fitch\Join as Join;

class Relation extends \fitch\fields\Field {
  protected $table = NULL;
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

  /*public function fixCondition($condition) {
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
  }*/

  public function __construct($meta, $data, $parent = NULL) {
    parent::__construct($meta, $data, $parent);
    if (!$parent) {
      $this->setTable($this->getName());
    } else {
      $table = $this->getMeta()->getTableNameFromRelation($parent->getName(), $this->getName());
      if (!$table) {
        throw new \Exception("Relation: \"" . $this->getName() . "\" doesn't exist in table \"" . $parent->getName() . "\"", 1);
      }
      $this->setTable($table);
    }

    if (count($this->children) == 0) {
      $fields = $meta->getFields($this->getTable());
      if (is_array($fields)) {
        foreach($fields as $field) {
          $field = new Field($meta, is_array($field)? $field : array("name" => $field), $this);
          $field->setParent($this);
          $this->addChild($field);
        }
      }
    }

    if ($this->hasVisibleFields() || !$parent) {
      $this->createHashField();
    }

    $this->conditions = $data["conditions"];

    for($i = 0; $i < count($data["functions"]); $i++) {

      $function = $data["functions"][$i];
      if ($function["name"] == "sort") {
        $this->functions["sort"] = array();
        for($y = 0; $y < count($function["params"]); $y++) {
          $this->functions["sort"][] = array (
            "field" => $function["params"][$y][0],
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

  public function getLeaves() {
    $leaves = array();
    foreach ($this->getChildren() as $child) {
      if (!($child instanceof Relation)) {
        $leaves[] = $child;
      }
    }
    return $leaves;
  }

  public function getRelations() {
    $relations = array();
    foreach ($this->getChildren() as $child) {
      if ($child instanceof Relation) {
        $relations[] = $child;
      }
    }
    return $relations;
  }

  protected function hasVisibleFields() {
    foreach ($this->getChildren() as $child) {
      if ($child->isVisible()) {
        return true;
      }
    }
    return false;
  }

  public function setTable($table) {
    $this->table = $table;
  }

  public function getTable() {
    return $this->table;
  }

  protected function createHashField() {
    $primary_key = $this->getMeta()->getPrimaryKeyName($this);
    $primary_key_field = new PrimaryKeyHash($this->getMeta(), array('name' => $primary_key));
    $primary_key_field->setPrimaryKey(array($primary_key));
    $primary_key_field->setName($primary_key);
    //$keys = $this->getMeta()->getPrimaryKey($this->getMeta()->getTableNameFromRelation($this->getRelationName()));
    $primary_key_field->setParent($this);
    $children = $this->getChildren();
    $replaced = false;
    for ($i = 0; $i < count($children); $i++) {
      if ($children[$i]->getName() == $primary_key) { //TODO: removed hardcode primary_key

        $primary_key_field->setField($children[$i]);
        $this->children[$i] = $primary_key_field;
        $replaced = true;
        break;
      }
    }
    if (!$replaced) {
      $primary_key_field->setVisible(false);
      array_unshift($this->children, $primary_key_field);
    }
    return $primary_key_field;
  }

  public function getPkIndex() {
    $root = $this->getParent("\\fitch\\fields\\Segment");

    if (!$root) { $root = $this; }

    $nodes = $root->getListOf("\\fitch\\fields\\Field");
    $i = 0;
    foreach ($nodes as $node) {
      if ($node instanceof \fitch\fields\Relation) { continue; }
      // TODO: it was $node->getParent() == $this, see another way to compare those instances
      if ($node->getParent()->getName() == $this->getName() && $node instanceof \fitch\fields\PrimaryKeyHash) { return $i; }
      $i++;
    }
    return -1;
  }

  public function hasPrimaryKey() {
    $children = $this->getChildren();
    foreach ($children as $child) {
      if ($child->getName() == "id") {
        return $child;
      }
    }
    return NULL;
  }

  public function getMapping($expanded = false) {
    $mapping = array();
    $pending = array($this);
    $pointer = &$mapping;

    $i = 0;
    while ($field = array_shift($pending)) {
      $alias = $field->getAliasOrName();
      if ($field instanceof Relation) {
        foreach($field->getChildren() as $child) {
          $pending[] = $child;
        }
        if ($field->isGenerated() && !$expanded) {
          continue;
        }
        $pointer[$alias] = array();
        $pointer = &$pointer[$alias];
        $pointer["_name"] = $alias;
        $pointer["_type"] = "relation";
        $pointer["_leaf"] = false;
        $pointer["_table"] = $field->getTable();
        $pointer["_generated"] = $field->isGenerated();
        $pointer["_many"] = $field->isMany();
        $pointer["_id"] = array("_name" => "_id", "_column_index" =>  $field->getPkIndex(), "_generated" => $generated, "_type" => "primary_key");
        $pointer["_children"] = array();
        $pointer = &$pointer['_children'];
      } else {
        $pointer[$alias] = array();
        $name = $field->getName();
        if ($field->getParent() instanceof Relation && $field->isGenerated())  {
          $pointer[$alias]["_id"] = array("_name" => "_id", "_column_index" => $field->getParent()->getPkIndex(), "_generated" => $generated, "_type" => "primary_key");
        }
        $pointer[$alias]["_name"] = $alias;
        $pointer[$alias]["_leaf"] = true;
        $pointer[$alias]["_visible"] = $field->isVisible();
        $pointer[$alias]["_type"] = "field";
        $pointer[$alias]["_level"] = $field->getLevel();
        $pointer[$alias]["_many"] = $field->isMany();
        $pointer[$alias]["_column_index"] = $i;
        $i++;
      }
    }
    return $mapping;
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
}

?>