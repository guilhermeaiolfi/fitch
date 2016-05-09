<?php

namespace fitch\sql;

class JoinMany extends Join {
  protected $joins = array();

  public function addJoin($join) {
    $this->joins[] = $join;
  }
  
  public function getJoins() {
    return $this->joins;
  }

  public function getSql() {
    foreach ($this->getJoins() as $join) {
      $sql .= $join->getSql();
    }
    return $sql;
  }

}

?>