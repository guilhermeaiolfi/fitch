<?php

namespace fitch;

class Join {
  public $table = null;
  public $alias = null;
  public $name = null;
  public $relation = null;
  public $conditions = "";
  public function setTable($table) {
    $this->table = $table;
  }

  public function getTable() {
    return $this->table;
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
  public function setRelation($relation) {
    $this->relation = $relation;
  }
  public function getRelation() {
    return $this->relation;
  }

  public function getConditions() {
    return $this->conditions;
  }
  public function setConditions() {
    return $this->conditions;
  }

}

?>