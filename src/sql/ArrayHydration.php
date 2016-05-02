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
        $pointer[$alias]["_level"] = $field->getLevel();
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

  public function getLineFor($ids){
    $last = $this->line;
    $last_id = NULL;
    foreach ($ids as $item) {
      $name = key($item);
      $id = $item[$name];

      $last = $last[$name];
      $last_id = $id;
    }
    $line = is_array($last) && is_array($last[$last_id])? $last[$last_id]["_value"] : NULL;
    return $line;
  }

  public function setLineFor($ids, $line) {
    $last = &$this->line;
    $last_id = NULL;
    foreach ($ids as $item) {
      $name = key($item);
      $id = $item[$name];
      if (!is_array($last[$name])) {
        $last[$name] = array();
      }
      if (!is_array($last[$name][$id])) {
        $last[$name][$id] = array();
      }
      $last = &$last[$name][$id];
      $last_id = $id;
    }

    $last["_value"] = $line;
  }

  public function setFieldValue (&$level, $row, $field) {
    $relation = $level["_relation"];
    $name = $field["_name"];

    $arr = &$level["_pointer"];
    if ($field["_many"]) {
      if (!is_array($arr[$name])) {
        $arr[$name] = array();
      }
      $arr[$name][] = $row[$field["_column_index"]];
    } else {
      $arr[$name] = $row[$field["_column_index"]];
    }
  }

  public function populateRow(&$result, $row, $mapping) {
    $pending = $mapping;
    $arr = &$result;
    $level = array();

    $ids = array();

    while ($node = array_shift($pending)) {
      $column_index = $node["_column_index"];
      $name = $node["_name"];

      if ($node["_type"] == "relation") {
        if (is_array($node['_children'])) {
          foreach ($node['_children'] as $child) {
            $pending[] = $child;
          }
        }
        if (!is_array($arr[$name])) {
          $arr[$name] = array();
        }

        $id = $row[$node["_id"]["_column_index"]];

        $ids[] = array($name => $id);

        $line = $this->getLineFor($ids);
        if ($line === NULL) {
          $arr[$name][] = array();
          $line = count($arr[$name]) - 1;
          $this->setLineFor($ids, $line);
        }

        $arr = &$arr[$name][$line];

        $level[] = array("_pointer" => &$arr, "_relation" => $node);

      } elseif ($node["_type"] == "field") {
        $this->setFieldValue($level[$node["_level"]], $row, $node);
      } elseif ($node["_type"] == "primary_key") {
      }
    }
  }
}

?>