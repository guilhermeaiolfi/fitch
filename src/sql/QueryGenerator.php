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

  protected $cache = array();

  public function addJoins($relation, $query) {
    $meta = $this->getMeta();
    $connections = $meta->getRelationConnections($relation->getParent()->getName(), $relation->getName());
    $join_table = new \fitch\sql\Table();

    $keys = array_keys($connections);
    if ($relation instanceof \fitch\fields\ManyRelation) {
      $join = new \fitch\sql\JoinOne();

      $join->from($join_table);

      list($parent_table_name, $parent_id) = explode(".", $keys[0]);
      list($join_table_name, $join_id) = explode(".", $connections[$keys[0]]);

      $join_table->setName($join_table_name);
      $join_table->setAlias($this->getTableAliasFor($join));

      $parent_table = $this->getOrCreateTable($relation->getParent());

      $join->setCondition($query->createParameterColumn($join_table, $join_id) . " = " . $query->createParameterColumn($parent_table, $parent_id));

      $query->join($join);

      /* SECOND PART */
      list($join_table_name, $parent_id) = explode(".", $keys[1]);
      list($relation_table_name, $relation_id) = explode(".", $connections[$keys[1]]);

      $relation_table = $this->getOrCreateTable($relation);

      $condition = $query->createParameterColumn($relation_table, $relation_id) . " = " . $query->createParameterColumn($join_table, $parent_id);

      $query->join($relation_table, $condition);
    } else if ($relation instanceof \fitch\fields\OneRelation) {

      list($parent_table_name, $parent_id) = explode(".", $keys[0]);
      list($join_table_name, $join_id) = explode(".", $connections[$keys[0]]);

      $join_table = $this->getOrCreateTable($relation);

      $parent_table = $this->getOrCreateTable($relation->getParent());

      $condition = $query->createParameterColumn($join_table, $join_id) . " = " . $query->createParameterColumn($parent_table, $parent_id);

      $query->join($join_table, $condition);
    }
  }

  public function getOrCreateTable($field) {
    $id = spl_object_hash($field);
    if ($this->cache[$id]) {
      return $this->cache[$id];
    }
    if ($field instanceof \fitch\fields\Relation) {
      $table = new \fitch\sql\Table();
      $table->setName($field->getTable());
      $table->setAlias($this->getTableAliasFor($field));
      return $this->cache[$id] = $table;
    } else if ($field instanceof \fitch\fields\Field) {
      $table = new \fitch\sql\Table();
      $table->setName($field->getParent()->getTable());
      $table->setAlias($this->getTableAliasFor($field->getParent()));
      return $this->cache[$id] = $table;
    }
    return NULL;
  }

  public function generateQueryForRelation($relation) {
    $joins = array();
    $meta = $this->getMeta();

    $query = new Query();
    $query->from($this->getOrCreateTable($relation));
    $fields[] = $relation;

    while ($field = array_shift($fields)) {
      if ($field instanceof Relation) {
        if ($field->getParent() && $field != $relation) {
          $this->addJoins($field, $query);
        }

        $children = $field->getChildren();
        foreach ($children as $child) {
          $fields[] = $child;
        }
      } else {
        $query->addField($this->getTableAliasFor($field) . "." . $field->getName());
      }
    }
    if ($relation instanceof Segment) {
      $conditions = $this->replaceFieldWithFieldsSql($relation->getConditions());
      $query->setConditions($conditions);
      $function = $relation->getFunction("sort");
      for($i = 0; $i < count($function); $i++) {
        $field = $function[$i]["field"];
        /*$sql_field = new \fitch\sql\Column();
        $sql_field->setName($field->getName());
        $sql_field->setAlias($field->getAlias());
        $sql_field->setTable($this->getOrCreateTable($field->getParent()));*/
        $function[$i]["field"] = $this->getTableAliasFor($field->getParent()) . "." . $field->getName();
        $query->addSortBy($function[$i]["field"], $function[$i]["direction"]);
      }
      if ($function = $relation->getFunction("limit")) {
        $query->limit($function["limit"], $function["offset"]);
      }
    }

    return $query;
  }

  public function replaceFieldWithFieldsSql($condition) {
    if (is_array($condition)) {
      if (isset($condition["field"])) { //condition
        $field = new \fitch\sql\Column();
        $field->setName($condition["field"]->getName());
        $field->setAlias($condition["field"]->getAlias());
        $field->setTable($this->getOrCreateTable($condition["field"]->getParent()));
        $condition["field"] = $field;
        return $condition;
      } else { // parenthesis
        $parenthesis = array();
         foreach ($condition as $item) {
          $parenthesis[] = $this->replaceFieldWithFieldsSql($item);
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

  function getTableAliasFor($node) {
    $table = "";
    if ($node instanceof Relation) {
      $table = $node->getName();
    } else if ($node instanceof \fitch\sql\Table) {
      $table = $node->getName();
    } else if ($node instanceof Field) {
      $table = $node->getParent()->getName();
      $node = $node->getParent();
    } else if ($node instanceof \fitch\sql\Join) {
      $table = $node->getTable()->getName();
      return $this->registerAliasFor($table, $node);
    }
    $alias = $this->registerAliasFor($table, $node);
    return $alias;
  }

  public function registerAliasFor($table, $node) {
    $n = 0;
    while (isset($this->aliases[$table . "_" . $n])) {
      if ($this->aliases[$table . "_" . $n] == $node) {
        return $table . "_" . $n;
      }
      $n++;
    }
    $this->aliases[$table . "_" . $n] = $node;
    return $table . "_" . $n;
  }
}

?>