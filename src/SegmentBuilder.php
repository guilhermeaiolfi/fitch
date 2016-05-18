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
    $segment = $this->buildNode('\\fitch\\fields\\Segment', $data);
    //print_r($segment->getMapping());
    return $segment;
  }

  protected function sliceDottedRelation($data) {
    $parts = explode(".", $data["name"]);

    $n = count($parts);

    if ($n > 1) {
      $data["name"] = array_shift($parts);
      $data["generated"] = true;
      $data["visible"] = false;
      $data["fields"] = array(
          array(
            "name" => implode(".", $parts),
            "generated" => false,
            "alias" => $data["alias"],
            "fields" => $data["fields"]
          )
      );
      $data["alias"] = NULL;
    }
    return $data;
  }

  public function buildNode($type, $data, $parent = NULL) {
    $meta = $this->meta;

    $data = $this->sliceDottedRelation($data);

    $current = new $type();

    $current->setGenerated(!!$data["generated"]);
    $current->setName($data["name"]);
    $current->setAlias($data["alias"]);
    if ($data["visible"] === false) {
      $current->setVisible(false);
    }
    $current->setParent($parent);

    if ($current instanceof Relation) {
      if (!$parent) {
        $current->setTable($data["name"]);
      } else {
        $table = $meta->getTableNameFromRelation($parent->getTable(), $data["name"]);
        if (!$table) {
          throw new \Exception("Relation: \"" . $current->getName() . "\" doesn't exist in table \"" . $parent->getName() . "\"", 1);
        }
        $current->setTable($table);
      }
    }

    if (isset($data["fields"]) && is_array($data["fields"])) {
      foreach ($data["fields"] as $field) {
        $obj = NULL;
        $name = explode(".", $field["name"]);
        $join = $meta->getRelationConnections($current->getTable(), $name[0]);
        if (empty($field["fields"]) && strpos($field["name"], ".") === false && $join == NULL) {

          if (!$meta->hasField($current->getTable(), $field["name"])) {
            continue;
          }

          $field["visible"] = true;

          $obj = $this->buildNode('\\fitch\fields\\Field', $field, $current);
        } else { // relation
          $cardinality = "OneRelation";
          if ($meta->getCardinality($current->getTable(), $name[0]) == "many") {
            $cardinality = "ManyRelation";
          }
          $obj = $this->buildNode("\\fitch\\fields\\" . $cardinality, $field, $current);
        }
        $obj->setParent($current);
        $current->addChild($obj);
      }
    } else {
      if ($current instanceof Relation) {
        $fields = $meta->getFields($current->getTable());
        if (is_array($fields)) {
          foreach($fields as $field) {
            $field = is_array($field)? $field : array("name" => $field);
            $field = $this->buildNode("\\fitch\\fields\\" . "Field", $field, $current);
            $field->setParent($current);
            $current->addChild($field);
          }
        }
      }
    }

    if ($current instanceof Relation) {
      //print_r($current->getChildren());exit;
      if ($current->hasVisibleFields() || !$parent) {
        $this->createHashField($current);
      }

      $current->setConditions($data["conditions"]);

      for($i = 0; $i < count($data["functions"]); $i++) {

        $function = $data["functions"][$i];
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
    $current->setMany($this->meta->isMany($current));
    return $current;
  }

  protected function createHashField($node) {
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