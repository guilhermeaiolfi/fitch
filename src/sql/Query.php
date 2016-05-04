<?php

namespace fitch\sql;

use \fitch\Fields\Field as Field;
use \fitch\Fields\Relation as Relation;
use \fitch\Fields\Segment as Segment;

class Query {
  protected $joins = array();
  protected $fields = array();
  protected $sort_by = array();
  protected $aliases = array();
  protected $conditions = NULL;
  protected $limit = NULL;

  public function setConditions($conditions) {
    $this->conditions = $conditions;
  }

  public function getRoot() {
    return $this->root;
  }

  public function setRoot($root) {
    return $this->root = $root;
  }

  public function addJoin($join) {
    $this->joins[] = $join;
  }

  public function addField($field) {
    $this->fields[] = $field;
  }

  public function setFields($fields) {
    $this->fields = $fields;
  }

  public function getFields() {
    return $this->fields;
  }

  public function getJoins() {
    return $this->joins;
  }

  public function getTable() {
    $parts = $this->getRoot()->getParts();
    if ($this->getRoot()->hasDot()) {
      return $parts[0];
    }
    return $this->getRoot()->getName();
  }

  public function getJoinSql($join, $meta) {
    $meta = $meta->getRelationConnections($join->getName());
    if (count($meta) == 2) {
      return $this->getManyToManyJoin($join, $meta);
    }
    return $this->getOneToManyJoin($join, $meta);
  }

  public function getManyToManyJoin($join, $meta) {

    $joins = array();
    $first = true;

    $aliases = $this->getAliasFor($join);

    foreach ($meta as $left => $right) {
      list($left_table, $left_field) = explode(".", $left);
      list($right_table, $right_field) = explode(".", $right);

      if ($first) {
        $first_left_relation = $this->getRelationByRelationName($left_table);
        $first_left_relation = $first_left_relation? $first_left_relation : $this->root;
        $joins[] = " " . $join->getType() . " JOIN $right_table " . $aliases[0] . " ON (" . $aliases[0] . "." . $right_field . " = " . $this->getAliasFor($first_left_relation) . "." . $left_field . ")";
        $first = false;
      } else {
        $alias = $this->getAliasFor($join);
        $joins[] = " " . $join->getType() . " JOIN $right_table " . $aliases[1] . " ON (" . $aliases[1] . "." . $right_field . " = " . $aliases[0] . "." . $left_field . ")";
      }

    }
    return implode(" ", $joins);
  }

  public function getOneToManyJoin($join, $meta) {

    list($left, $right) = each($meta);
    list($left_table, $left_field) = explode(".", $right);
    list($right_table, $right_field) = explode(".", $left);

    $table = $left_table;
    $aliases = $this->getAliasFor($join);

    $left_relation = $join->getRelation();
    $left_relation = $left_relation->getParent()? $left_relation->getParent() : $this->root;

    return " " . $join->getType() . " JOIN $left_table " . $aliases[1] . " ON (" . $this->getAliasFor($left_relation) . "." . $right_field . " = " . $aliases[1] . "." . $left_field . ")";
  }

  public function getRelationByRelationName($relation_name) {
    $relations = $this->getRoot()->getListOf("\\fitch\\fields\\Relation");
    foreach ($relations as $relation) {
      if ($relation->getName() == $relation_name || $relation->getAlias() == $relation_name) {
        return $relation;
      }
    }
    return NULL;
  }

  public function getConditionSql($column, $operator, $value) {
    $alias = NULL;
    if (strpos($column, ".") === false) {
      $alias = $this->getAliasFor($this->root);
    } else {
      $parts = explode(".", $column);
      $alias = $this->getAliasFor($this->getRelationByRelationName($parts[0]));

      if (!$alias) {
        throw new Exception("Alias not found for " . $parts[0], 1);
      }
      $column = $parts[1];
    }
    if ($operator == "~") {
      return $alias . "." . $column . " LIKE " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
    }
    if ($operator == "!=") {
      return $alias . "." . $column . " <> " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
    }
    if ($operator == "~") {
      return $alias . "." . $column . " LIKE " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
    }
    return $alias . "." . $column . " " . $operator . " " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
  }

  public function getSql($meta) {
    $sql = "SELECT ";

    $alias = $this->getAliasFor($this->getRoot());

    $fields = $this->getFields();
    $select_fields = array();
    for ($i = 0; $i < count($fields); $i++) {
      $field = $fields[$i];
      $alias = $this->getAliasFor($field);
      $select_fields[] = $alias . "." . $field->getName();
    }

    $sql .= implode(", ", $select_fields);
    $sql .= " FROM " . $this->getTable() . " AS " . $this->getAliasFor($this->getRoot());

    foreach ($this->getJoins() as $join) {
      $join_sql = $this->getJoinSql($join, $meta);
      $sql .= $join_sql;
    }

    $where = "";

    if (is_array($this->conditions)) {
      $where .= " WHERE ";
      foreach ($this->conditions as $condition) {
        if (is_array($condition)) {
          if (isset($condition["left"]) && isset($condition["right"])) { //condition
            $where .= $this->getConditionSql($condition["left"], $condition["operator"], $condition["right"]);
          } else { // parenthesis
            $where .= "(";
            $block = $condition;
            foreach ($block as $subcondition) {
              if (is_string($subcondition)) {
                if ($condition == '&') {
                  $where .= " AND ";
                } else {
                  $where .= " OR ";
                }
              } else {
                $where .= $this->getConditionSql($subcondition["left"], $subcondition["operator"], $subcondition["right"]);
              }
            }
            $where .= ")";
          }
        } else { // SQL's 'AND' or 'OR'
          if ($condition == '&') {
            $where .= " AND ";
          } else {
            $where .= " OR ";
          }
        }
      }
    }

    $sql .= $where;

    $sort_by = $this->sort_by;
    $sort_by_count = count($sort_by);
    if ($sort_by_count > 0) {
      $sql .= " SORT BY ";
    }
    for ($i = 0; $i < count($sort_by); $i++) {
      $sql .= $i? ", " : "";
      $sql .= $sort_by[$i]["column"] . " " . ($sort_by[$i]["direction"] == "+"? "ASC" : "DESC");
    }

    if (is_array($this->limit)) {
      $sql .= " LIMIT " . join(",", $this->limit);
    }

    return $sql;
  }

  function addSortBy($sort) {
    $this->sort_by[] = $sort;
  }

  function limit($limit, $offset) {
    $this->limit = array($limit, $offset);
  }

  function getAliasFor($node) {
    $table = "";
    if ($node instanceof Segment) {
      if ($node->hasDot()) {
        $parts = $node->getParts();
        $table = $parts[0];
      } else {
        $table = $node->getName();
      }
    } else if ($node instanceof Relation) {
      $table = $node->getName();
    } else if ($node instanceof Field) {
      $table = $node->getParent()->getName();
      $node = $node->getParent();
    } else if ($node instanceof \fitch\Join) {
      $relation = $node->getRelation();
      $table = ($relation->getParent()? $relation->getParent()->getName() : $relation->getName()) . "_" . $node->getTable();
      $aliases = array();
      $aliases[] = $this->registerAliasFor($table, $relation);

      $table = $node->getTable();

      $aliases[] = $this->registerAliasFor($table, $relation);
      return $aliases;
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