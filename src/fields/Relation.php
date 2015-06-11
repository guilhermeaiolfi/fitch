<?php

namespace fitch\fields;

use \fitch\Join as Join;

class Relation extends \fitch\fields\Field {
  public function getJoins() {
    $parts = explode(".", $this->getName());
    $joins = [];
    for ($i = 0; $i < count($parts); $i++) {
      $obj = new Join();
      $obj->setRelation($this);
      $obj->setTable($parts[$i]);
      if ($i == 0) {
        $obj->setName($this->getParent()->getName() . "." . $parts[0]);
        $joins[] = $obj;
      } else {
        $obj->setName($parts[$i - 1] . "." . $parts[$i]);
      }
    }
    return $joins;
  }
}

?>