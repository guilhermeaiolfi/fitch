<?php

namespace fitch\fields;

use \fitch\Join as Join;

class OneRelation extends \fitch\fields\Relation {
  public function __construct($meta, $data, $parent = NULL) {
    parent::__construct($meta, $data, $parent);
  }
}

?>