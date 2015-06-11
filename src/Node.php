<?php

namespace fitch;

use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class Node {

  protected $children = array();

  protected $name = null;

  protected $parent = null;

  protected $alias = null;

  public function getChildren() {
    return $this->children;
  }
  public function setChildren($children) {
    $this->children = $children;
  }
  public function getChildByName($name, $parent) {
    foreach ($this->children as $child) {
      if ($name == $child->getName() && $parent == $child->getParent()) {
        return $child;
      }
    }
    return null;
  }

  public function addChild($child) {
    if (!$exists = $this->getChildByName($child->getName(), $child->getParent())) {
      $this->children[] = $child;
    }
  }
  public function getAlias() {
    return $this->alias;
  }
  public function getAliasOrName() {
    return isset($this->alias)? $this->alias : $this->name;
  }
  public function setAlias($alias) {
    $this->alias = $alias;
  }

  public function getParent($type = null) {
    if (!$type) {
      return $this->parent;
    }
    $current = $this;
    while ($current && $parent = $current->getParent()) {
      if ($parent instanceof $type) {
        return $parent;
      }
      $current = $parent;
    }
    return null;
  }
  public function getParents($type = null) {
    $arr = array();
    $current = $this;
    while ($current && $parent = $current->getParent()) {
      if ($type != null && $parent instanceof $type) {
        $arr[] = $parent;
      } else {
        $arr[] = $parent;
      }
      $current = $parent;
    }
    return $arr;
  }
  public function setParent($parent) {
    $this->parent = $parent;
  }
  public function setName($name) {
    $this->name = $name;
  }
  public function getName() {
    return $this->name;
  }


  public function __construct($data = null) {
    @$this->setName($data["name"]);
    @$this->setAlias($data["alias"]);
    if (isset($data["fields"]) && is_array($data["fields"])) {
      foreach ($data["fields"] as $field) {
        $obj = null;
        if (empty($field["fields"])) {
          $obj = new Field($field);
        } else {
          $obj = new Relation($field);
        }
        $obj->setParent($this);
        $this->addChild($obj);
      }
    }
  }
}

?>