<?php

namespace fitch;

class Generator {
  private $root = null;
  public function __construct($segment = null, $meta) {
    $this->root = $segment;
    $this->meta = $meta;
  }
  public function setRoot($segment) {
    $this->root = $segment;
  }
  public function getRoot() {
    return $this->root;
  }
  public function getCode() {
    throw new Exception("Error Processing Request", 1);
  }
}

?>