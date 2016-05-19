<?php
namespace fitch;

use \fitch\fields\Field as Field;
use \fitch\fields\Segment as Segment;
use \fitch\fields\Relation as Relation;
use \fitch\fields\OneRelation as OneRelation;
use \fitch\fields\ManyRelation as ManyRelation;
use \fitch\fields\PrimaryKeyHash as PrimaryKeyHash;

class SegmentBuilder
{
  protected $parents = array();
  protected $meta = NULL;
  public function __construct($meta) {
    $this->meta = $meta;
  }

  public function buildSegment($data) {
    $data = $this->splitArray($data);
    $segment = $this->buildNode('\\fitch\\fields\\Segment', $data);
    return $segment;
  }

  protected function splitArray($data) {
    if (strpos($data["name"], ".") !== FALSE) {
      $name = explode(".", $data["name"]);
      $data["name"] = array_shift($name);
      $data["generated"] = true;
      $data["visible"] = false;
      $data["fields"] = array(
          array(
            "name" => implode(".", $name),
            "generated" => false,
            "alias" => $data["alias"],
            "fields" => $data["fields"]
          )
      );
      $data["alias"] = NULL;
    }
    if (isset($data["fields"]) && is_array($data["fields"])) {
      for ($i = 0; $i < count($data["fields"]); $i++) {
        $data["fields"][$i] = $this->splitArray($data["fields"][$i]);
      }
    }
    return $data;
  }

  protected function getTableName($node) {
    $table = NULL;
    $parent = $node->getParent();
    if (!$parent) {
      $table = $node->getName();
    } else {
      $table = $this->meta->getTableNameFromRelation($parent->getTable(), $node->getName());
    }
    return $table;
  }

  public function buildNode($type, $data, $parent = NULL) {
    $meta = $this->meta;

    $current = new $type();
    $current->setGenerated(!!$data["generated"]);
    $current->setName($data["name"]);
    $current->setAlias($data["alias"]);
    if ($data["visible"] === false) {
      $current->setVisible(false);
    }
    $current->setParent($parent);

    if ($current instanceof Relation) {
      $table = $this->getTableName($current);
      if (!$table) {
        throw new \Exception("Relation: \"" . $current->getName() . "\" doesn't exist in table \"" . $parent->getName() . "\"", 1);
      }
      $current->setTable($table);

      $children = $data["fields"];
      if (!isset($children) || !is_array($children)) {
        // add all fields if none are specified
        $children = $meta->getFields($current->getTable());
      }

      $this->manageChildren($current, $children);

      if ($current->hasVisibleFields() || !$parent) {
        $this->createPkField($current);
      }

      $current->setConditions($data["conditions"]);

      $this->manageFunctions($current, $data["functions"]);
    }
    $current->setMany($this->meta->isMany($current));
    return $current;
  }

  protected function manageChildren($current, $fields) {
    if ($fields == NULL || !is_array($fields) || count($fields) == 0) {
      return;
    }
    $meta = $this->meta;
    foreach ($fields as $field) {
      if (is_string($field)) {
        $field = array("name" => $field);
      }
      $obj = NULL;
      $join = $meta->getRelationConnections($current->getTable(), $field["name"]);
      if (empty($field["fields"]) && $join == NULL) {

        if (!$meta->hasField($current->getTable(), $field["name"])) {
          continue;
        }

        $field["visible"] = true;

        $obj = $this->buildNode('\\fitch\fields\\Field', $field, $current);
      } else { // relation
        $cardinality = "OneRelation";
        if ($meta->getCardinality($current->getTable(), $field["name"]) == "many") {
          $cardinality = "ManyRelation";
        }
        $obj = $this->buildNode("\\fitch\\fields\\" . $cardinality, $field, $current);
      }
      $obj->setParent($current);
      $current->addChild($obj);
    }
  }

  protected function manageFunctions($current, $functions) {
    for($i = 0; $i < count($functions); $i++) {
      $function = $functions[$i];
      if ($function["name"] == "sort") {
        for($y = 0; $y < count($function["params"]); $y++) {
          $current->addSort($function["params"][$y][0], $function["params"][$y][1]);
        }
      }
      if ($function["name"] == "limit") {
        $current->limit($function["params"][0], $function["params"][1]);
      }
    }
  }

  protected function createPkField($node) {
    $primary_key = $this->meta->getPrimaryKeyName($node);
    $primary_key_field = new PrimaryKeyHash();
    $primary_key_field->setPrimaryKey(array($primary_key));
    $primary_key_field->setName($primary_key);

    $primary_key_field->setParent($node);
    $children = $node->getChildren();
    $replaced = false;
    for ($i = 0; $i < count($children); $i++) {
      if ($children[$i]->getName() == $primary_key) { //TODO: removed hardcode primary_key
        $primary_key_field->setField($children[$i]);
        $primary_key_field->setMany($children[$i]->isMany());
        $children[$i] = $primary_key_field;
        $replaced = true;
        break;
      }
    }
    if (!$replaced) {
      $primary_key_field->setVisible(false);

      array_unshift($children, $primary_key_field);
    }
    $node->setChildren($children);
    return $primary_key_field;
  }
}
?>