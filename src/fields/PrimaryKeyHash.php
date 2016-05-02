<?php
namespace fitch\fields;

use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;

class PrimaryKeyHash extends Field {

  protected $primary_key = array();
  protected $field = NULL;


  public function setPrimaryKey($keys) {
    $this->primary_key = $keys;
  }
  public function getPrimaryKey($keys) {
    return $this->primary_key;
  }


  public function setField($field) {
    return $this->field = $field;
  }

  public function getField() {
    return $this->field;
  }

  public function getName() {
    if (count($this->primary_key) == 1) {
      return $this->primary_key[0];
    }
    $name = "CONCAT_WS('-'";
    foreach($this->primary_key as $key) {
      $name .= "," . $key;
    }
    $name .= ")";
    return $name;
  }
}

?>