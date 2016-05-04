<?php

namespace fitch\fields;

use \fitch\Node as Node;
use \fitch\fields\SoftRelation as SoftRelation;

class Field extends Node {
  protected $generate = false;

  public function isMany() {
    return !$this->getParent() || $this->getMeta()->isManyToManyRelation($this->getRelationName());
  }

  public function setGenerated($b) {
    $this->generated = $b;
  }
  public function isGenerated() {
    return $this->generated;
  }
  public function getFullname() {
    $name = "";
    $parent = $this;
    while (($parent = $parent->getParent()) && $parent instanceof \fitch\fields\Relation && $parent->isGenerated()) {
      get_class($parent);
      $name = $parent->getName() . "." . $name;
    }
    return $name . $this->name;
  }

  public function hasDot() {
    return strpos($this->getName(), ".") !== false;
  }
  public function getParts() {
    return explode(".", $this->getName());
  }

  public function getAliasOrName() {
    return isset($this->alias)? $this->alias : $this->getFullname();
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
      if ($this->getParent() instanceof \fitch\fields\Relation && $this->getParent()->isGenerated()) {
        return $this->getParent()->getParent()->getName() . "." . $this->getParent()->getName();
      }
      if (!$this->hasDot()) { return $this->getParent()->getName(); }
      $parent = $this->getParent();

      while ($parent instanceof \fitch\fields\Relation && $parent->isGenerated()) { $parent = $parent->getParent(); }
      $parts = $this->getParts();
      $n = count($parts);
      if ($n == 2) {
        return $parent->getName() . "." . $parts[0];
      } else {
        return $parts[$n - 3] . "." . $parts[$n - 2];
      }
    }
    return null;
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