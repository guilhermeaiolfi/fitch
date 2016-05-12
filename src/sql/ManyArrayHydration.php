<?php

namespace fitch\sql;

use \fitch\fields\Relation as Relation;
//use \fitch\fields\Field as Field;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class ManyArrayHydration extends ArrayHydration{

  public function getResult($results) {
    $result = array();
    $index = 0;
    $keys = array_keys($results);
    foreach ($keys as $relation_name) {
      foreach($results[$relation_name] as $row) {
        $this->populateRow($result, $row, $relation_name);
      }
    }
    //print_r($result);exit;
    return $result;
  }

  public function isNodeFromBranch($node, $relation_name) {
    while ($parent = $node->getParent()) {
      if ($parent->getName() == $relation_name) {
        return true;
      }
      $node = $node->getParent();
    }
    return false;
    //if $node->getParent()->getName() == $relation_name || $node->getParent()
  }

  public function populateRow(&$result, $row, $relation_name = NULL) {
    $arr = &$result;
    $relations = array();
    $ids = array();

    $column_index = 0;
    $pending = array($this->segment);
    $pks = 0;

    while ($node = array_shift($pending)) {
      $name = $node->getAliasOrName();

      if ($node instanceof \fitch\fields\Relation) {
        if ($children = $node->getChildren()) {
          foreach ($children as $child) {
            $pending[] = $child;
          }
          if ($node->isGenerated()) {
            continue;
          }
        }
        if (!is_array($arr[$name])) {
          $arr[$name] = array();
        }

        //echo $node->getName() . "($column_index)" . "." . $row[$column_index] . "\n";
        $id = $row[$column_index];

        //if ($row[$column_index + 1] == NULL) break;
        if ($id) {
          $ids[] = array($name => $id);
          /*echo "----------------------------\n";
          print_r($ids);
          echo "\n";
          print_r($this->line);*/

          $line = $this->getLineFor($ids);
          //echo "LINE: " . $line . "\n";
          if ($line === NULL) {
            $arr[$name][] = array();
            $line = count($arr[$name]) - 1;
            $this->setLineFor($ids, $line);
          }

          $arr = &$arr[$name][$line];

          $relations[] = array("_pointer" => &$arr, "_relation" => $node);
        }

      } elseif ($node instanceof \fitch\fields\Field) {
        if ($node->isVisible() && $this->isNodeFromBranch($node, $relation_name)) {
          //echo $node->getName() . " - " . $column_index . " - " . $row[$diff] . "\n";
          $this->setFieldValue($relations[$node->getLevel()], $node, $row[$column_index++]);
        } else if ($node instanceof \fitch\fields\PrimaryKeyHash){
          $column_index++;
          $pks++;
        }

        if ($column_index > count($row)) { //nothing to do here for this branch
          break;
        }
      }
    }
  }
}

?>