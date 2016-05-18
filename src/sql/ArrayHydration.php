<?php

namespace fitch\sql;

use \fitch\fields\Relation as Relation;
//use \fitch\fields\Field as Field;
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

  public function setFieldValue (&$level, $field, $value) {
    $relation = $level["_relation"];
    $name = $field->getAliasOrName();

    $arr = &$level["_pointer"];
    if ($field->isMany()) {
      if (!is_array($arr[$name])) {
        $arr[$name] = array();
      }
      $arr[$name][] = $value;
    } else {
      $arr[$name] = $value;
    }
  }

  public function populateRow(&$result, $row) {
    $arr = &$result;
    $relations = array();
    $ids = array();

    $column_index = 0;
    $pending = array($this->segment);

    while ($node = array_shift($pending)) {
      $name = $node->getAliasOrName();

      if ($node instanceof \fitch\fields\Relation) {
        if ($children = $node->getChildren()) {
          foreach ($children as $child) {
            $pending[] = $child;
          }
          if ($node->isGenerated()) {
            continue;
          }
        }
        if (!is_array($arr[$name])) {
          $arr[$name] = array();
        }

        $id = $row[$node->getPkIndex()];

        $ids[] = array($name => $id);

        if ($node->isMany()) {

          $line = $this->getLineFor($ids);
          if ($line === NULL) {
            $arr[$name][] = array();
            $line = count($arr[$name]) - 1;
            $this->setLineFor($ids, $line);
          }

          $arr = &$arr[$name][$line];
        } else {
          $arr = &$arr[$name];
        }

        $relations[] = array("_pointer" => &$arr, "_relation" => $node);

      } elseif ($node instanceof \fitch\fields\Field) {
        if ($node->isVisible()) {
          $this->setFieldValue($relations[$node->getLevel()], $node, $row[$column_index]);
        }
        $column_index++;
      }
    }
  }
}

?>