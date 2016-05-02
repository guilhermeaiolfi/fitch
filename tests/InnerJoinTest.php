<?php

use \fitch\Meta as Meta;
use \fitch\parser\Parser as Parser;
error_reporting(E_ALL ^ E_NOTICE);
class InnerJoinTest extends PHPUnit_Framework_TestCase
{



  protected $meta = array(
    "schools" => array(
      "fields" => array("id", "name")
    ),
    "users" => array(
      "fields" => array("id", "name")
    ),
    "schools.director" => array(
      "foreign_keys" => array(
        "school.director_id" => "users.id"
      )
    ),
    "schools.departments" => array(
      "foreign_keys" => array(
        "schools.id" => "school_department.school_id",
        "school_department.department_id" => "departments.id"
        )
    ),
    "schools.programs" => array(
      "foreign_keys" => array(
        "schools.id" => "school_program.school_id",
        "school_program.program_id" => "programs.id"
      )
    ),
    "departaments.courses" => array(
      "foreign_keys" => array(
        "departaments.id" => "departament_course.departament_id",
        "departament_course.course_id" => "courses.id"
      )
    )
  );

  /*public function testNoFields()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $rows = array (
      array(1, "School #1"),
      array(2, "School #2"),
    );

    $ql = "/schools";

    $segment = $parser->parse($ql);
    $segment = new \fitch\fields\Segment($segment);

    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($queries[0], $segment, $meta);

    $nested = $populator->getResult($rows);
    print_r($nested);exit;

    $result = array (
      "schools" => array (
        0 => array("name" => "School #1", "id" => 1, "departments.id" => array (0 => 1, 1 => 2)),
        1 => array("name" => "School #2", "id" => 2, "departments.id" => array (0 => 1))
      )
    );

    $this->assertEquals($result, $nested);
  }*/

  public function testSimpleJoin()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $rows = array (
      array("School #1", 1, 1),
      array("School #2", 2, 1),
      array("School #1", 1, 2)
    );

    $ql = "/schools{name, id, departments.id}";

    $segment = $parser->parse($ql);
    $segment = new \fitch\fields\Segment($segment);
    //print_r($segment);exit;
    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($queries[0], $segment, $meta);

    $nested = $populator->getResult($rows);
    //print_r($nested);

    $result = array (
      "schools" => array (
        0 => array("name" => "School #1", "id" => 1, "departments.id" => array (0 => 1, 1 => 2)),
        1 => array("name" => "School #2", "id" => 2, "departments.id" => array (0 => 1))
      )
    );

    $this->assertEquals($result, $nested);
  }

  public function testNestedRelation()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $rows = array (
      array("School #1", 1, 1),
      array("School #2", 2, 1),
      array("School #1", 1, 2)
    );

    $ql = "/schools{name, id, departments{id}}";

    $segment = $parser->parse($ql);
    $segment = new \fitch\fields\Segment($segment);
    //print_r($segment);exit;
    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($queries[0], $segment, $meta);

    $nested = $populator->getResult($rows);

    $result = array (
      "schools" => array (
        0 => array("name" => "School #1", "id" => 1, "departments" => array (0 => array ("id" => 1), 1 => array("id" => 2))),
        1 => array("name" => "School #2", "id" => 2, "departments" => array (0 => array("id" => 1)))
      )
    );
    $this->assertEquals($result, $nested);
  }

}



?>