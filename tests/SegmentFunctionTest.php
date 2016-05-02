<?php

use \fitch\Meta as Meta;
error_reporting(E_ALL ^ E_NOTICE);
class SegmentFunctionTest extends PHPUnit_Framework_TestCase
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

  public function testLimitFunction()
  {
    $parser = new \fitch\parser\Parser();
    $result = $parser->parse("/school.limit(10,20)");

    $expected = array (
      "name" => "school",
      "type" => "Segment",
      "ids" => NULL,
      "functions" =>  array (
        array (
          "type" => "Function",
          "name" => "limit",
          "params" => array (
            10,
            20
          )
        )
      ),
      "fields" => NULL,
      "conditions" => NULL
    );

    $meta = new Meta($this->meta);

    $segment = new \fitch\fields\Segment($meta, $expected);
    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $this->assertEquals($result, $expected);
    $this->assertEquals($sql, "SELECT school_0.id FROM school AS school_0 LIMIT 10,20");
  }

  public function testSortFunction()
  {
    $parser = new \fitch\parser\Parser();
    $result = $parser->parse("/school.sort(id+,name-)");

    $expected = array (
      "name" => "school",
      "type" => "Segment",
      "ids" => NULL,
      "functions" =>  array (
        array (
          "type" => "Function",
          "name" => "sort",
          "params" => array (
            array ("id", "+"),
            array ("name", "-")
          )
        )
      ),
      "fields" => NULL,
      "conditions" => NULL
    );

    $meta = new Meta($this->meta);

    $segment = new \fitch\fields\Segment($meta, $expected);
    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $this->assertEquals($result, $expected);
    $this->assertEquals($sql, "SELECT school_0.id FROM school AS school_0 SORT BY id ASC, name DESC");
  }

}



?>