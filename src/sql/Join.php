<?php

namespace fitch\sql;

class Join {
  public $table = null;
  public $name = null;
  public $conditions = "";
  public $type = "INNER";

  public function from($table, $alias = NULL) {
    if (is_string($table)) {
      $table = new Table();
      $table->setName($table);
      $table->setAlias($alias);
      $this->table = $table;
    } else {
      $this->table = $table;
    }
    return $this;
  }

  public function type($type) {
    $this->type = $type;
    return $this;
  }

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
    return "";
  }

}

?>