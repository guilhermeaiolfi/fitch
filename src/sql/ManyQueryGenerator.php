<?php

namespace fitch\sql;

use \fitch\Generator as Generator;
//use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;
use \fitch\fields\OneRelation as OneRelation;
use \fitch\fields\ManyRelation as ManyRelation;
use \fitch\fields\Segment as Segment;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class ManyQueryGenerator extends QueryGenerator {

  protected $cache = array();

  protected $queries = array();

  public function createUpJoins($relation) {
    $meta = $this->getMeta();
    $connections = $meta->getRelationConnections($relation->getParent()->getName(), $relation->getName());
    $joins = array();
    $join_table = new \fitch\sql\Table();

    $first = true;
    foreach ($connections as $left => $right) {

      if ($first) {
        $join = new \fitch\sql\JoinOne();

        $join->setJoinTable($join_table);
        
        list($parent_table_name, $parent_field) = explode(".", $left);
        list($join_table_name, $join_field) = explode(".", $right);

        $join_table->setName($join_table_name);
        $join_table->setAlias($this->getTableAliasFor($join));

        
        $parent_table = $this->getOrCreateTable($relation->getParent());

        $join->setParentTable($parent_table);

        $join->setParentField($parent_field);
        $join->setJoinField($join_field);

        $joins[] = $join;
        $first = false;

      } else {
        list($join_table_name, $parent_field) = explode(".", $left);
        list($relation_table_name, $relation_field) = explode(".", $right);

        $relation_table = $this->getOrCreateTable($relation->getParent());

        $join = new \fitch\sql\JoinOne();

        $join->setJoinTable($relation_table);
        $join->setParentTable($join_table);

        $join->setJoinField($relation_field);
        $join->setParentField($join_field);

        $joins[] = $join;
      }
    }
    return $joins;
  }

  public function generateQueryForManyRelation ($relation) {
    $joins = array();
    $meta = $this->getMeta();

    $query = new Query();
    $root = $this->getOrCreateTable($relation);
    $query->SetRoot($root);

    $fields[] = $relation;
    $parent = $relation;
    while ($parent) {
      if ($parent->getParent()) {
        $joins = $this->createUpJoins($parent);
        foreach ($joins as $join) {
          $query->addJoin($join);
        }
      }
      $parent = $parent->getParent();
    }

    foreach ($relation->getChildren() as $field) {
      if (!$field instanceof Relation) {
        $sql_field = new \fitch\sql\Field();
        $sql_field->setName($field->getName());
        $sql_field->setAlias($field->getAlias());
        $sql_field->setTable($this->getOrCreateTable($field));
        $query->addField($sql_field);
      }
    }
   

    return $query;
  }
  public function generateQueryForRelation($relation) {
    $joins = array();
    $meta = $this->getMeta();

    $query = new Query();
    $root = new \fitch\sql\Table();
    $root->setName($relation->getName());
    $root->setAlias($this->getTableAliasFor($relation));
    $query->setRoot($root);
    $fields[] = $relation;

    while ($field = array_shift($fields)) {
      if ($field instanceof Relation) {
        if ($field->getParent() && $field != $relation) {
          if ($field instanceof OneRelation) {
            $joins = $this->createJoins($field);
            foreach ($joins as $join) {
              $query->addJoin($join);
            }
          } else {
            $this->queries[] = $this->generateQueryForManyRelation($field);
          }
        }
        
        $children = $field->getChildren();
        foreach ($children as $child) {
          $fields[] = $child;
        }
      } else if ($field->getParent() == $relation || $field->getParent() instanceof OneRelation) {
        $sql_field = new \fitch\sql\Field();
        $sql_field->setName($field->getName());
        $sql_field->setAlias($field->getAlias());
        $sql_field->setTable($this->getOrCreateTable($field));
        $query->addField($sql_field);
      }
    }
    if ($relation instanceof Segment) {
      $conditions = $this->replaceFieldWithFieldsSql($relation->getConditions());
      $query->setConditions($conditions);
      $function = $relation->getFunction("sort");
      for($i = 0; $i < count($function); $i++) {
        $field = $function[$i]["field"];
        $sql_field = new \fitch\sql\Field();
        $sql_field->setName($field->getName());
        $sql_field->setAlias($field->getAlias());
        $sql_field->setTable($this->getOrCreateTable($field->getParent()));
        $function[$i]["field"] = $sql_field;
        $query->addSortBy($function[$i]);
      }
      if ($function = $relation->getFunction("limit")) {
        $query->limit($function["limit"], $function["offset"]);
      }
    }

    return $query;
  }

  public function getQueries() {
    $root = $this->getRoot();
    $this->queries[] = $this->generateQueryForRelation($root);
    foreach ($this->queries as $query) {
      echo $query->getSql($root->getMeta()) . "\n";
    }
    exit;
    return $this->queries;
  }

}

?>