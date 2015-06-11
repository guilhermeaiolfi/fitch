<?php

namespace fitch\sql;

use \fitch\fields\Relation as Relation;
use \fitch\fields\Field as Field;

class ArrayHydration {
  protected $query = null;
  protected $segment = null;
  protected $meta = null;
  public function __construct($query, $segment, $meta = null) {
    $this->query = $query;
    $this->segment = $segment;
    $this->meta = $meta;
  }
  public function getResult($rows) {

    $fields = $this->query->getFields();
    $segment = $this->segment;
    $query = $this->query;

    $result = array();
    $index = 0;
    $current = &$result;
    foreach($rows as $row) {
      //print_r($row);
      $id_label = $segment->getAliasOrName();
      //print_r($row);
      //print_r($fields);
      foreach ($row as $key => $value) {
        if (!is_numeric($key)) { continue; }
        $field = $fields[$key];
        $this->setArrayValue($result, $field, $row, $value);
      }
    }
    return $result;
  }
  public function getPrimaryKeyHashIndexFrom($relation) {
    $i = 0;
    foreach ($this->query->getFields() as $field) {
      //echo get_class($field);exit;
      if ($field instanceof \fitch\fields\PrimaryKeyHash && $field->getParent() == $relation) {
        return $i;
      }
      $i++;
    }
    return -1;
  }
  public function &makePath(&$result, $field, $row) {
    $meta = $this->meta;
    $current = $field;
    $main_key = $this->getPrimaryKeyHashIndexFrom($field->getParent("\\fitch\\fields\\Segment"));
    if (empty($result[$row[$main_key]])) {
      $result[$row[$main_key]] = array();
    }
    $arr = &$result[$row[$main_key]];
    $name = $field->getName();

    $parents = $field->getParents();

    for ($i = 0; $i < count($parents) - 1; $i++) {
      if ($parents[$i] instanceof Relation) {
        $relation = $parents[$i];
        $name = $relation->getName();
        if (empty($arr[$name])) {
          $arr[$name] = array();
        }
        $arr = &$arr[$name];

        $key = $this->getPrimaryKeyHashIndexFrom($relation);
        if (empty($arr[$row[$key]])) {
          // here we should test if it could return more tan one record
          // and create a array of records if it can, otherwise use just the array of the record
          $arr[$row[$key]] = array();
        }
        $arr = &$arr[$row[$key]];
        continue;
      }
    }
    return $arr;
  }
  public function setArrayValue(&$result, $field, $row, $value) {
    if ($field instanceof \fitch\fields\PrimaryKeyHash) return;

    $arr = &$this->makePath($result, $field, $row);

    $name = $field->getName();

    if (!$this->canRepeat($field)) {
      $arr[$name] = $value;
    } else {
      if (empty($arr[$name])) {
        $arr[$name] = array();
      }
      $arr[$name][] = $value;
    }

    return $result;
  }
  public function canRepeat($field) {
    $meta = $this->meta;

    return $meta->isManyToManyRelation($field->getRelationName());
  }
}

?>