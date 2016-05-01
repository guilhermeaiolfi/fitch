<?php

namespace fitch\sql;

use \fitch\fields\Relation as Relation;
use \fitch\fields\SoftRelation as SoftRelation;
use \fitch\fields\Field as Field;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class ArrayHydration {
  protected $query = null;
  protected $segment = null;
  protected $meta = null;
  protected $line = array();
  public function __construct($query, $segment, $meta = null) {
    $this->query = $query;
    $this->segment = $segment;
    $this->meta = $meta;
  }

  public function getColumnIndexForPK($field, $root) {
    $nodes = $root->getListOf("\\fitch\\fields\\Field");
    $i = 0;
    foreach ($nodes as $node) {
      if ($node->getParent() == $field && $node->getName() == "id") { return $i; }
      if ($node instanceof Relation) {
        if (!$field->hasPrimaryKey()) {
          $i++;
        }
      } else {
        $i++;
      }
      $a++;
    }
    return -1;
  }

  public function getMapping() {
    $meta = $this->meta;
    $root = $this->segment;
    $mapping = array();
    $pending = array($root);
    $pointer = &$mapping;

    $i = 0;
    while ($field = array_shift($pending)) {
      $alias = $field->getAliasOrName();
      if ($field instanceof SoftRelation) {
        foreach($field->getChildren() as $child) {
          $pending[] = $child;
        }
      } else if (is_a($field, "\\fitch\\fields\\Relation")) {
        $pointer[$alias] = array();
        $pointer = &$pointer[$alias];
        $pointer["_name"] = $alias;
        $pointer["_type"] = "relation";
        $pointer["_leaf"] = false;
        $pointer["_many"] = $field == $this->segment || $meta->isManyToManyRelation($field->getRelationName())? true : false;
        foreach($field->getChildren() as $child) {
          $pending[] = $child;
        }
        $id_column_index = $this->getColumnIndexForPK($field, $root);
        $generated = $id_column_index == -1;
        if ($generated) {
          $id_column_index = $i++;
        }
        $pointer["_id"] = array("_name" => "_id", "_column_index" =>  $id_column_index, "_generated" => $generated, "_type" => "primary_key");
        $pointer["_children"] = array();
        $pointer = &$pointer['_children'];
      } else {
        $pointer[$alias] = array();
        $name = $field->getName();
        if ($field->getParent() instanceof SoftRelation)  {
          $id_column_index = $this->getColumnIndexForPK($field->getParent(), $root);
          $generated = $id_column_index == -1;
          if ($generated) {
            $id_column_index = $i++;
          }
          $pointer[$alias]["_id"] = array("_name" => "_id", "_column_index" => $id_column_index, "_generated" => $generated, "_type" => "primary_key");
        }
        $pointer[$alias]["_name"] = $alias;
        $pointer[$alias]["_leaf"] = true;
        $pointer[$alias]["_type"] = "field";
        $pointer[$alias]["_many"] = $meta->isManyToManyRelation($field->getRelationName());
        $pointer[$alias]["_column_index"] = $i;

        $i++;
      }
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

  public function getLineFor($relation, $id){
    return is_array($this->line[$relation])? $this->line[$relation][$id] : NULL;
  }

  public function setLineFor($relation, $id, $line) {
    if (!is_array($this->line[$relation])) {
      $this->line[$relation] = array();
    }
    $this->line[$relation][$id] = $line;
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

        $line = $this->getLineFor($name, $id);
        if ($line === NULL) {
          $arr[] = array();
          $line = count($arr) - 1;
          $this->setLineFor($name, $id, $line);
        }
        $arr = &$arr[$line];
      } elseif ($node["_type"] == "field") {
        if ($node["_many"]) {
          if (!is_array($arr[$name])) {
            $arr[$name] = array();
          }
          $id = $row[$node["_id"]["_column_index"]];
          $line = $this->getLineFor($name, $id);
          if ($line === NULL) {
            $line = count($arr[$name]);
            $this->setLineFor($name, $id, $line);
          }
          $arr[$name][$line] = $row[$node["_column_index"]];
        } else {
          //echo $name;
          $arr[$name] = $row[$node["_column_index"]];
        }
      } elseif ($node["_type"] == "primary_key") {
      }
    }
  }
}

?>