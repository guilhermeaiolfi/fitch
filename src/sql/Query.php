<?php

namespace fitch\sql;

use \fitch\Fields\Field as Field;
use \fitch\Fields\Segment as Segment;

class Query {
  protected $joins = array();
  protected $fields = array();
  protected $sort_by = array();
  protected $aliases = array();

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
    $meta = $meta->getRelationConnections($join->getName());
    if (count($meta) == 2) {
      return $this->getManyToManyJoin($join, $meta);
    }
    return $this->getOneToManyJoin($join, $meta);
  }
  public function getManyToManyJoin($join, $meta) {

    $joins = array();
    $first = true;

    foreach ($meta as $left => $right) {
      list($left_table, $left_field) = explode(".", $left);
      list($right_table, $right_field) = explode(".", $right);

      $aliases = $this->getAliasFor($join);
      if ($first) {
        $joins[] = " " . $join->getType() . " JOIN $right_table " . $aliases[0] . " ON (" . $aliases[0] . "." . $right_field . " = " . $this->getAliasFor($this->getRoot()) . "." . $left_field . ")";
        $first = false;
      } else {
        $alias = $this->getAliasFor($join);
        $joins[] = " " . $join->getType() . " JOIN $right_table " . $aliases[1] . " ON (" . $aliases[1] . "." . $right_field . " = " . $aliases[0] . "." . $left_field . ")";
      }

    }
    return implode(" ", $joins);
  }

  public function getOneToManyJoin($join, $meta) {

    list($left, $right) = each($meta);
    list($left_table, $left_field) = explode(".", $right);
    list($right_table, $right_field) = explode(".", $left);

    $table = $left_table;
    $aliases = $this->getAliasFor($join);

    return " " . $join->getType() . " JOIN $left_table " . $aliases[1] . " ON (" . $this->getAliasFor($this->getRoot()) . "." . $right_field . " = " . $aliases[1] . "." . $left_field . ")";
  }

  public function getSql($meta) {
    $sql = "SELECT ";

    $alias = $this->getAliasFor($this->getRoot());

    $fields = $this->getFields();
    $select_fields = array();
    for ($i = 0; $i < count($fields); $i++) {
      $field = $fields[$i];
      if (!$field->hasDot()) {
        $alias = $this->getAliasFor($field);
        $select_fields[] = $alias . "." . $field->getName();
      } else {
        $parts = $field->getParts();
        $n = count($parts);
        $select_fields[] = $this->getAliasFor($field) . "." . $parts[$n-1];
      }
    }

//print_r($select_fields);
    $sql .= (empty($fields)? "*" : implode(", ", $select_fields)) . " FROM " . $this->getTable() . " AS " . $this->getAliasFor($this->getRoot());

    foreach ($this->getJoins() as $join) {
      $sql .= $this->getJoinSql($join, $meta);
    }

    $sort_by = $this->sort_by;
    $sort_by_count = count($sort_by);
    if ($sort_by_count > 0) {
      $sql .= " SORT BY ";
    }
    for ($i = 0; $i < count($sort_by); $i++) {
      $sql .= $i? ", " : "";
      $sql .= $sort_by[$i]["column"] . " " . ($sort_by[$i]["direction"] == "+"? "ASC" : "DESC");
    }
    return $sql;
  }

  function addSortBy($sort) {
    $this->sort_by[] = $sort;
  }

  function getAliasFor($node) {
    $table = "";

    if ($node instanceof Segment) {
      if ($node->hasDot()) {
        $parts = $node->getParts();
        $table = $parts[0];
      } else {
        $table = $node->getName();
      }
    } else if ($node instanceof Field) {
      $parent = $node->getParent();
      if ($node->hasDot()) {
        $parent = $node;
      }
      if ($parent->hasDot()) {
        $parts = $parent->getParts();
        if ($parent instanceof Segment) {
          $table = $parts[count($parts) - 1];
        } else {
          $table = $parts[count($parts) - 2];
        }
      } else {
        $table = $parent->getName();
      }
      $node = $node->getParent();
    } else if ($node instanceof \fitch\Join) {

      $relation = $node->getRelation();
      $table = ($relation->getParent()? $relation->getParent()->getName() : $relation->getName()) . "_" . $node->getTable();
      $aliases = array();
      $aliases[] = $this->registerAliasFor($table, $relation);

      $table = $node->getTable();

      $aliases[] = $this->registerAliasFor($table, $relation);
      return $aliases;
    } else {
      $table = $node->getTable();
    }
    return $this->registerAliasFor($table, $node);
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