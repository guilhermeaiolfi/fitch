<?php

namespace fitch\sql;

use \fitch\Generator as Generator;
use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;
use \fitch\fields\Segment as Segment;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class SqlGenerator extends Generator {

  public function createHashField($relation) {
    $primary_key_field = new PrimaryKeyHash(array('alias' => $relation->getName() . "_" . "id"));
    $primary_key_field->setPrimaryKey(array("id"));
    $keys = $this->meta->getPrimaryKey($this->meta->getTableNameFromRelation($relation->getRelationName()));
    $primary_key_field->setParent($relation);
    $fields = $relation->getListOf("\\fitch\\fields\\Field");
    foreach ($fields as $field) {
      if ($field->getName() == "id") {
        $primary_key_field->setField($field);
        break;
      }
    }
    return $primary_key_field;
  }
  public function getQueries() {
    $root = $this->getRoot();

    $queries = array();

    $queries[] = $query = new Query();

    $joins = array();

    $meta = $this->getMeta();

    $fields[] = $root;

    //$user_fields = $root->getListOf("\\fitch\\fields\\Field");

    while ($field = array_shift($fields)) {
      if ($field instanceof Segment) {
        $query->setRoot($field);
        foreach ($field->getJoins() as $join) {
          $query->addJoin($join);
        }

        $query->addField($this->createHashField($field));

        $children = $field->getChildren();

        foreach ($children as $child) {
          $fields[] = $child;
        }
      } else if ($field instanceof Relation) {
        foreach ($field->getJoins() as $join) {
          $query->addJoin($join);
        }
        $query->addField($this->createHashField($field));

        $children = $field->getChildren();

        foreach ($children as $child) {
          $fields[] = $child;
        }
      } else {
        if (!$field->hasDot()) {
          $query->addField($field);
        } else {
          $relation = $field->getParent();
          foreach ($relation->getJoins() as $join) {
            $query->addJoin($join);
          }
          $query->addField($this->createHashField($relation));
          //$field->setParent($relation);
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