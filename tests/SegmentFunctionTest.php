<?php

use \fitch\Meta as Meta;
error_reporting(E_ALL ^ E_NOTICE);
class SegmentFunctionTest extends PHPUnit_Framework_TestCase
{

  protected $meta = array(
    "schools" => array(
      "primary_key" => "id",
      "fields" => array("id", "name"),
      "foreign_keys" => array(
        "director" => array(
          "schools.director_id" => "users.id"
        ),
        "departments" => array(
          "schools.id" => "school_department.school_id",
          "school_department.department_id" => "departments.id"
        ),
        "programs" => array(
          "schools.id" => "school_program.school_id",
          "school_program.program_id" => "programs.id"
        )
      )
    ),
    "departments" => array(
      "primary_key" => "id",
      "fields" => array("id", "name"),
      "foreign_keys" => array(
        "courses" => array(
          "departaments.id" => "departament_course.departament_id",
          "departament_course.course_id" => "courses.id"
        )
      )
    ),
    "users" => array(
      "primary_key" => "id",
      "fields" => array("id", "name")
    ),
    "courses" => array(
      "primary_key" => "id",
      "fields" => array("id", "name")
    )
  );

  public function testLimitFunction()
  {
    $parser = new \fitch\parser\Parser();
    $result = $parser->parse("/schools.limit(10,20)");

    $expected = array (
      "name" => "schools",
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

    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($result);
    $generator = new \fitch\sql\QueryGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $this->assertEquals($result, $expected);
    $this->assertEquals($sql, "SELECT schools_0.id, schools_0.name FROM schools AS schools_0 LIMIT 10,20");
  }

  public function testSortFunction()
  {
    $parser = new \fitch\parser\Parser();
    $result = $parser->parse("/schools.sort(id+,name-)");

    $expected = array (
      "name" => "schools",
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

    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($result);

    $generator = new \fitch\sql\QueryGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $this->assertEquals($result, $expected);
    $this->assertEquals("SELECT schools_0.id, schools_0.name FROM schools AS schools_0 ORDER BY schools_0.id ASC, schools_0.name DESC", $sql);
  }
}



?>