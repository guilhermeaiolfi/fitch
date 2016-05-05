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
    $parts = $this->getRoot()->getParts();
    if ($this->getRoot()->hasDot()) {
      return $parts[0];
    }
    return $this->getRoot()->getName();
  }

  public function getJoinSql($join, $meta) {
    $relation = $join->getRelation();
    $parent = $relation->getParent();
    $meta = $meta->getRelationConnections($parent->getName() . "." . $relation->getName());
    if (count($meta) == 2) {
      return $this->getManyToManyJoin($join, $meta);
    }
    return $this->getOneToManyJoin($join, $meta);
  }

  public function getManyToManyJoin($join, $meta) {

    $joins = array();
    $first = true;

    $relation = $join->getRelation();
    $parent = $relation->getParent();

    $relation_alias = $this->getTableAliasFor($relation);
    $parent_alias = $this->getTableAliasFor($parent);
    $join_alias = $this->getTableAliasFor($join);

    foreach ($meta as $left => $right) {

      if ($first) {
        list($parent_table, $parent_id) = explode(".", $left);
        list($join_table, $join_parent_id) = explode(".", $right);

        $joins[] = " " . $join->getType() . " JOIN $join_table " . $join_alias . " ON (" . $join_alias . "." . $join_parent_id . " = " . $parent_alias . "." . $parent_id . ")";
        $first = false;

      } else {
        list($join_table, $join_parent_id) = explode(".", $left);
        list($relation_table, $relation_id) = explode(".", $right);

        $joins[] = " " . $join->getType() . " JOIN $relation_table " . $relation_alias . " ON (" . $relation_alias . "." . $relation_id . " = " . $join_alias . "." . $join_parent_id . ")";
      }

    }
    return implode(" ", $joins);
  }

  public function getOneToManyJoin($join, $meta) {
    list($left, $right) = each($meta);
    list($left_table, $left_field) = explode(".", $right);
    list($right_table, $right_field) = explode(".", $left);


    $left_relation = $join->getRelation();
    $right_relation = $join->getRelation();

    $parent_table = $this->getTableAliasFor($join->getRelation()->getParent());
    $join_table = $this->getTableAliasFor($join);

    return " " . $join->getType() . " JOIN $left_table " . $join_table . " ON (" . $parent_table . "." . $right_field . " = " . $join_table . "." . $left_field . ")";
  }

  public function getConditionSql($condition, $root = true) {
    if (is_array($condition)) {
      if (isset($condition["field"]) && isset($condition["value"])) { //condition
        $field = $condition["field"];
        $operator = $condition["operator"];
        $value = $condition["value"];
        $alias = $this->getTableAliasFor($field->getParent());
        if ($operator == "~") {
          return $alias . "." . $field->getName() . " LIKE " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
        }
        if ($operator == "!=") {
          return $alias . "." . $field->getName() . " <> " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
        }
        if ($operator == "~") {
          return $alias . "." . $field->getName() . " LIKE " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
        }
        return $alias . "." . $field->getName() . " " . $operator . " " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
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

    $root_alias = $this->getTableAliasFor($this->getRoot());

    $fields = $this->getFields();
    $select_fields = array();
    for ($i = 0; $i < count($fields); $i++) {
      $field = $fields[$i];
      $alias = $this->getTableAliasFor($field);
      $select_fields[] = $alias . "." . $field->getName();
    }

    $sql .= implode(", ", $select_fields);
    $sql .= " FROM " . $this->getTable() . " AS " . $root_alias;

    foreach ($this->getJoins() as $join) {
      $join_sql = $this->getJoinSql($join, $meta);
      $sql .= $join_sql;
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
      $alias = $this->getTableAliasFor($field);
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

  function getTableAliasFor($node) {
    $table = "";
    if ($node instanceof Segment) { // TODO: improve this non-sense
      if ($node->hasDot()) {
        $parts = $node->getParts();
        $table = $parts[0];
      } else {
        $table = $node->getName();
      }
    } else if ($node instanceof Relation) {
      $table = $node->getName();
    } else if ($node instanceof Field) {
      $table = $node->getParent()->getName();
      $node = $node->getParent();
    } else if ($node instanceof \fitch\Join) {
      $relation = $node->getRelation();
      $table = $relation->getParent()->getName() . "_" . $relation->getName();
      return $this->registerAliasFor($table, $node);
    }
    $alias = $this->registerAliasFor($table, $node);
    return $alias;
  }

  public function registerAliasFor($table, $node) {
    $n = 0;
    while (isset($this->aliases[$table . "_" . $n])) {
      if ($this->aliases[$table . "_" . $n] == $node) {
        return $table . "_" . $n;
      }
      $n++;
    }
    $this->aliases[$table . "_" . $n] = $node;
    return $table . "_" . $n;
  }
}

?>