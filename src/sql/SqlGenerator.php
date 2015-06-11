<?php

namespace fitch\sql;

use \fitch\Generator as Generator;

class SqlGenerator extends Generator {

  public function getQueries() {
    $root = $this->getRoot();

    $queries = array();

    $queries[] = $query = new Query();

    $query->setTable($root->getName());

    $query->setAlias($root->getAlias());

    $joins = array();

    foreach ($root->getListOf("fitch\Relation") as $relation) {
      foreach ($relation->getJoins() as $join) {
        $query->addJoin($join);
      }
    }

    $query->setFields($root->getListOf("fitch\Field"));

    return $queries;
  }
}

?>