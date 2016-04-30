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
          "arguments" => array (
            array ("id", "+"),
            array ("name", "-")
          )
        )
      ),
      "fields" => NULL,
      "conditions" => NULL
    );

    $meta = new Meta($this->meta);

    $segment = new \fitch\fields\Segment($expected);
    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $this->assertEquals($result, $expected);
    $this->assertEquals($sql, "SELECT school.id AS school_id FROM school AS school SORT BY id ASC, name DESC");
  }

}



?>