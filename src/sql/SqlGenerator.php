<?php

namespace fitch\sql;

use \fitch\Generator as Generator;
use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;
use \fitch\fields\Segment as Segment;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class SqlGenerator extends Generator {

/*  public function getRelationByRelationName($relation_name) {
    $relations = $this->getRoot()->getListOf("\\fitch\\fields\\Relation");
    foreach ($relations as $relation) {
      if ($relation->getName() == $relation_name || $relation->getAlias() == $relation_name) {
        return $relation;
      }
    }
    return NULL;
  }*/

  public function generateQueryForSegment($segment) {
    $joins = array();
    $meta = $this->getMeta();

    $query = new Query();
    $query->setRoot($segment);
    $fields[] = $segment;

    $query->setConditions($segment->getConditions());

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
    $function = $segment->getFunction("sort");
    for($i = 0; $i < count($function); $i++) {
      $query->addSortBy($function[$i]);
    }
    if ($function = $segment->getFunction("limit")) {
      $query->limit($function["limit"], $function["offset"]);
    }

    return $query;
  }

  public function getQueries() {
    $root = $this->getRoot();
    $queries = array();
    $queries[] = $this->generateQueryForSegment($root);
    return $queries;
  }
}

?>