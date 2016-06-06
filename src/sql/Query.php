<?php

namespace fitch\sql;

use \fitch\sql\Column as Column;
use \fitch\sql\Join as Join;
use \fitch\sql\JoinOne as JoinOne;
use \fitch\sql\Table as Table;


class Query extends Table {
  protected $joins = array();
  protected $sort_by = array();
  protected $aliases = array();
  protected $conditions = NULL;
  protected $limit = NULL;
  protected $cache = array("table" => array());

  public function setConditions($conditions) {
    $this->conditions = $conditions;
  }

  public function getRoot() {
    return $this->root;
  }

  public function setRoot($root) {
    return $this->root = $root;
  }

  public function findTable($alias) {
    return $this->cache["table"][$alias];
    foreach ($this->cache["table"] as $table) {
      if ($alias == $table->getAlias()) {
        return $table;
      }
    }
    return NULL;
  }

  public function getOrCreateTable(){
    $args = func_get_args();
    if ($args[0] instanceof Table) {
      $table = $this->findTable($args[0]->getAlias());
      if (!$table) {
        $table = new Table();
        $table->setAlias($args[0]->getAlias());
      }
      if ($table->getName() && $table->getName() != $args[0]->getName()) {
        throw new Exception("Same alias but different names for: " . $table->getAlias(), 1);
      }
      $table->setName($args[0]->getName());
      return $table;
    } else if (is_string($args[0])) {
      $table = $this->findTable($args[0]);
      if (!$table) {
        $table = new Table();
        $table->setAlias($args[0]);
        $table->setName($args[0]);
        $this->cache("table", $args[0], $table);
      }
      return $table;
    } else if (is_array($args[0])) {
      $keys = array_keys($args[0]);
      $alias = $args[0][$keys[0]];
      $name = $keys[0];
      $table = $this->findTable($alias);
      if (!$table) {
        $table = new Table();
        $table->setAlias($alias);
        $table->setName($name);
        $this->cache("table", $alias, $table);
      }
      return $table;
    }
    return NULL;
  }

  /*
    join(Join $join)
    join(Table $table, string $condition, string $type);
    join(array $from, string $condition, $string $type)
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
    } else {
      $join = new JoinOne();
      $join->from($this->getOrCreateTable($args[0]));
    }
    $condition = $args[1];
    if ($args[2]) {
      $join->setType($args[2]);
    }
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

  public function getLastJoin() {
    $n = count($this->joins);
    return $n? $this->joins[$n - 1] : NULL;
  }

  protected function cache($where, $key, $obj) {
    if (!$key || !$obj || !$where) return;
    return $this->cache[$where][$key] = $obj;
  }

  public function from($root, $alias = NULL) {
    if (is_string($root)) {
      $this->root = $this->getOrCreateTable(array($root => $alias));
    } else {
      $this->root = $root;
    }
    return $this;
  }

  public function addJoin($join) {
    $this->joins[] = $join;
  }

  public function addColumn($column) {
    if (is_string($column)) {
      $parts = explode(".", $column);
      $col = new Column();
      $col->from($this->getOrCreateTable($parts[0]));
      $col->setName($parts[1]);
    } elseif (is_array($column)) {
      $col = new Column();
      $keys = array_keys($column);
      $alias = $column[$keys[0]];
      $name = $keys[0];
      $parts = explode(".", $name);
      $col->from($this->getOrCreateTable($parts[0]));
      $col->setName($parts[1]);
      $col->setAlias($alias);
    } else if ($column instanceof Column) {
      $col = $column;
    }
    $this->columns[] = $col;
    return $this;
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
      if (isset($condition["column"]) && isset($condition["value"])) { //condition
        $column = $condition["column"];
        $operator = $condition["operator"];
        $value = $condition["value"];
        if ($operator == "~") {
          return $column->getTable()->getAlias() . "." . $column->getName() . " LIKE " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
        }
        if ($operator == "!=") {
          return $column->getTable()->getAlias() . "." . $column->getName() . " <> " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
        }
        if ($operator == "~") {
          return $column->getTable()->getAlias() . "." . $column->getName() . " LIKE " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
        }

        return $column->getTable()->getAlias() . "." . $column->getName() . " " . $operator . " " . (is_string($value)? "\\\"" . $value . "\\\"" : $value);
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

  public function getSql() {
    $sql = "SELECT ";

    $root_alias = $this->getRoot()->getAlias();

    $columns = $this->getColumns();
    $select_columns = array();
    for ($i = 0; $i < count($columns); $i++) {
      $column = $columns[$i];
      $select_columns[] = $column->getSql();
    }

    $sql .= implode(", ", $select_columns);
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
      $sql .= " ORDER BY ";
    }
    for ($i = 0; $i < count($sort_by); $i++) {
      $column = $sort_by[$i]["column"];
      $alias = $column->getTable()->getAlias();
      $sql .= $i? ", " : "";
      $sql .= $alias . "."  . $column->getName() . " " . $sort_by[$i]["direction"];
    }

    if (is_array($this->limit)) {
      $sql .= " LIMIT " . join(",", $this->limit);
    }
    return $sql;
  }

  function addSortBy($column, $direction) {
    if (is_string($column)) {
      $column = new Column();
      $parts = explode(".", $column);
      $column->setName($parts[1]);
      $column->from($this->getOrCreateTable($parts[0]));
    } else {
      $column = $column;
    }
    $this->sort_by[] = array("column" => $column, "direction" => $direction);
  }

  function limit($limit, $offset) {
    $this->limit = array($limit, $offset);
  }
}

?>