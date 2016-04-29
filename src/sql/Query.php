<?php

namespace fitch\sql;

class Query {
  protected $joins = array();
  protected $fields = array();
  protected $sort_by = array();

  public function __construct() {

  }
  public function getRoot() {
    return $this->root;
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

  public function setTable($table) {
    $this->table = $table;
  }
  public function getTable() {
    return $this->table;
  }
  public function getAlias() {
    return $this->alias? $this->alias : $this->getTable();
  }
  public function setAlias($alias) {
    $this->alias = $alias;
  }
  public function getFields() {
    return $this->fields;
  }
  public function getJoins() {
    return $this->joins;
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

      if ($first) {
        $first_alias = $this->getTable() . "_" . $join->getTable();
        $joins[] = " LEFT JOIN $right_table " . $first_alias . " ON (" . $first_alias . "." . $right_field . " = " . $this->getAlias() . "." . $left_field . ")";
        $first = false;
      } else {
        $alias = $join->getTable();
        $joins[] = " LEFT JOIN $right_table " . $alias . " ON (" . $alias . "." . $right_field . " = " . $first_alias . "." . $left_field . ")";
      }

    }

    return implode(" ", $joins);

  }

  public function getOneToManyJoin($join, $meta) {

    list($left, $right) = each($meta);
    list($left_table, $left_field) = explode(".", $right);
    list($right_table, $right_field) = explode(".", $left);

    $table = $left_table;
    $alias = explode(".", $join->getName())[1];


    return " LEFT JOIN $left_table $alias ON (" . $this->getAlias() . "." . $right_field . " = " . $join->getAlias() . "." . $left_field . ")";
  }

  public function getSql($meta) {
    $sql = "SELECT ";

    $fields = array_map(function($value) {
      if (!$value->hasDot()) {
        return $value->getParent()->getAliasOrName() . "." . $value->getName() . ($value->getAlias()? (" AS " . $value->getAlias()) : "");
      } else {
        $parts = $value->getParts();
        $n = count($parts);
        return $parts[$n-2] . "." . $parts[$n-1] . ($value->getAlias()? (" AS " . $value->getAlias()) : "");
      }
    }, $this->getFields());

    $sql .= (empty($fields)? "*" : implode(", ", $fields)) . " FROM " . $this->getTable();
    if ($this->getAlias()) {
      $sql .= " AS " . $this->getAlias();
    }

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
}

?>