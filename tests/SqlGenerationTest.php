<?php

use \fitch\Meta as Meta;
error_reporting(E_ALL ^ E_NOTICE);
class SqlGenerationTest extends PHPUnit_Framework_TestCase
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

  public function testManyToMany()
  {
    $tests = array();
    $tests["alias"] = array(
      "token" => array(
        "name" => "schools",
        "type" => "Segment",
        "fields" => array(
          array(
            "name" => "name"
          ),
          array(
            "name" => "departments.name",
            "alias" => "department_name"
          )
        )
      ),
      "sql" => "SELECT schools_0.id, schools_0.name, departments_0.id, departments_0.name FROM schools AS schools_0 LEFT JOIN school_department schools_departments_0 ON (schools_departments_0.school_id = schools_0.id)  LEFT JOIN departments departments_0 ON (departments_0.id = schools_departments_0.department_id)",
      "result" => array(
        "schools" => array(
          "1" => array("name" => "School #1", "department_name" => array("1" => "Department #1", "2" => "Department #2")),
          "2" => array("name" => "School #2", "department_name" => array("1" => "Department #1"))
        )
      )
    );
    $tests["without_alias"] = array(
      "token" => array(
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
      ),
      "sql" => "SELECT schools_0.id, schools_0.name, departments_0.id, departments_0.name FROM schools AS schools_0 LEFT JOIN school_department schools_departments_0 ON (schools_departments_0.school_id = schools_0.id)  LEFT JOIN departments departments_0 ON (departments_0.id = schools_departments_0.department_id)",
      "result" => array(
        "schools" => array(
          "1" => array("name" => "School #1", "departments.name" => array("1" => "Department #1", "2" => "Department #2")),
          "2" => array("name" => "School #2", "departments.name" => array("1" => "Department #1"))
        )
      )
    );

    $tests["nested"] = array(
      "token" => array(
        "name" => "schools",
        "type" => "Segment",
        "fields" => array(
          array(
            "name" => "name",
            "alias" => "alias_name"
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
      ),
      "sql" => "SELECT schools_0.id, schools_0.name, departments_0.id, departments_0.name FROM schools AS schools_0 LEFT JOIN school_department schools_departments_0 ON (schools_departments_0.school_id = schools_0.id)  LEFT JOIN departments departments_0 ON (departments_0.id = schools_departments_0.department_id)",
      "result" => array(
        "schools" => array(
          "1" => array("alias_name" => "School #1", "departments" => array(
              "1" => array("name" => "Department #1"),
              "2" => array("name" => "Department #2")
            )
          ),
          "2" => array("alias_name" => "School #2", "departments" => array(
              "1" => array("name" => "Department #1")
            )
          )
        )
      )
    );

    $tests["nested_withou_alias"] = array(
      "token" => array(
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
      ),
      "sql" => "SELECT schools_0.id, schools_0.name, departments_0.id, departments_0.name FROM schools AS schools_0 LEFT JOIN school_department schools_departments_0 ON (schools_departments_0.school_id = schools_0.id)  LEFT JOIN departments departments_0 ON (departments_0.id = schools_departments_0.department_id)",
      "result" => array(
        "schools" => array(
          "1" => array("name" => "School #1", "departments" => array(
              "1" => array("name" => "Department #1"),
              "2" => array("name" => "Department #2")
            )
          ),
          "2" => array("name" => "School #2", "departments" => array(
              "1" => array("name" => "Department #1")
            )
          )
        )
      )
    );

    $meta = $this->meta;


    $meta = new Meta($meta);

    $rows = array (
              array(1, "School #1", 1, "Department #1"),
              array(2, "School #2", 1, "Department #1"),
              array(1, "School #1", 2, "Department #2")
            );

    foreach ($tests as $test) {
      $segment = new \fitch\fields\Segment($test["token"]);
      $generator = new \fitch\sql\SqlGenerator($segment, $meta);
      $queries = $generator->getQueries();
      $sql = $queries[0]->getSql($meta);


      $this->assertEquals($test["sql"], $sql);

      $populator = new \fitch\sql\ArrayHydration($queries[0], $segment, $meta);

      $nested = $populator->getResult($rows);
      $this->assertEquals($test["result"], $nested);
    }
  }

}



?>