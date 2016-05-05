<?php

use \fitch\Meta as Meta;
use \fitch\parser\Parser as Parser;
error_reporting(E_ALL ^ E_NOTICE);
class InnerJoinTest extends PHPUnit_Framework_TestCase
{

  protected $meta = array(
    "schools" => array(
      "fields" => array(
        array("name" => "id"),
        array("name" => "name")
      )
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
    "departments.courses" => array(
      "foreign_keys" => array(
        "departments.id" => "department_course.departament_id",
        "department_course.course_id" => "courses.id"
      )
    )
  );

  public function testNoFields()
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
    $segment = new \fitch\fields\Segment($meta, $segment);

    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($queries[0], $segment, $meta);

    $nested = $populator->getResult($rows);
    //print_r($nested);exit;

    $result = array (
      "schools" => array (
        0 => array("id" => 1, "name" => "School #1"),
        1 => array("id" => 2, "name" => "School #2")
      )
    );

    $this->assertEquals($result, $nested);
  }


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
    $segment = new \fitch\fields\Segment($meta, $segment);
    // print_r($segment);exit;
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
    $segment = new \fitch\fields\Segment($meta, $segment);
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

  public function testAliasForCondition()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $rows = array (
      array(1, 1, 4),
      array(2, 1, 5),
      array(1, 2, 6)
    );

    $ql = "/schools{id,departments :as dep{id}}?dep.id=1";

    $segment = $parser->parse($ql);
    $segment = new \fitch\fields\Segment($meta, $segment);
    //print_r($segment->getMapping());exit;
    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($queries[0], $segment, $meta);

    $nested = $populator->getResult($rows);

    $sql_expected = "SELECT schools_0.id, departments_0.id FROM schools AS schools_0 LEFT JOIN school_department schools_departments_0 ON (schools_departments_0.school_id = schools_0.id)  LEFT JOIN departments departments_0 ON (departments_0.id = schools_departments_0.department_id) WHERE departments_0.id = 1";

    $this->assertEquals($sql_expected, $sql);

    $result = array (
      "schools" => array (
        0 => array("id" => 1, "dep" => array (0 => array ("id" => 1), 1 => array("id" => 2))),
        1 => array("id" => 2, "dep" => array (0 => array("id" => 1)))
      )
    );
    $this->assertEquals($result, $nested);
  }

  public function testThreeDeepRelation()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $rows = array (
      array(1, 1, 4),
      array(2, 1, 5),
      array(1, 2, 6)
    );

    $ql = "/schools{id,departments{id, courses{id}}}";

    $segment = $parser->parse($ql);
    $segment = new \fitch\fields\Segment($meta, $segment);
    //print_r($segment);exit;
    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($queries[0], $segment, $meta);

    $nested = $populator->getResult($rows);

    $sql_expected = "SELECT schools_0.id, departments_0.id, courses_0.id FROM schools AS schools_0 LEFT JOIN school_department schools_departments_0 ON (schools_departments_0.school_id = schools_0.id)  LEFT JOIN departments departments_0 ON (departments_0.id = schools_departments_0.department_id) LEFT JOIN department_course departments_courses_0 ON (departments_courses_0.departament_id = departments_0.id)  LEFT JOIN courses courses_0 ON (courses_0.id = departments_courses_0.course_id)";

    $this->assertEquals($sql_expected, $sql);

    $result = array (
      "schools" => array (
        0 => array("id" => 1, "departments" => array (0 => array ("id" => 1, "courses" => array(array("id" => 4))), 1 => array("id" => 2, "courses" => array(array("id" => 6))))),
        1 => array("id" => 2, "departments" => array (0 => array("id" => 1, "courses" => array(array("id" => 5)))))
      )
    );
    $this->assertEquals($result, $nested);
  }

  public function testDottedDeep()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $rows = array (
      array(1, "Name #1", 1, 1),
      array(2, "Name #2", 1, 1),
      array(1, "Name #1", 1, 2)
    );

    $ql = "/schools{name,departments.courses.id}";

    $segment = $parser->parse($ql);
    $segment = new \fitch\fields\Segment($meta, $segment);

    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($queries[0], $segment, $meta);

    $nested = $populator->getResult($rows);

    //print_r($nested);
    $sql_expected = "SELECT schools_0.id, schools_0.name, departments_0.id, courses_0.id FROM schools AS schools_0 LEFT JOIN school_department schools_departments_0 ON (schools_departments_0.school_id = schools_0.id)  LEFT JOIN departments departments_0 ON (departments_0.id = schools_departments_0.department_id) LEFT JOIN department_course departments_courses_0 ON (departments_courses_0.departament_id = departments_0.id)  LEFT JOIN courses courses_0 ON (courses_0.id = departments_courses_0.course_id)";

    $this->assertEquals($sql_expected, $sql);

    $result = array (
      "schools" => array (
        0 => array("name" => "Name #1", "departments.courses.id" => array(1, 2)),
        1 => array("name" => "Name #2", "departments.courses.id" => array(1))
      )
    );
    $this->assertEquals($result, $nested);
  }

}



?>