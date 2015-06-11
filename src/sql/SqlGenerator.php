<?php

namespace fitch\sql;

use \fitch\Generator as Generator;
use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;

class SqlGenerator extends Generator {

  public function getQueries() {
    $root = $this->getRoot();

    $queries = array();

    $queries[] = $query = new Query();

    $query->setTable($root->getName());

    $query->setAlias($root->getAlias());

    $joins = array();

    foreach ($root->getListOf("\\fitch\\fields\\Relation") as $relation) {
      foreach ($relation->getJoins() as $join) {
        $query->addJoin($join);
      }
    }

    $query->setFields($root->getListOf("\\fitch\\fields\\Field"));

    return $queries;
  }
}

?>