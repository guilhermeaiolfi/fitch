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

class NestedQueryGenerator extends QueryGenerator {

  protected $cache = array();
  protected $alias_manager = NULL;

  public function addJoins($parent, $relation, $query, $direction = "down") {
    $meta = $this->getMeta();
    $connections = $meta->getRelationConnections($parent->getName(), $relation->getName());
    $join_table = new \fitch\sql\Table();
    $joins = array();
    $keys = array_keys($connections);
    if ($relation instanceof \fitch\fields\ManyRelation) {
      $join = new \fitch\sql\JoinOne();

      list($parent_table_name, $parent_id) = explode(".", $keys[0]);
      list($join_table_name, $join_id) = explode(".", $connections[$keys[0]]);

      if (count($keys) == 1) {
        $join_table = $this->generateQueryForRelation($relation);

        $join_table->setName($relation->getTable());
        $join_table->setAlias($this->alias_manager->getTableAliasFor($join_table));

        foreach ($join_table->getColumns() as $column) {
          //$primary_key = $this->getMeta()->getPrimaryKey($parent->getTable());
          //echo $parent->getName() . "(" . $this->alias_manager->getTableAliasFor($join_table) . ") - " . $column->getTable()->getAlias() . "." . $column->getName() . "(" . $column->getAlias() . ") - " . $primary_key[0] . "\n";
          $query_column = clone $column;
          //echo $column->getTable()->getAlias() . "_" . $column->getName();exit;
          $query_column->setTable($join_table);
          if ($column->getAlias()) {
            $query_column->setName($column->getAlias());
          }
          $query_column->setAlias($join_table->getAlias() . "_" . ($column->getAlias()? $column->getAlias() : $column->getName()));
          $query->addColumn($query_column);
        }
      } else {
        $join_table->setName($join_table_name);
        $join_table->setAlias($this->alias_manager->getPivotAlias($join_table_name));
      }

      // TODO: do for ONE-TO-MANY what was done to ONE-TO-ONE and keep the behavior for MANY-TO-MANY
      $join->from($join_table);

      $parent_table = $this->alias_manager->getTableFromRelation($parent);

      $join->setCondition($query->createParameterColumn($join_table, $join_id) . " = " . $query->createParameterColumn($parent_table, $parent_id));

      $query->join($join);

      /* SECOND PART */
      if (count($keys) == 2) {
        $join = new \fitch\sql\JoinOne();
        list($join_table_name, $parent_id) = explode(".", $keys[1]);
        list($relation_table_name, $relation_id) = explode(".", $connections[$keys[1]]);

        //$relation_table = $this->alias_manager->getTableFromRelation($relation);
        $relation_table = $this->generateQueryForRelation($relation);

        $relation_table->setName($relation->getTable());
        $relation_table->setAlias($this->alias_manager->getTableAliasFor($relation_table));

        foreach ($relation_table->getColumns() as $column) {
          //$primary_key = $this->getMeta()->getPrimaryKey($parent->getTable());
          //echo $parent->getName() . "(" . $this->alias_manager->getTableAliasFor($relation_table) . ") - " . $column->getTable()->getAlias() . "." . $column->getName() . "(" . $column->getAlias() . ") - " . $primary_key[0] . "\n";
          $query_column = clone $column;
          //echo $column->getTable()->getAlias() . "_" . $column->getName();exit;
          $query_column->setTable($relation_table);
          if ($column->getAlias()) {
            $query_column->setName($column->getAlias());
          }
          $query_column->setAlias($relation_table->getAlias() . "_" . ($column->getAlias()? $column->getAlias() : $column->getName()));
          $query->addColumn($query_column);
        }

        // update the registry to ref the JOIN and not the table
        $this->alias_manager->setTableForRelation($relation, $relation_table);

        $join->from($relation_table);

        $condition = $query->createParameterColumn($relation_table, $relation_id) . " = " . $query->createParameterColumn($join_table, $parent_id);

        $join->setCondition($condition);
        $query->join($join);
      }
    } else if ($relation instanceof \fitch\fields\OneRelation) {

      $join = new \fitch\sql\JoinOne();

      list($parent_table_name, $parent_id) = explode(".", $keys[0]);
      list($join_table_name, $join_id) = explode(".", $connections[$keys[0]]);

      $join_table = $this->generateQueryForRelation($relation);

      $join_table->setName($relation->getTable());
      $join_table->setAlias($this->alias_manager->getTableAliasFor($join_table));

      foreach ($join_table->getColumns() as $column) {
        $query_column = clone $column;
        $query_column->setTable($join_table);
        if ($column->getAlias()) {
          $query_column->setName($column->getAlias());
        }
        $query_column->setAlias($join_table->getAlias() . "_" . ($column->getAlias()? $column->getAlias() : $column->getName()));
        $query->addColumn($query_column);
      }

      // update the registry to ref the JOIN and not the table
      $this->alias_manager->setTableForRelation($relation, $join_table);

      $parent_table = $this->alias_manager->getTableFromRelation($parent);

      $condition = $query->createParameterColumn($join_table, $join_id) . " = " . $query->createParameterColumn($parent_table, $parent_id);

      $join->from($join_table);
      $join->setCondition($condition);
      $query->join($join);
    }
  }

  public function buildRest($relation, $query) {
    $conditions = $this->replaceFieldWithFieldsSql($relation->getConditions());
    $query->setConditions($conditions);
    $function = $relation->getFunction("sort");
    for($i = 0; $i < count($function); $i++) {
      $field = $function[$i]["field"];
      $sql_field = new \fitch\sql\Column();
      $sql_field->setName($field->getName());
      $sql_field->setAlias($field->getAlias());
      $sql_field->setTable($this->alias_manager->getTableFromRelation($field->getParent()));
      $function[$i]["column"] = $sql_field;
      unset($function[$i]["field"]);
      $query->addSortBy($function[$i]["column"], $function[$i]["direction"]);
    }
    if ($function = $relation->getFunction("limit")) {
      $query->limit($function["limit"], $function["offset"]);
    }
  }

  public function generateQueryForRelation($relation) {
    $joins = array();
    $meta = $this->getMeta();

    $query = new Query();
    $query->from($this->alias_manager->getTableFromRelation($relation));
    //$fields[] = $relation;

    $fields = $relation->getChildren();
    //array_unshift($fields, $relation);
    while ($field = array_shift($fields)) {
      if ($field instanceof Relation) {
        if ($field->getName() != $relation->getName()) {
          //echo $relation->getName();
          $this->addJoins($relation, $field, $query);
        }
      } else {
        $table = $this->alias_manager->getTableFromRelation($field->getParent());
        $column = new Column();
        $column->setTable($table);
        $column->setName($field->getName());
        $query->addColumn($column);
      }
    }

    $this->buildRest($relation, $query);
    return $query;
  }


  public function getQueries() {
    $this->alias_manager = new \fitch\sql\AliasManager();
    $root = $this->getRoot();
    $queries = array();
    $queries[] = $this->generateQueryForRelation($root);
    return $queries;
  }
}

?>