<?php

namespace fitch\fields;

use \fitch\Node as Node;

class Field extends Node {
  private $fullname = null;
  public function __construct ($data = null) {
    parent::__construct($data);
    @$this->setFullname($data["fullname"]);
  }

  public function getFullname() {
    return $this->fullname;
  }
  public function setFullname($fullname) {
    $this->fullname = $fullname;
  }

  public function hasDot() {
    return strpos($this->getName(), ".") !== false;
  }
  public function getParts() {
    return explode(".", $this->getName());
  }

  public function getListOf($type) {
    $pending = array();
    $current = $this;
    $joins = array();
    while($current && $children = $current->getChildren()) {
      foreach ($children as $item) {
        if ($item instanceof $type) {
          $joins[] = $item;
        }
        if ($item->getChildren()) {
          $pending[] = $item;
        }
      }
      $current = array_shift($pending);
    }
    return $joins;
  }
}

?>