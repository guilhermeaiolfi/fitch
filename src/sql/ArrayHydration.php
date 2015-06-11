<?php

namespace fitch\sql;

class ArrayHydration {
  protected $query = null;
  protected $segment = null;
  public function __construct($query, $segment) {
    $this->query = $query;
    $this->segment = $segment;
  }
  public function getResult() {

    $fields = $this->segment->getListOf("fitch\Field");
    $segment = $this->segment;
    $query = $this->query;

    $result = array();
    $index = 0;
    $current = &$result;
    while($row = $query->fetch()) {
      $id_label = $segment->getAliasOrName();

      foreach ($row as $key => $value) {
        if (!is_numeric($key)) { continue; }
        $field = $fields[$key];
        $this->setArrayValue($result, $field, $row, $value);
      }
    }
    return $result;
  }
  public function setArrayValue(&$result, $field, $row, $value) {
    $current = $field;
    if (empty($result[$row["code"]])) {
      $result[$row["code"]] = array();
    }
    $arr = &$result[$row["code"]];
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

        if (empty($arr[$row["coderelation1"]])) {
          // here we should test if it could return more tan one record
          // and create a array of records if it can, otherwise use just the array of the record
          $arr[$row["coderelation1"]] = array();
        }
        $arr = &$arr[$row["coderelation1"]];
        continue;
      }
    }
    $name = $field->getName();
    if (empty($arr[$name])) {
      $arr[$name] = $value;
    } else if (!is_array($arr[$name]) && $arr[$name] != $value) {
      $prev = $arr[$name];
      $arr[$name] = array();
      $arr[$name][] = $prev;
      $arr[$name][] = $value;
    } else if (is_array($arr[$name])){
      $arr[$name][] = $value;
    }
    return $result;
  }
}

?>