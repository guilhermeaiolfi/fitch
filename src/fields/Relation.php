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
        $name = $this->getParent()? $this->getParent()->getName() . "." . $parts[0] : $parts[0];
        $obj->setName($name);
        $joins[] = $obj;
      } else {
        $obj->setName($parts[$i - 1] . "." . $parts[$i]);
      }
    }
    return $joins;
  }
  public function hasPrimaryKey() {
    $children = $this->getChildren();
    foreach ($children as $child) {
      if ($child->getName() == "id") {
        return $child;
      }
    }
    return NULL;
  }
}

?>