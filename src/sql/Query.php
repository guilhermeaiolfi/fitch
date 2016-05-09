<?php

namespace fitch\sql;

use \fitch\Fields\Field as Field;
use \fitch\Fields\Relation as Relation;
use \fitch\Fields\Segment as Segment;

class Query {
  protected $joins = array();
  protected $fields = array();
  protected $sort_by = array();
  protected $aliases = array();
  protected $conditions = NULL;
  protected $limit = NULL;

  public function setConditions($conditions) {
    $this->conditions = $conditions;
  }

  public function getRoot() {
    return $this->root;
  }

  public function setRoot($root) {
    return $this->root = $root;
  }

  public function addJoin($join) {
    $this->joins[] = $join;
  }

  public function addField($field) {
    $this->fields[] = $field;
  }

  public function setFields($fields) {
    $this->fields = $fields;
  }

  public function getFields() {
    return $this->fields;
  }

  public function getJoins() {
    return $this->joins;
  }

  public function getTable() {
    return $this->getRoot()->getName();
  }

  public function getJoinSql($join, $meta) {
    //print_r($join->getRelation()->getName());exit;
    $relation = $join->getRelation();
    $parent = $relation->getParent();

    $meta = $meta->getRelationConnections($parent->getName(), $relation->getName());

    if (count($meta) == 2) {
      return $this->getManyToManyJoin($join, $meta);
    }
    return $this->getOneToManyJoin($join, $meta);
  }

  public function getConditionSql($condition, $root = true) {
    if (is_array($condition)) {
      if (isset($condition["field"]) && isset($condition["value"])) { //condition
        $field = $condition["field"];
        $operator = $condition["operator"];
        $value = $condition["value"];
        if ($operator == "~") {
          return $field->getTable()->getAlias() . "." . $field->getName() . " LIKE " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
        }
        if ($operator == "!=") {
          return $field->getTable()->getAlias() . "." . $field->getName() . " <> " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
        }
        if ($operator == "~") {
          return $field->getTable()->getAlias() . "." . $field->getName() . " LIKE " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
        }
        return $field->getTable()->getAlias() . "." . $field->getName() . " " . $operator . " " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
      } else { // parenthesis
        $where = !$root? "(" : "";
        foreach ($condition as $item) {
          if (is_string($item)) {
            if ($item == '&') {
              $where .= " AND ";
            } else {
              $where .= " OR ";
            }
          } else {
            $where .= $this->getConditionSql($item, false);
          }
        }
        return $where .= !$root? ")" : "";
      }
    } else { // SQL's 'AND' or 'OR'
      if ($condition == '&') {
        return " AND ";
      } else {
        return " OR ";
      }
    }
    return NULL;
  }

  public function getSql($meta) {
    $sql = "SELECT ";

    $root_alias = $this->getRoot()->getAlias();

    $fields = $this->getFields();
    $select_fields = array();
    for ($i = 0; $i < count($fields); $i++) {
      $field = $fields[$i];
      $select_fields[] = $field->getSql();
    }

    $sql .= implode(", ", $select_fields);
    $sql .= " FROM " . $this->getTable() . " AS " . $root_alias;

    foreach ($this->getJoins() as $join) {
      $sql .= $join->getSql();
    }

    $where = "";

    if (is_array($this->conditions)) {
      $where .= " WHERE ";
      $where .= $this->getConditionSql($this->conditions);
    }

    $sql .= $where;

    $sort_by = $this->sort_by;
    $sort_by_count = count($sort_by);
    if ($sort_by_count > 0) {
      $sql .= " SORT BY ";
    }
    for ($i = 0; $i < count($sort_by); $i++) {
      $field = $sort_by[$i]["field"];
      $alias = $field->getTable()->getAlias();
      $sql .= $i? ", " : "";
      $sql .= $alias . "."  . $field->getName() . " " . ($sort_by[$i]["direction"] == "+"? "ASC" : "DESC");
    }

    if (is_array($this->limit)) {
      $sql .= " LIMIT " . join(",", $this->limit);
    }
    return $sql;
  }

  function addSortBy($sort) {
    $this->sort_by[] = $sort;
  }

  function limit($limit, $offset) {
    $this->limit = array($limit, $offset);
  }
}

?>