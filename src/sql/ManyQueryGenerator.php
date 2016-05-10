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

  protected $alias_manager = NULL;
  protected $cache = array();

  protected $queries = array();


  public function addUpJoins($relation, $query) {
    $meta = $this->getMeta();
    $connections = $meta->getRelationConnections($relation->getName(), $relation->getParent()->getName());

    $keys = array_keys($connections);


    $join = new \fitch\sql\JoinOne();

    list($parent_table_name, $parent_id) = explode(".", $keys[0]);
    list($join_table_name, $join_id) = explode(".", $connections[$keys[0]]);

    $join_table = new \fitch\sql\Table();
    $join_table->setName($join_table_name);
    $join_table->setAlias($this->alias_manager->getPivotAlias($join_table_name));

    $join->from($join_table);

    $parent_table = $this->alias_manager->getTableFromRelation($relation);

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
    $table->setAlias($this->alias_manager->getTableAliasFor($table));
    $this->queries[$parent->getName()] = $table;
    $condition = $query->createParameterColumn($table, $parent_id) . " = " . $query->createParameterColumn($join_table, $join_id);
    $query->join($table, $condition);
  }

  public function generateQueryForManyRelation ($relation) {
    $joins = array();
    $meta = $this->getMeta();

    $query = new Query();
    $root = $this->alias_manager->getTableFromRelation($relation);
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
        $column = new \fitch\sql\Column();
        $column->setTable($this->alias_manager->getTableFromRelation($field->getParent()));
        $column->setName($field->getName());
        $query->addField($column);
      }
    }

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

    return $query;
  }

  public function generateQueryForRelation($relation) {
    $this->alias_manager = new \fitch\sql\AliasManager();
    $joins = array();
    $meta = $this->getMeta();

    $query = new Query();
    $query->from($this->alias_manager->getTableFromRelation($relation));
    $fields[] = $relation;

    while ($field = array_shift($fields)) {
      if ($field instanceof Relation) {
        if ($field->getParent() && $field != $relation) {
          if ($field instanceof OneRelation) {
            $this->addJoins($field->getParent(), $field, $query);
          } else {
            $table = NULL;
            if (!$this->queries[$field->getName()]) {
              $table = $this->generateQueryForManyRelation($field);
            }
            $table->setName($field->getName());
            $table->setAlias($this->alias_manager->getTableAliasFor($table));
            $this->queries[$field->getName()] = $table;
          }
        }

        $children = $field->getChildren();
        foreach ($children as $child) {
          $fields[] = $child;
        }
      } else if ($field->getParent() == $relation || $field->getParent() instanceof OneRelation) {
        $table = $this->alias_manager->getTableFromRelation($field->getParent());
        $column = new Column();
        $column->setTable($table);
        $column->setName($field->getName());
        $query->addField($column);
      }
    }
    $conditions = $this->replaceFieldWithFieldsSql($relation->getConditions());
    $query->setConditions($conditions);
    $function = $relation->getFunction("sort");
    for($i = 0; $i < count($function); $i++) {
      $field = $function[$i]["field"];
      $column = new \fitch\sql\Column();
      $column->setName($field->getName());
      $column->setTable($this->alias_manager->getTableFromRelation($field->getParent()));
      $function[$i]["field"] = $column;
      $query->addSortBy($function[$i]["field"], $function[$i]["direction"]);
    }
    if ($function = $relation->getFunction("limit")) {
      $query->limit($function["limit"], $function["offset"]);
    }
    return $query;
  }

  public function getQueries() {
    $root = $this->getRoot();
    $this->queries[$root->getName()] = $this->generateQueryForRelation($root);
    return $this->queries;
  }

}

?>