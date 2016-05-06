<?php
namespace fitch;

class Meta {
  public $meta = null;
  public function __construct($meta) {
    $this->meta = $meta;
  }
  public function getPrimaryKey($table) {
    return array("id");
  }
  public function getRelationConnections($table, $relation){
    return $this->meta[$table]["foreign_keys"][$relation]["on"];
  }
  public function getPrimaryKeyName($relation) {
    return $this->meta[$relation->getTable()]["primary_key"];
  }
  public function getTableNameFromRelation($parent_name, $relation_name) {
    return $this->meta[$parent_name]["foreign_keys"][$relation_name]["table"];
  }
  public function isManyToMany($node) {
    if ($node instanceof \fitch\fields\Relation) {
      if ($node->getParent()) {
        return count($this->getRelationConnections($node->getParent()->getName(), $node->getName())) == 2;
      }
      return true;
    } else { //field
      $relation = $node->getParent();
      if ($relation->getParent() && $relation->isGenerated()) {
        return count($this->getRelationConnections($relation->getParent()->getName(), $relation->getName())) == 2;
      }
    }
    return false;
  }

  public function getFields($table) {
    return $this->meta[$table]["fields"];
  }
}
?>