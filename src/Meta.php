<?php
namespace fitch;

class Meta {
  public $meta = null;
  public function __construct($meta) {
    $this->meta = $meta;
  }
  public function hasField($table, $field) {
    $fields = $this->getFields($table);
    if (!is_array($fields)) {
      return false;
    }
    foreach ($fields as $item) {
      if (is_array($item)) {
        if ($item["name"] == $field) {
          return true;
        }
      } else {
        if ($item == $field) {
          return true;
        }
      }
    }
    return false;
  }
  public function getPrimaryKey($table) {
    return is_array($this->meta[$table]["primary_key"])? $this->meta[$table]["primary_key"] : array($this->meta[$table]["primary_key"]);
  }
  public function getRelationConnections($table, $relation){
    return $this->meta[$table]["foreign_keys"][$relation]["on"];
  }
  public function getCardinality($table, $relation){
    return $this->meta[$table]["foreign_keys"][$relation]["cardinality"];
  }
  public function getPrimaryKeyName($relation) {
    return $this->meta[$relation->getTable()]["primary_key"];
  }
  public function getTableNameFromRelation($parent_name, $relation_name) {
    return $this->meta[$parent_name]["foreign_keys"][$relation_name]["table"];
  }
  public function isMany($node) {
    if ($node instanceof \fitch\fields\Relation) {
        //print_r($node->getMapping(true));exit;
      if ($node->getParent()) {
        return $this->getCardinality($node->getParent()->getTable(), $node->getName()) == "many";
      }
      return true;
    } else { //field
      $relation = $node->getParent();
      if ($relation->getParent() && $relation->isGenerated()) {
        return ($this->getCardinality($relation->getParent()->getTable(), $relation->getName()) == "many");
      }
    }
    return false;
  }

  public function getFields($table) {
    return $this->meta[$table]["fields"];
  }
}
?>