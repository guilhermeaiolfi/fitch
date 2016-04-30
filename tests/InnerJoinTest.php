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

  public function testSimpleJoin()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();
    // $ql = "/schools.director";
    // $segment = $parser->parse($ql);
    // $segment = new \fitch\fields\Segment($segment);
    // $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    // $queries = $generator->getQueries();
    // $sql = $queries[0]->getSql($meta);


    // $this->assertEquals("SELECT director_0.id FROM schools AS schools_0 INNER JOIN users director_0 ON (schools_0.director_id = director_0.id)", $sql);



    $rows = array (
      array(1, 1, "School #1", 1, 1),
      array(2, 2, "School #2", 1, 1),
      array(1, 1, "School #1", 2, 2)
    );

    $ql = "/schools{id, name, departments.id}";
    $segment = $parser->parse($ql);
    $segment = new \fitch\fields\Segment($segment);
    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($queries[0], $segment, $meta);

    $nested = $populator->getResult($rows);
    //print_r($nested);

    $result = array (
      "schools" => array (
        1 => array("id" => 1, "name" => "School #1", "departments.id" => array (1 => 1, 2 => 2)),
        2 => array("id" => 2, "name" => "School #2", "departments.id" => array (1 => 1))
      )
    );
    $this->assertEquals($result, $nested);
  }

}



?>