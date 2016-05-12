<?php

namespace fitch\sql;

class Column {
  public $alias = null;
  public $name = null;
  public $table = null;
  public $primary_key = false;

  public function setPrimaryKey($primary_key) {
    $this->primary_key = $primary_key;
  }

  public function isPrimaryKey() {
    return $this->primary_key;
  }

  public function setAlias($alias) {
    $this->alias = $alias;
  }

  public function getAlias() {
    return $this->alias;
  }

  public function from($root, $alias = NULL) {
    if (is_array($root)) {
      $table = new Table();
      $keys = array_keys($root);
      $table->setName($keys[0]);
      $table->setAlias($root[$keys[0]]);
      $this->table = $table;
    } else if (is_string($root)) {
      $table = new Table();
      $table->setName($root);
      $table->setAlias($alias);
      $this->table = $table;
    } else {
      $this->table = $root;
    }
    return $this;
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
    return $this->getTable()->getAlias() . "." . $this->getName() . ($this->getAlias()? " AS " . $this->getAlias() : "");
  }
}

?>