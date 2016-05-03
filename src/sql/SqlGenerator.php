<?php

namespace fitch\sql;

use \fitch\Generator as Generator;
use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;
use \fitch\fields\Segment as Segment;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class SqlGenerator extends Generator {

  public function getQueries() {
    $root = $this->getRoot();
    $queries = array();
    $joins = array();
    $meta = $this->getMeta();

    $queries[] = $query = new Query();
    $query->setRoot($root);
    $fields[] = $root;

    $query->setConditions($root->getConditions());

    while ($field = array_shift($fields)) {
      if ($field instanceof Relation) {
        foreach ($field->getJoins() as $join) {
          $query->addJoin($join);
        }

        $children = $field->getChildren();

        foreach ($children as $child) {
          $fields[] = $child;
        }
      } else {
        $query->addField($field);
      }
    }
    $function = $root->getFunction("sort");
    for($i = 0; $i < count($function); $i++) {
      $query->addSortBy($function[$i]);
    }
    if ($function = $root->getFunction("limit")) {
      $query->limit($function["limit"], $function["offset"]);
    }

    return $queries;
  }
}

?>