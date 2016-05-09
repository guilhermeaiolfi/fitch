<?php

namespace fitch\sql;

use \fitch\Generator as Generator;
use \fitch\fields\Field as Field;
use \fitch\fields\Relation as Relation;
use \fitch\fields\OneRelation as OneRelation;
use \fitch\fields\ManyRelation as ManyRelation;
use \fitch\fields\Segment as Segment;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class QueryGenerator extends Generator {

  protected $cache = array();

  public function createJoins($relation) {
    $meta = $this->getMeta();
    $connections = $meta->getRelationConnections($relation->getParent()->getName(), $relation->getName());
    $joins = array();
    $join_table = new \fitch\sql\Table();

    if ($relation instanceof \fitch\fields\ManyRelation) {
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

          $relation_table = $this->getOrCreateTable($relation);

          $join = new \fitch\sql\JoinOne();

          $join->setJoinTable($relation_table);
          $join->setParentTable($join_table);

          $join->setJoinField($relation_field);
          $join->setParentField($parent_field);

          $joins[] = $join;
        }
      }
    } else if ($relation instanceof \fitch\fields\OneRelation) {

      list($left, $right) = each($connections);
      list($parent_table_name, $parent_field) = explode(".", $right);
      list($join_table_name, $join_field) = explode(".", $left);

      $parent_alias = $this->getTableAliasFor($relation->getParent());
      $join_alias = $this->getTableAliasFor($relation);

      $join_table = $this->getOrCreateTable($relation);

      $parent_table = $this->getOrCreateTable($relation->getParent());

      $join = new \fitch\sql\JoinOne();
      $join->setParentTable($parent_table);
      $join->setJoinTable($join_table);

      $join->setParentField($join_field);
      $join->setJoinField($parent_field);

      $joins[] = $join;
    }
    return $joins;
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
    $root = new \fitch\sql\Table();
    $root->setName($relation->getName());
    $root->setAlias($this->getTableAliasFor($relation));
    $query->setRoot($root);
    $fields[] = $relation;

    while ($field = array_shift($fields)) {
      if ($field instanceof Relation) {
        if ($field->getParent() && $field != $relation) {
          $joins = $this->createJoins($field);
          foreach ($joins as $join) {
            $query->addJoin($join);
          }
        }
        
        $children = $field->getChildren();
        foreach ($children as $child) {
          $fields[] = $child;
        }
      } else {
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

  public function replaceFieldWithFieldsSql($condition) {
    if (is_array($condition)) {
      if (isset($condition["field"])) { //condition
        $field = new \fitch\sql\Field();
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
    } else if ($node instanceof Field) {
      $table = $node->getParent()->getName();
      $node = $node->getParent();
    } else if ($node instanceof \fitch\sql\Join) {
      $table = $node->getJoinTable()->getName();
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