<?php

namespace fitch\sql;

class Table {
  public $alias = null;
  public $name = null;
  public $type = "INNER";

  public function setType($type) {
    $this->type = $type;
  }

  public function getType() {
    return $this->type;
  }

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