<?php

namespace fitch\sql;

class Table {
  public $alias = null;
  public $name = null;

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

  public function getSql() {
  	return $this->getName()? $this->name : "";
  }
}