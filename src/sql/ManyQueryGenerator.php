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

    //echo $relation->getName() . " = " . $relation->getParent()->getName();
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

    $parent = $relation->getParent();
    $table = $this->queries[$parent->getName()];
    if (!$table) {
      //echo $relation->getName();
      //echo get_class($this->generateQueryForManyRelation($parent));
      $table = $this->generateQueryForManyRelation($parent);
    }

    $table->setName($parent->getName());
    $table->setAlias($this->alias_manager->getTableAliasFor($table));

    foreach ($table->getColumns() as $column) {
      $primary_key = $this->getMeta()->getPrimaryKey($parent->getTable());
      //echo $parent->getName() . "(" . $this->alias_manager->getTableAliasFor($table) . ") - " . $column->getTable()->getAlias() . "." . $column->getName() . "(" . $column->getAlias() . ") - " . $primary_key[0] . "\n";
      if ($column->getName() == $primary_key[0] || $column->isPrimaryKey()) {
        $query_column = clone $column;
        //echo $column->getTable()->getAlias() . "_" . $column->getName();exit;
        $query_column->setTable($table);
        //$query_column->setPrimaryKey(true);
        if ($column->getAlias()) {
          $query_column->setName($column->getAlias());
        }
        $query_column->setAlias($table->getAlias() . "_" . ($column->getAlias()? $column->getAlias() : $column->getName()));
        $query->addColumn($query_column);
      } else {
        $table->removeColumn($column);
      }
    }

    $this->queries[$parent->getName()] = $table;
    $condition = $query->createParameterColumn($table, $parent_id) . " = " . $query->createParameterColumn($join_table, $join_id);
    $query->join($table, $condition);
  }

  public function generateQueryForManyRelation ($relation) {
    $meta = $this->getMeta();

    $query = new Query();
    $root = $this->alias_manager->getTableFromRelation($relation);
    $query->from($root);

    $current = $relation;
    if ($current->getParent()) {
      $this->addUpJoins($current, $query);
    }

    foreach ($relation->getChildren() as $field) {
      if (!$field instanceof Relation) {
        $column = new \fitch\sql\Column();
        $column->setTable($this->alias_manager->getTableFromRelation($field->getParent()));
        $column->setName($field->getName());
        $query->addColumn($column);
      }
    }

    $this->buildRest($relation, $query);
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
        $has_fields = false;
        foreach ($children as $child) {
          if (!($child instanceof ManyRelation) && !($child instanceof PrimaryKeyHash)) {
            $has_fields = true;
          }
          $fields[] = $child;
        }

        if (!$has_fields) {
          $this->unset[] = $field->getName();
        }
      } else if ($field->getParent() == $relation || $field->getParent() instanceof OneRelation) {
      //TODO: one relation can be from a different branch than the one being constructed here
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
    $this->unset = array();
    $root = $this->getRoot();
    $this->queries[$root->getName()] = $this->generateQueryForRelation($root);
    foreach ($this->unset as $key) {
      unset($this->queries[$key]);
    }
    return $this->queries;
  }
}

?>