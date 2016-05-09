<?php

namespace fitch\sql;

class Column {
  public $alias = null;
  public $name = null;
  public $table = null;

  public function setAlias($alias) {
    $this->alias = $alias;
  }

  public function getAlias() {
    return $this->alias;
  }

  public function setTable($table) {
    $this->table = $table;
  }

  public function getTable() {
    return $this->table;
  }

  public function setName($name) {
    $this->name = $name;
  }

  public function getName() {
    return $this->name;
  }

  public function getSql() {
    return $this->getTable()->getAlias() . "." . $this->getName();
    return $this->getTable()->getAlias() . "." . $this->getName() . ($this->getAlias()? " AS " . $this->getAlias() : "");
  }

}

?>