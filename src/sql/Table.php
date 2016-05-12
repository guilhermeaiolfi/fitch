<?php

namespace fitch\sql;

class Table {
  protected $alias = null;
  protected $name = null;
  protected $columns = array();

  public function setAlias($alias) {
    $this->alias = $alias;
  }

  public function getAlias() {
    return $this->alias;
  }

  public function setName($name) {
    $this->name = $name;
  }

  public function getName() {
    return $this->name;
  }

  public function setColumns($columns) {
    $this->columns = $columns;
  }

  public function getColumns() {
    return $this->columns;
  }

  public function removeColumn($column) {
    if(($key = array_search($column, $this->columns, true)) !== FALSE) {
        unset($this->columns[$key]);
    }
    return $this;
  }

  public function getSql() {
  	return $this->getName()? $this->name : "";
  }
}