<?php

namespace fitch\sql;

use \fitch\Generator as Generator;
use \fitch\fields\Field as Field;
use \fitch\sql\Column as Column;
use \fitch\fields\Relation as Relation;
use \fitch\fields\OneRelation as OneRelation;
use \fitch\fields\ManyRelation as ManyRelation;
use \fitch\fields\Segment as Segment;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class ManyQueryGenerator extends QueryGenerator {

  protected $cache = array();

  protected $queries = array();

  public function addUpJoins($relation, $query) {
    //$query->join($this->)
    $meta = $this->getMeta();
    $connections = $meta->getRelationConnections($relation->getName(), $relation->getParent()->getName());
    $joins = array();
    $join_table = new \fitch\sql\Table();

    $keys = array_keys($connections);

    $join = new \fitch\sql\JoinOne();

    $join->from($join_table);

    list($parent_table_name, $parent_id) = explode(".", $keys[0]);
    list($join_table_name, $join_id) = explode(".", $connections[$keys[0]]);

    $join_table->setName($join_table_name);
    $join_table->setAlias($this->getTableAliasFor($join));

    $parent_table = $this->getOrCreateTable($relation);

    $condition = $query->createParameterColumn($join_table, $join_id) . " = " . $query->createParameterColumn($parent_table, $parent_id);
    $join->setCondition($condition);

    $query->join($join);

    /* SECOND PART */
    list($join_table_name, $join_id) = explode(".", $keys[1]);
    list($relation_table_name, $relation_id) = explode(".", $connections[$keys[1]]);

    $table = NULL;
    $parent = $relation->getParent();
    if (!$this->queries[$parent->getName()]) {
      $table = $this->generateQueryForManyRelation($parent);
    }
    $table->setName($parent->getName());
    $table->setAlias($this->getTableAliasFor($table));
    $this->queries[$parent->getName()] = $table;
    $condition = $query->createParameterColumn($table, $parent_id) . " = " . $query->createParameterColumn($join_table, $join_id);
    $query->join($table, $condition);
  }

  public function generateQueryForManyRelation ($relation) {
    $joins = array();
    $meta = $this->getMeta();

    $query = new Query();
    $root = $this->getOrCreateTable($relation);
    $query->from($root);

    $current = $relation;
    while ($parent = $current->getParent()) {
      if ($current->getParent()) {
        $this->addUpJoins($current, $query);
      }
      $current = $current->getParent();
    }

    foreach ($relation->getChildren() as $field) {
      if (!$field instanceof Relation) {
        /*$sql_field = new \fitch\sql\Column();
        $sql_field->setName($field->getName());
        $sql_field->setAlias($field->getAlias());
        $sql_field->setTable($this->getOrCreateTable($field));*/
        $query->addField($this->getTableAliasFor($field) . "." . $field->getName());
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
            $this->addJoins($field);
          } else {
            $table = NULL;
            if (!$this->queries[$field->getName()]) {
              $table = $this->generateQueryForManyRelation($field);
            }
            $table->setName($field->getName());
            $table->setAlias($this->getTableAliasFor($table));
            $this->queries[$field->getName()] = $table;
            //$query->join($table, "abc.a = rec.b", "INNER");
          }
        }

        $children = $field->getChildren();
        foreach ($children as $child) {
          $fields[] = $child;
        }
      } else if ($field->getParent() == $relation || $field->getParent() instanceof OneRelation) {
        $sql_field = new \fitch\sql\Column();
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
        $sql_field = new \fitch\sql\Column();
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
    $this->queries[$root->getName()] = $this->generateQueryForRelation($root);
    foreach ($this->queries as $query) {
      echo $query->getSql() . "\n";
    }
    exit;
    return $this->queries;
  }

}

?>