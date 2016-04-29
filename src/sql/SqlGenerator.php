<?php

namespace fitch\sql;

use \fitch\Generator as Generator;
use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class SqlGenerator extends Generator {

  public function createHashField($relation) {
    $primary_key_field = new PrimaryKeyHash(array('alias' => $relation->getName() . "_" . "id"));
    $primary_key_field->setPrimaryKey(array("id"));
    $keys = $this->meta->getPrimaryKey($this->meta->getTableNameFromRelation($relation->getRelationName()));
    $primary_key_field->setParent($relation);
    return $primary_key_field;
  }
  public function getQueries() {
    $root = $this->getRoot();

    $queries = array();

    $queries[] = $query = new Query();

    $query->setTable($root->getName());

    $query->setAlias($root->getAlias());

    $joins = array();

    $query->addField($this->createHashField($root));

    foreach ($root->getListOf("\\fitch\\fields\\Field") as $field) {
      if ($field instanceof Relation) {
        foreach ($field->getJoins() as $join) {
          $query->addJoin($join);
        }
        $query->addField($this->createHashField($field));
      } else {
        if (!$field->hasDot()) {
          $query->addField($field);
        } else {
          $relation = new Relation();
          $relation->setGenerated(true);
          $relation->setParent($field->getParent());
          $parts = $field->getParts();
          $n = count($parts);
          $relation->setName($parts[$n - 2]);
          foreach ($relation->getJoins() as $join) {
            $query->addJoin($join);
          }
          $query->addField($this->createHashField($relation));
          $query->addField($field);
        }
      }
    }
    $function = $root->getFunction("sort");
    for($i = 0; $i < count($function); $i++) {
      $query->addSortBy($function[$i]);
    }

    return $queries;
  }
}

?>