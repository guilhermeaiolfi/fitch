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
  public function getRelationConnections($relation_name){
    return $this->meta[$relation_name];
  }
  public function getTableNameFromRelation($relation_name) {
    $connections = $this->getRelationConnections($relation_name);
    if (!is_array($connections)) {
      return $relation_name;
    }
    $last = array_pop($connections);
    return explode(".", $last)[0];
  }
}
?>