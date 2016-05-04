<?php

namespace fitch\fields;

use \fitch\Join as Join;

class Relation extends \fitch\fields\Field {
  public function __construct($meta, $data = null) {
    parent::__construct($meta, $data);
    if (count($this->children) == 0) {
      $fields = $meta->getFields($this->getRelationName());
      if (is_array($fields)) {
        foreach($fields as $field) {
          $field = new Field($meta, is_array($field)? $field : array("name" => $field));
          $field->setParent($this);
          $this->addChild($field);
        }
      }
    }
    $this->createHashField();
  }
  protected function createHashField() {
    $primary_key_field = new PrimaryKeyHash($this->getMeta(), array('name' => "id"));
    $primary_key_field->setPrimaryKey(array("id"));
    $primary_key_field->setName("id");
    //$keys = $this->getMeta()->getPrimaryKey($this->getMeta()->getTableNameFromRelation($this->getRelationName()));
    $primary_key_field->setParent($this);
    $children = $this->getChildren();
    $replaced = false;
    for ($i = 0; $i < count($children); $i++) {
      if ($children[$i]->getName() == "id") { //TODO: removed hardcode primary_key

        $primary_key_field->setField($children[$i]);
        $this->children[$i] = $primary_key_field;
        $replaced = true;
        break;
      }
    }
    if (!$replaced) {
      $primary_key_field->setVisible(false);
      array_unshift($this->children, $primary_key_field);
    }
    return $primary_key_field;
  }

  public function getPkIndex() {
    $root = $this->getParent("\\fitch\\fields\\Segment");

    if (!$root) { $root = $this; }

    $nodes = $root->getListOf("\\fitch\\fields\\Field");
    $i = 0;
    foreach ($nodes as $node) {

      if ($node instanceof \fitch\fields\Relation) { continue; }
      if ($node->getParent() == $this && $node instanceof \fitch\fields\PrimaryKeyHash) { return $i; }
      $i++;
    }
    return -1;
  }

  public function getJoins() {
    $relation = $this;
    $joins = [];
    if ($relation instanceof \fitch\fields\Relation) {
      if ($relation instanceof \fitch\fields\Segment) {
        break;
      }
      $join = new Join();
      $join->setRelation($relation);
      // if ($relation->isGenerated()) {
      // }
      $joins[] = $join;
      //$relation = $relation->getParent();
    }
    /*$parts = explode(".", $this->getName());
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
    }*/
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