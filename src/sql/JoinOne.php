<?php

namespace fitch\sql;

class JoinOne extends Join {
  public $conditions = "";
  public $params = NULL;

  public function getType() {
    return $this->type;
  }

  public function setType($type) {
    $this->type = $type;
  }

  public function getSql() {
    $table = $this->getTable();

    $sql = "";
    if ($table instanceof \fitch\sql\Query) {
      $sql = "(" . $table->getSql() . ")";
    } else {
      $sql = $table->getSql();
    }
    return " " . $this->getType() . " JOIN " . $sql . " " . $table->getAlias() . " ON (" . $this->condition . ")";
  }

  public function setCondition($condition) {
    $this->condition = $condition;
  }
}

?>