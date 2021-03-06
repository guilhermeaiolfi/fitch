<?php

namespace fitch\fields;

use \fitch\Node as Node;
use \fitch\fields\SoftRelation as SoftRelation;

class Selectable extends Node {

  public function getFullname() {
    $name = "";
    $parent = $this;
    while (($parent = $parent->getParent()) && $parent instanceof \fitch\fields\Relation && $parent->isGenerated()) {
      $name = $parent->getName() . "." . $name;
    }
    return $name . $this->name;
  }

  public function getAliasOrName() {
    return isset($this->alias)? $this->alias : $this->getFullname();
  }

  public function getLevel() {
    $level = -1;
    $parent = $this->getParent();
    while ($parent) {
      if ($parent instanceof \fitch\fields\Relation && $parent->isGenerated()) {
      } else if ($parent instanceof \fitch\fields\Relation) {
        $level++;
      }
      $parent = $parent->getParent();
    }
    return $level;
  }
}

?>