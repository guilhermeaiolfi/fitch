<?php

namespace fitch\sql;

use \fitch\fields\Relation as Relation;
use \fitch\fields\Field as Field;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class ArrayHydration {
  protected $query = null;
  protected $segment = null;
  protected $meta = null;
  public function __construct($query, $segment, $meta = null) {
    $this->query = $query;
    $this->segment = $segment;
    $this->meta = $meta;
  }
  public function getMapping() {
    $meta = $this->meta;
    $mapping = array();
    $pending = array($this->segment);
    $pointer = &$mapping;
    $last_relation = $current;

    $i = 0;
    while ($field = array_shift($pending)) {
      $name = $field->getName();
      if (is_a($field, "\\fitch\\fields\\Relation")) {
        $pointer[$name] = array();
        $pointer = &$pointer[$name];
        $pointer["_name"] = $name;
        $pointer["_type"] = "relation";
        $pointer["_leaf"] = false;
        $pointer["_many"] = $field == $this->segment || $meta->isManyToManyRelation($field->getRelationName())? true : false;
        foreach($field->getChildren() as $child) {
          $pending[] = $child;
        }
        $pointer["_id"] = array("_name" => "_id", "_column_index" => $i, "_type" => "primary_key");
        $pointer["_children"] = array();
        $pointer = &$pointer['_children'];
      } else {
        $pointer[$name] = array();
        if (strpos($name,".") !== FALSE)  {
          $pointer[$name]["_id"] = array("_name" => "_id", "_column_index" => $i, "_type" => "primary_key");
          $i++;
        }
        $pointer[$name]["_name"] = $name;
        $pointer[$name]["_leaf"] = true;
        $pointer[$name]["_type"] = "field";
        $pointer[$name]["_many"] = $meta->isManyToManyRelation($field->getRelationName());
        $pointer[$name]["_column_index"] = $i;

      }
      $i++;
    }

    return $mapping;
  }
  public function getResult($rows) {
    $result = array();
    $index = 0;
    $mapping = $this->getMapping();
    foreach($rows as $row) {
      $this->populateRow($result, $row, $mapping);
    }
    return $result;
  }
  public function populateRow(&$result, $row, $mapping) {
    $pending = $mapping;
    $arr = &$result;
    while ($node = array_shift($pending)) {
      $column_index = $node["_column_index"];
      $name = $node["_name"];
      if ($node["_type"] == "relation") {
        if (!is_array($arr[$name])) {
          $arr[$name] = array();
        }
        $arr = &$arr[$name];
        if (is_array($node['_children'])) {
          foreach ($node['_children'] as $child) {
            $pending[] = $child;
          }
        }
        $id = $row[$node["_id"]["_column_index"]];
        if (!is_array($arr[$id])) {
          $arr[$id] = array();
        }
        $arr = &$arr[$id];

      } elseif ($node["_type"] == "field") {
        if ($node["_many"]) {
          if (!is_array($arr[$name])) {
            $arr[$name] = array();
          }
          $id = $row[$node["_id"]["_column_index"]];
          $arr[$name][$id] = $row[$node["_column_index"]];
        } else {
          $arr[$name] = $row[$node["_column_index"]];
        }
      } elseif ($node["_type"] == "primary_key") {
      }
    }
  }
}

?>