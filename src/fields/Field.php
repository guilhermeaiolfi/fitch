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

  public function getRelationName() {
    if (is_a($this, "\\fitch\\fields\\Relation")) {
      $parts = explode(".", $this->getName());
      $n = count($parts);
      if ($n == 1) {
        if ($this->getParent()) {
          return $this->getParent()->getName() . "." . $parts[0];
        } else {
          return $parts[0];
        }
      }
      return $parts[$n-2] . "." . $parts[$n - 1];
    } else {
      if (!$this->hasDot()) { return $this->getParent()->getName(); }
      $parts = $this->getParts();
      $n = count($parts);
      $parent = $this->getParent();
      if ($n == 2) {
        return $parent->getName() . "." . $parts[0];
      } else {
        return $parts[$n - 3] . "." . $parts[$n - 2];
      }
    }
    return null;
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