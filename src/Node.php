<?php

namespace fitch;

use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;
use \fitch\fields\SoftRelation as SoftRelation;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class Node {
  protected $children = array();
  protected $name = null;
  protected $parent = null;
  protected $meta = null;
  protected $alias = null;
  protected $visible = true;
  protected $generated = false;

  public function setGenerated($b) {
    $this->generated = $b;
  }

  public function isGenerated() {
    return $this->generated;
  }

  public function setVisible($visible) {
    $this->visible = $visible;
  }

  public function isVisible() {
    return $this->visible;
  }

  public function getChildren() {
    return $this->children;
  }

  public function setChildren($children) {
    $this->children = $children;
  }

  public function getMeta() {
    return $this->meta;
  }

  public function setMeta($meta) {
    $this->meta = $meta;
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

  public function __construct($meta, $data, $parent = NULL) {
    $this->setMeta($meta);
    $this->setGenerated(!!$data["generated"]);
    @$this->setName($data["name"]);
    @$this->setAlias($data["alias"]);

    $parts = explode(".", $data["name"]);

    $n = count($parts);

    if (!$parent) {
      $parent = $this;
    }

    if ($n > 1) {
      $data["name"] = array_shift($parts);
      $data["generated"] = true;
      $data["fields"] = array(
          array(
            "name" => implode(".", $parts),
            "generated" => false,
            //"parent" => $data,
            "alias" => $data["alias"],
            "fields" => $data["fields"]
          )
      );

      $this->setName($data["name"]);
      $this->setGenerated(true);
      $this->setAlias(NULL);
      $this->setVisible(false);
      $parent = $this;
    }


    if (isset($data["fields"]) && is_array($data["fields"])) {
      foreach ($data["fields"] as $field) {
        //$field["parent"] = $data;
        $obj = null;

        $join = $meta->getRelationConnections($parent? $parent->getName() : NULL, $field["name"]);
        if (empty($field["fields"]) && strpos($field["name"], ".") === false && $join == NULL) {
          $obj = new Field($meta, $field, $parent);
        } else { // relation
          $obj = new Relation($meta, $field, $this);
        }
        $obj->setParent($this);
        $this->addChild($obj);
      }
    }
  }
}

?>