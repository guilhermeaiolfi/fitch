<?php

namespace fitch\sql;

class JoinOne extends Join {
  public $parent_table = null;
  public $join_table = null;
  public $conditions = "";
  public $type = "INNER";
  public $params = NULL;

  public function setTable($table) {
    $this->table = $table;
  }

  public function getType() {
    return $this->type;
  }

  public function setType($type) {
    $this->type = $type;
  }

  public function getParentTable() {
    return $this->parent_table;
  }

  public function setParentTable($table) {
    $this->parent_table = $table;
  }

  public function setJoinTable($table) {
    $this->join_table = $table;
  }

  public function getJoinTable() {
    return $this->join_table;
  }

  public function setJoinField($field) {
    $this->join_field = $field;
  }

  public function getJoinField() {
    return $this->join_field;
  }

  public function setParentField($field) {
    $this->parent_field = $field;
  }
  
  public function getParentField() {
    return $this->parent_field;
  }

  public function getSql() {
    $join_table = $this->getJoinTable();
    $parent_table = $this->getParentTable();

    return " " . $this->getType() . " JOIN " . $join_table->getSql() . " " . $join_table->getAlias() . " ON " . $this->getCondition();
  }

  private function getCondition() {
    $join_table = $this->getJoinTable();
    $parent_table = $this->getParentTable();

    return "(" . $join_table->getAlias() . "." . $this->getJoinField() . " = " . $parent_table->getAlias() . "." . $this->getParentField() . ")";
  }
}

?>