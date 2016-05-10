<?php
namespace fitch\fields;

use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;

class Segment extends Relation {
  public function __construct ($meta, $data, $parent = NULL) {
    parent::__construct($meta, $data, $parent);
  }
}

?>