<?php

namespace fitch\fields;

use \fitch\Join as Join;

class Relation extends \fitch\fields\Field {
  public function __construct($meta, $data, $parent = NULL) {
    parent::__construct($meta, $data, $parent);
  }
}

?>