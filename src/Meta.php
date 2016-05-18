<?php
namespace fitch;

class Meta {
  public $meta = null;
  public function __construct($meta) {
    $this->meta = $meta;
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
      if ($node->getParent()) {
        return $this->getCardinality($node->getParent()->getName(), $node->getName()) == "many";
      }
      return true;
    } else { //field
      $relation = $node->getParent();
      if ($relation->getParent() && $relation->isGenerated()) {
        return $this->getCardinality($relation->getParent()->getName(), $relation->getName()) == "many";
      }
    }
    return false;
  }

  public function getFields($table) {
    return $this->meta[$table]["fields"];
  }
}
?>