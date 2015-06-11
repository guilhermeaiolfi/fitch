<?php

namespace fitch\fields;

use \fitch\Node as Node;

class Field extends Node {
  protected $fullname = null;
  protected $generate = false;
  public function __construct ($data = null) {
    parent::__construct($data);
    @$this->setFullname($data["fullname"]);
  }

  public function setGenerated($b) {
    $this->generated = $b;
  }
  public function getGenerated($b) {
    return $this->generated;
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
    $list = array();
    while($current && $children = $current->getChildren()) {
      foreach ($children as $item) {
        if (is_a($item, $type)) {
          $list[] = $item;
        }
        if ($item->getChildren()) {
          $pending[] = $item;
        }
      }
      $current = array_shift($pending);
    }

    return $list;
  }
}

?>