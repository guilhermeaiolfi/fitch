<?php

namespace fitch\sql;

use \fitch\fields\Relation as Relation;
use \fitch\fields\Field as Field;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class ArrayHydration {
  protected $segment = null;
  protected $meta = null;
  protected $line = array();
  public function __construct($segment, $meta = null) {
    $this->segment = $segment;
    $this->meta = $meta;
  }

  public function getResult($rows) {
    $result = array();
    $index = 0;
    foreach($rows as $row) {
      $this->populateRow($result, $row);
    }
    return $result;
  }

  public function getLineFor($ids){
    $last = $this->line;
    $last_id = NULL;
    foreach ($ids as $item) {
      $name = key($item);
      $id = $item[$name];

      $last = $last[$name][$id];
      $last_id = $id;
    }
    $line = is_array($last)? $last["_value"] : NULL;
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

  public function setFieldValue(&$pointer, $field, $value) {
    $name = $field->getAliasOrName();
    if ($field->getParent()->isMany() && $field->getParent()->isGenerated()) {
      if (!is_array($pointer[$name])) {
        $pointer[$name] = array();
      }
      $pointer[$name][] = $value;
    } else {
      $pointer[$name] = $value;
    }
  }
  public function populateRelation(&$arr, $node, $row, $ids, $column_index) {
    $name = $node->getAliasOrName();
    $pointer = &$arr;
    $empty = false;

    $id = $row[$column_index];

    if (!$node->isGenerated()) {
      if (!is_array($arr[$name])) {
        $arr[$name] = array();
      }
    }

    if (!$id) {
      //probably because $relation->getType() == "<"
      // it doesn't matter, we can't set values
      $empty = true;
    } else if (!$node->isGenerated()) {

      $ids[] = array($name => $id);

      if ($node->isMany()) {
        $line = $this->getLineFor($ids);
        if ($line === NULL) {
          $arr[$name][] = array();
          $line = count($arr[$name]) - 1;
          $this->setLineFor($ids, $line);
        }

        $pointer = &$arr[$name][$line];
      } else {
        $pointer = &$arr[$name];
      }
    }

    if ($children = $node->getChildren()) {
      // FOR QueryGenerator (not NestedQueryGenerator) it needs to loop two times
      // because fields are grouped together and relation are pulled to the end
      foreach ($children as $child) {
        if ($child instanceof Field) {
          if ($child->isVisible() && !$empty) {
            $this->setFieldValue($pointer, $child, $row[$column_index]);
          }
          $column_index++;
        } else if ($child instanceof Relation) {
          $column_index = $this->populateRelation($pointer, $child, $row, $ids, $column_index);
        }
      }
    }
    return $column_index;
  }

  public function populateRow(&$result, $row) {
    $ids = array();
    $this->populateRelation($result, $this->segment, $row, $ids, 0);
  }
}

?>