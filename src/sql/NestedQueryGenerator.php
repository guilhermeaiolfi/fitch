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

  protected function createJoin($query, $relation, $parent, $join_id, $parent_id, $parent_table = NULL) {
    $join = new \fitch\sql\JoinOne();

    $join_table = NULL;
    if (is_string($relation)) { // pivot table
      $join_table = new \fitch\sql\Table();
      $join_table->setName($relation);
      $join_table->setAlias($this->alias_manager->getPivotAlias($relation));
    } else { // an actual relation
      $join_table = $this->generateQueryForRelation($relation);
      $join_table->setName($relation->getTable());
      $join_table->setAlias($this->alias_manager->getTableAliasFor($join_table));

      $this->addColumns($join_table, $query);

      // update the registry to ref the JOIN and not the table
      $this->alias_manager->setTableForRelation($relation, $join_table);
    }

    $join->from($join_table);

    if ($parent_table == NULL) {
      $parent_table = $this->alias_manager->getTableFromRelation($parent);
    }

    $condition = $query->createParameterColumn($join_table, $join_id) . " = " . $query->createParameterColumn($parent_table, $parent_id);

    $join->setCondition($condition);

    return $join;
  }

  public function addColumns($from, $to) {
    foreach ($from->getColumns() as $column) {
      //$primary_key = $this->getMeta()->getPrimaryKey($parent->getTable());
      //echo $parent->getName() . "(" . $this->alias_manager->getTableAliasFor($join_table) . ") - " . $column->getTable()->getAlias() . "." . $column->getName() . "(" . $column->getAlias() . ") - " . $primary_key[0] . "\n";
      $aliased_column = clone $column;
      //echo $column->getTable()->getAlias() . "_" . $column->getName();exit;
      $aliased_column->setTable($from);
      if ($column->getAlias()) {
        $aliased_column->setName($column->getAlias());
      }
      $aliased_column->setAlias($from->getAlias() . "_" . ($column->getAlias()? $column->getAlias() : $column->getName()));
      $to->addColumn($aliased_column);
    }
  }

  public function addJoins($parent, $relation, $query, $direction = "down") {
    $meta = $this->getMeta();
    $connections = $meta->getRelationConnections($parent->getName(), $relation->getName());
    $join_table = new \fitch\sql\Table();
    $joins = array();
    $keys = array_keys($connections);
    if ($relation instanceof \fitch\fields\ManyRelation) {

      list($parent_table_name, $parent_id) = explode(".", $keys[0]);
      list($join_table_name, $join_id) = explode(".", $connections[$keys[0]]);

      $join = $this->createJoin($query, count($keys) == 1? $relation : $join_table_name, $parent, $join_id, $parent_id);
      $join_table = $join->getTable();

      $query->join($join);

      /* SECOND PART */
      if (count($keys) == 2) {
        list($parent_table_name, $parent_id) = explode(".", $keys[1]);
        list($join_table_name, $join_id) = explode(".", $connections[$keys[1]]);

        $join = $this->createJoin($query, $relation, $parent, $join_id, $parent_id, $join_table);

        $query->join($join);
      }
    } else if ($relation instanceof \fitch\fields\OneRelation) {

      list($parent_table_name, $parent_id) = explode(".", $keys[0]);
      list($join_table_name, $join_id) = explode(".", $connections[$keys[0]]);

      $join = $this->createJoin($query, $relation, $parent, $join_id, $parent_id);

      $query->join($join);
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