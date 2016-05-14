<?php
namespace fitch\sql;

class AliasManager
{
  protected $aliases = array();
  protected $tables = array();
  protected $pivots = 0;

  public function setTableForRelation($relation, $table) {
    $id = spl_object_hash($relation);
    $this->tables[$id] = $table;
  }

  public function getTableFromRelation($relation) {
    $id = spl_object_hash($relation);
    if ($this->tables[$id]) {
      return $this->tables[$id];
    }
    $table = new \fitch\sql\Table();
    $table->setName($relation->getTable());
    $table->setAlias($this->getTableAliasFor($relation));
    return $this->tables[$id] = $table;
  }

  public function getPivotAlias($table = NULL) {
    return $this->getTableAliasFor($table? $table : "pivot");
  }

  public function getTableAliasFor($node) {
    $table = "";
    if ($node instanceof \fitch\fields\Relation) {
      $table = $node->getTable();
    } else if ($node instanceof \fitch\sql\Query) {
      $table = $node->getName();

    } else {
      $table = $node;
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