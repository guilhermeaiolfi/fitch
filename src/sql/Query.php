<?php

namespace fitch\sql;

use \fitch\sql\Column as Column;
use \fitch\sql\Join as Join;
use \fitch\sql\JoinOne as JoinOne;
use \fitch\sql\Table as Table;


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

  /*
    join(Table $table, string CONDITION);
    join(Join $join)
    join(string $from, string $condition)
    join(array $from, string $condition)
  */
  public function join() {
    $args = func_get_args();
    $join = null;
    if ($args[0] instanceof Join) {
      $this->addJoin($args[0]);
      return $this;
    } elseif ($args[0] instanceof Table) {
      $join = new JoinOne();
      $join->from($args[0]);
      $condition = $args[1];
    } elseif (is_string($args[0])) {
      $join = new JoinOne();
      $table = new Table();
      $table->from($args[0]);
    } else {
      $join = new JoinOne();
      $join->from($args[0][0], $args[0][1]);
    }
    $condition = $args[1];
    $join->setCondition($condition);
    $this->addJoin($join);
    return $this;
  }

  /*
   createParameterColumn(Column $column)
   createParameterColumn(Table $table, $column_name)
   createParameterColumn(array(table_name => alias), $column_name)
   createParameterColumn(array(table_name => alias), array(column_name => column_alias))
  */
  public function createParameterColumn() {
    $args = func_get_args();
    if ($args[0] instanceof Column) {
      return $args[0]->getTable()->getName() . "." . $args[0]->getName();
    } elseif ($args[0] instanceof Table) {
      return $args[0]->getAlias() . "." . $args[1];
    } elseif (is_string($args[0])) {
      return $args[0] . '.' . $args[1];
    }
    $table_keys = array_keys($args[0]);
    if (is_array($args[1])) {
      return $args[0][$table_keys[0]] . "." . $args[1][$column_keys[0]];
    } else {
      return $args[0][$table_keys[0]] . "." . $args[1];
    }
  }

  public function from($root, $alias = NULL) {
    if (is_string($root)) {
      $table = new Table();
      $table->setName($root);
      $table->setAlias($alias);
      $this->root = $table;
    } else {
      $this->root = $root;
    }
    return $this;
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
    return $this->getRoot()->getName();
  }

  public function getJoinSql($join, $meta) {
    //print_r($join->getRelation()->getName());exit;
    $relation = $join->getRelation();
    $parent = $relation->getParent();

    $meta = $meta->getRelationConnections($parent->getName(), $relation->getName());

    if (count($meta) == 2) {
      return $this->getManyToManyJoin($join, $meta);
    }
    return $this->getOneToManyJoin($join, $meta);
  }

  public function getConditionSql($condition, $root = true) {
    if (is_array($condition)) {
      if (isset($condition["field"]) && isset($condition["value"])) { //condition
        $field = $condition["field"];
        $operator = $condition["operator"];
        $value = $condition["value"];
        if ($operator == "~") {
          return $field->getTable()->getAlias() . "." . $field->getName() . " LIKE " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
        }
        if ($operator == "!=") {
          return $field->getTable()->getAlias() . "." . $field->getName() . " <> " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
        }
        if ($operator == "~") {
          return $field->getTable()->getAlias() . "." . $field->getName() . " LIKE " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
        }
        return $field->getTable()->getAlias() . "." . $field->getName() . " " . $operator . " " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
      } else { // parenthesis
        $where = !$root? "(" : "";
        foreach ($condition as $item) {
          if (is_string($item)) {
            if ($item == '&') {
              $where .= " AND ";
            } else {
              $where .= " OR ";
            }
          } else {
            $where .= $this->getConditionSql($item, false);
          }
        }
        return $where .= !$root? ")" : "";
      }
    } else { // SQL's 'AND' or 'OR'
      if ($condition == '&') {
        return " AND ";
      } else {
        return " OR ";
      }
    }
    return NULL;
  }

  public function getSql($meta) {
    $sql = "SELECT ";

    $root_alias = $this->getRoot()->getAlias();

    $fields = $this->getFields();
    $select_fields = array();
    for ($i = 0; $i < count($fields); $i++) {
      $field = $fields[$i];
      $select_fields[] = $field->getSql();
    }

    $sql .= implode(", ", $select_fields);
    $sql .= " FROM " . $this->getTable() . " AS " . $root_alias;

    foreach ($this->getJoins() as $join) {
      $sql .= $join->getSql();
    }

    $where = "";

    if (is_array($this->conditions)) {
      $where .= " WHERE ";
      $where .= $this->getConditionSql($this->conditions);
    }

    $sql .= $where;

    $sort_by = $this->sort_by;
    $sort_by_count = count($sort_by);
    if ($sort_by_count > 0) {
      $sql .= " SORT BY ";
    }
    for ($i = 0; $i < count($sort_by); $i++) {
      $field = $sort_by[$i]["field"];
      $alias = $field->getTable()->getAlias();
      $sql .= $i? ", " : "";
      $sql .= $alias . "."  . $field->getName() . " " . ($sort_by[$i]["direction"] == "+"? "ASC" : "DESC");
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
}

?>