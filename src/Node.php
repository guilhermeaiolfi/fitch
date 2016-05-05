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

  public function __construct($meta, $data = null) {
    $this->setMeta($meta);
    $this->setGenerated(!!$data["generated"]);
    @$this->setName($data["name"]);
    @$this->setAlias($data["alias"]);

    $parts = explode(".", $data["name"]);
    $n = count($parts);

    if ($n > 1) {
      //echo "dsadsa";exit;
      $data["name"] = array_shift($parts);
      $this->setName($data["name"]);
      $this->setGenerated(true);
      $this->setAlias(NULL);
      $data["fields"] = array(
          array(
            "name" => implode(".", $parts),
            "generated" => false,
            "alias" => $data["alias"],
            "fields" => $data["fields"]
          )
      );
    }
    //print_r($data);


    if (isset($data["fields"]) && is_array($data["fields"])) {
      foreach ($data["fields"] as $field) {
        $obj = null;

        if (empty($field["fields"]) && strpos($field["name"], ".") === false && $meta->getRelationConnections($data["name"] . "." . $field["name"]) == NULL) {
          $obj = new Field($meta, $field);
        } else { // relation
            $obj = new Relation($meta, $field);
        }
        $obj->setParent($this);
        $this->addChild($obj);
      }
    }
  }
}

?>