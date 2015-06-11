<?php

// include __DIR__ . "/../vendor/autoload.php";
// include __DIR__ . "/../src/Node.php";
// include __DIR__ . "/../src/Field.php";
// include __DIR__ . "/../src/Segment.php";
// include __DIR__ . "/../src/Relation.php";
// include __DIR__ . "/../src/Query.php";
// include __DIR__ . "/../src/Fitch.php";
// include __DIR__ . "/../src/ArrayHydration.php";
// include __DIR__ . "/../src/SqlFitch.php";

// use \fitch\Segment as Segment;
// use \fitch\Relation as Relation;
// use \fitch\SqlFitch as SqlFitch;
// use \fitch\Fitch as Fitch;
// use \fitch\ArrayHydration as ArrayHydration;
use \fitch\Meta as Meta;
error_reporting(E_ALL ^ E_NOTICE);
class SqlGenerationTest extends PHPUnit_Framework_TestCase
{



  protected $meta = array(
    "schools.director" => array(
      "school.director_id" => "users.id"
    ),
    "schools.departments" => array(
      "schools.id" => "school_department.school_id",
      "school_department.department_id" => "departments.id"
    ),
    "schools.programs" => array(
      "schools.id" => "school_program.school_id",
      "school_program.program_id" => "programs.id"
    ),
    "departaments.courses" => array(
      "departaments.id" => "departament_course.departament_id",
      "departament_course.course_id" => "courses.id"
    )
  );

  public function testManyToManyTwoLevelDeep()
  {
    $double = $this->double;
    $meta = $this->meta;

    $token = array(
      "name" => "schools",
      "type" => "Segment",
      "fields" => array(
        array(
          "name" => "name"
        ),
        array(
          "name" => "departments",
          "fields" => array(
            array(
              "name" => "name"
            )
          )
        )
      )
    );

    $segment = new \fitch\fields\Segment($token);

    $meta = new Meta($meta);
    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);
    $expected = "SELECT schools.id AS schools_id, schools.name, departments.id AS departments_id, departments.name FROM schools AS schools LEFT JOIN school_department schools_departments ON (schools_departments.school_id = schools.id)  LEFT JOIN departments departments ON (departments.id = schools_departments.department_id)";

    $this->assertEquals($expected, $sql);

    $populator = new \fitch\sql\ArrayHydration($queries[0], $segment, $meta);
    $rows = array (
              array(1, "School #1", 1, "Department #1"),
              array(2, "School #2", 1, "Department #1"),
              array(1, "School #1", 2, "Department #2")
            );

    $nested = $populator->getResult($rows);
    $expected = array(
      "1" => array("name" => "School #1", "departments" => array("1" => array("name" => "Department #1"), "2" => array("name" => "Department #2"))),
      "2" => array("name" => "School #2", "departments" => array("1" => array("name" => "Department #1")))
    );
    $this->assertEquals($nested,$expected);
  }

  public function testManyToManyOneLevelDeep()
  {
    $token = array(
      "name" => "schools",
      "type" => "Segment",
      "fields" => array(
        array(
          "name" => "name"
        ),
        array(
          "name" => "departments.name"
        )
      )
    );

    $meta = $this->meta;

    $segment = new \fitch\fields\Segment($token);

    $meta = new Meta($meta);
    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);
    $expected = "SELECT schools.id AS schools_id, schools.name, departments.id AS departments_id, departments.name FROM schools AS schools LEFT JOIN school_department schools_departments ON (schools_departments.school_id = schools.id)  LEFT JOIN departments departments ON (departments.id = schools_departments.department_id)";

    $this->assertEquals($expected, $sql);

    $populator = new \fitch\sql\ArrayHydration($queries[0], $segment, $meta);
    $rows = array (
              array(1, "School #1", 1, "Department #1"),
              array(2, "School #2", 1, "Department #1"),
              array(1, "School #1", 2, "Department #2")
            );

    $nested = $populator->getResult($rows);
    $expected = array(
      "1" => array("name" => "School #1", "departments.name" => array("Department #1", "Department #2")),
      "2" => array("name" => "School #2", "departments.name" => array("Department #1"))
    );
    $this->assertEquals($expected, $nested);
  }
}



?>