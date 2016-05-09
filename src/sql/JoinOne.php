<?php

namespace fitch\sql;

class JoinOne extends Join {
  public $table = null;
  public $conditions = "";
  public $type = "INNER";
  public $params = NULL;

  public function getType() {
    return $this->type;
  }

  public function setType($type) {
    $this->type = $type;
  }

  public function setTable($table) {
    $this->table = $table;
  }

  public function getTable() {
    return $this->table;
  }

  public function getSql() {
    $table = $this->getTable();

    return " " . $this->getType() . " JOIN " . $table->getSql() . " " . $table->getAlias() . " ON " . $this->condition;
  }

  public function setCondition($left_field, $op, $right_field) {
    $this->condition = "(" . $left_field->getTable()->getAlias() . "." . $left_field->getName() . " $op " . $right_field->getTable()->getAlias() . "." . $right_field->getName() . ")";
  }
}

?>