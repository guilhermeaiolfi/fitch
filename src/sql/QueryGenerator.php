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

class QueryGenerator extends Generator {

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

      $join_table->setName($join_table_name);
      if (count($keys) == 1) {
        $join_table->setAlias($this->alias_manager->getTableAliasFor($relation));
      } else {
        $join_table->setAlias($this->alias_manager->getPivotAlias($join_table_name));
      }

      $join->from($join_table);

      $parent_table = $this->alias_manager->getTableFromRelation($parent);

      $join->setCondition($query->createParameterColumn($join_table, $join_id) . " = " . $query->createParameterColumn($parent_table, $parent_id));

      $query->join($join);

      /* SECOND PART */
      if (count($keys) == 2) {
        $join = new \fitch\sql\JoinOne();
        list($join_table_name, $parent_id) = explode(".", $keys[1]);
        list($relation_table_name, $relation_id) = explode(".", $connections[$keys[1]]);

        $relation_table = $this->alias_manager->getTableFromRelation($relation);

        $join->from($relation_table);

        $condition = $query->createParameterColumn($relation_table, $relation_id) . " = " . $query->createParameterColumn($join_table, $parent_id);

        $join->setCondition($condition);
        $query->join($join);
      }
    } else if ($relation instanceof \fitch\fields\OneRelation) {

      $join = new \fitch\sql\JoinOne();

      list($parent_table_name, $parent_id) = explode(".", $keys[0]);
      list($join_table_name, $join_id) = explode(".", $connections[$keys[0]]);

      $join_table = $this->alias_manager->getTableFromRelation($relation);

      $parent_table = $this->alias_manager->getTableFromRelation($parent);

      $condition = $query->createParameterColumn($join_table, $join_id) . " = " . $query->createParameterColumn($parent_table, $parent_id);

      $join->from($join_table);
      $join->setCondition($condition);
      $query->join($join);
    }
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
          $this->addJoins($field->getParent(), $field, $query);
        }
        //$this->cache($field->getName(), $from);

        $children = $field->getChildren();
        foreach ($children as $child) {
          $fields[] = $child;
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


    // it can be:
    // /relation?field=value OR
    // /relation{other_rel}?other_rel.field=value
    // /relation{other_rel :as rel}?rel.field=value
  public function createColumnFor($field, $relation) {
    $parts = explode(".", $field);
    $owner = NULL;

    $column = new \fitch\sql\Column();
    if (count($parts) > 1) {
      $column->setName($parts[count($parts) - 1]);
      $owner = $relation->getRelationByName($parts[0]);
    } else {
      $owner = $relation;
      $column->setName($field);
    }
    $column->setTable($this->alias_manager->getTableFromRelation($owner));
    return $column;
  }

  public function buildRest($relation, $query) {
    $conditions = $this->replaceFieldWithCOlumns($relation->getConditions(), $relation);
    $query->setConditions($conditions);
    $function = $relation->getFunction("sort");
    for($i = 0; $i < count($function); $i++) {
      $function[$i]["column"] = $this->createColumnFor($function[$i]["field"], $relation);
      unset($function[$i]["field"]);
      $query->addSortBy($function[$i]["column"], $function[$i]["direction"] == "+"? "ASC" : "DESC");
    }
    if ($function = $relation->getFunction("limit")) {
      $query->limit($function["limit"], $function["offset"]);
    }
  }

  public function replaceFieldWithColumns($condition, $root) {
    if (is_array($condition)) {
      if (isset($condition["field"])) { //condition
        $condition["column"] = $this->createColumnFor($condition["field"], $root);
        unset($condition["field"]);
        return $condition;
      } else { // parenthesis
        $parenthesis = array();
         foreach ($condition as $item) {
          $parenthesis[] = $this->replaceFieldWithColumns($item, $root);
        }
        return $parenthesis;
      }
    } else { // SQL's 'AND' or 'OR'
      return $condition;
    }
    return NULL;
  }

  public function getQueries() {
    $root = $this->getRoot();
    $queries = array();
    $queries[] = $this->generateQueryForRelation($root);
    return $queries;
  }
}

?>