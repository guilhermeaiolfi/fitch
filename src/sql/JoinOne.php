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

    return " " . $this->getType() . " JOIN " . $table->getSql() . " " . $table->getAlias() . " ON (" . $this->condition . ")";
  }

  public function setCondition($condition) {
    $this->condition = $condition;
  }
}

?>