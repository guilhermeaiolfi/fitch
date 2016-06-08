<?php

use \fitch\Meta as Meta;
error_reporting(E_ALL ^ E_NOTICE);
class SqlGenerationTest extends PHPUnit_Framework_TestCase
{
  protected $meta = array(
    "schools" => array(
      "primary_key" => "id",
      "fields" => array(
        array("name" => "id"),
        array("name" => "name")
      ),
      "foreign_keys" => array(
        "director" => array(
          "table" => "users",
          "cardinality" => "one",
          "on" => array("schools.director_id" => "users.id")
        ),
        "programs" => array(
          "table" => "programs",
          "fields" => array("id"),
          "cardinality" => "many",
          "on" => array(
            "schools.id" => "school_program.school_id",
            "school_program.program_id" => "programs.id"
          )
        ),
        "departments" => array(
          "table" => "departments",
          "cardinality" => "many",
          "on" => array(
            "schools.id" => "school_department.school_id",
            "school_department.department_id" => "departments.id"
          )
        ),
        "courses" => array(
          "table" => "courses",
          "cardinality" => "many",
          "on" => array(
            "schools.id" => "school_course.school_id",
            "school_course.course_id" => "courses.id"
          )
        )
      )
    ),
    "departments" => array(
      "primary_key" => "id",
      "fields" => array(
        array("name" => "id"),
        array("name" => "name")
      ),
      "foreign_keys" => array(
        "schools" => array(
          "table" => "schools",
          "cardinality" => "many",
          "on" => array(
            "departments.id" => "school_department.department_id",
            "school_department.school_id" => "schools.id"
          )
        ),
        "courses" => array(
          "table" => "courses",
          "cardinality" => "many",
          "on" => array(
            "departments.id" => "department_course.departament_id",
            "department_course.course_id" => "courses.id"
          )
        )
      )
    ),
    "courses" => array(
      "primary_key" => "id"
    ),
    "users" => array(
      "primary_key" => "id",
      "fields" => array("id", "name")
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
      "sql" => "SELECT schools_0.id, schools_0.name, departments_0.id, departments_0.name FROM schools AS schools_0 INNER JOIN school_department school_department_0 ON (school_department_0.school_id = schools_0.id) INNER JOIN departments departments_0 ON (departments_0.id = school_department_0.department_id)",
      "result" => array(
        "schools" => array(
          0 => array("name" => "School #1", "department_name" => array(0 => "Department #1", 1 => "Department #2")),
          1 => array("name" => "School #2", "department_name" => array(0 => "Department #1"))
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
      "sql" => "SELECT schools_0.id, schools_0.name, departments_0.id, departments_0.name FROM schools AS schools_0 INNER JOIN school_department school_department_0 ON (school_department_0.school_id = schools_0.id) INNER JOIN departments departments_0 ON (departments_0.id = school_department_0.department_id)",
      "result" => array(
        "schools" => array(
          0 => array("name" => "School #1", "departments.name" => array(0 => "Department #1", 1 => "Department #2")),
          1 => array("name" => "School #2", "departments.name" => array(0 => "Department #1"))
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
      "sql" => "SELECT schools_0.id, schools_0.name, departments_0.id, departments_0.name FROM schools AS schools_0 INNER JOIN school_department school_department_0 ON (school_department_0.school_id = schools_0.id) INNER JOIN departments departments_0 ON (departments_0.id = school_department_0.department_id)",
      "result" => array(
        "schools" => array(
          0 => array("alias_name" => "School #1", "departments" => array(
              0 => array("name" => "Department #1"),
              1 => array("name" => "Department #2")
            )
          ),
          1 => array("alias_name" => "School #2", "departments" => array(
              0 => array("name" => "Department #1")
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
      "sql" => "SELECT schools_0.id, schools_0.name, departments_0.id, departments_0.name FROM schools AS schools_0 INNER JOIN school_department school_department_0 ON (school_department_0.school_id = schools_0.id) INNER JOIN departments departments_0 ON (departments_0.id = school_department_0.department_id)",
      "result" => array(
        "schools" => array(
          0 => array("name" => "School #1", "departments" => array(
              0 => array("name" => "Department #1"),
              1 => array("name" => "Department #2")
            )
          ),
          1 => array("name" => "School #2", "departments" => array(
              0 => array("name" => "Department #1")
            )
          )
        )
      )
    );

    $tests["conditions"] = array(
      "token" => array(
        "name" => "schools",
        "type" => "Segment",
        "conditions" => array(
          array("field" => "id", "operator" => "=", "value" => 12)
        )
      ),
      "sql" => "SELECT schools_0.id, schools_0.name FROM schools AS schools_0 WHERE schools_0.id = 12"
    );
    $tests["condition with AND"] = array(
      "token" => array(
        "name" => "schools",
        "type" => "Segment",
        "conditions" => array(
          array("field" => "id", "operator" => "=", "value" => 12),
          "&",
          array("field" => "name", "operator" => "=", "value" => "guilherme"),
        )
      ),
      "sql" => "SELECT schools_0.id, schools_0.name FROM schools AS schools_0 WHERE schools_0.id = 12 AND schools_0.name = \\\"guilherme\\\""
    );

    $tests["conditions with parenthesis"] = array(
      "token" => array(
        "name" => "schools",
        "type" => "Segment",
        "conditions" => array(
          array("field" => "id", "operator" => "=", "value" => 1),
          "&",
          array(
            array("field" => "id", "operator" => "=", "value" => 2),
            "|",
            array("field" => "id", "operator" => "=", "value" => 3)
          )
        )
      ),
      "sql" => "SELECT schools_0.id, schools_0.name FROM schools AS schools_0 WHERE schools_0.id = 1 AND (schools_0.id = 2 OR schools_0.id = 3)"
    );

    $tests["conditions in relations"] = array(
      "token" => array(
        "name" => "schools",
        "type" => "Segment",
        "fields" => array(
          array("name" => "id"),
          array("name" => "departments.id"),
          array("name" => "name"),
        ),
        "conditions" => array(
          array("field" => "departments.id", "operator" => "=", "value" => 1),
          "&",
          array(
            array("field" => "departments.id", "operator" => "=", "value" => 2),
            "|",
            array("field" => "departments.id", "operator" => "=", "value" => 3)
          )
        )
      ),
      "sql" => "SELECT schools_0.id, schools_0.name, departments_0.id FROM schools AS schools_0 INNER JOIN school_department school_department_0 ON (school_department_0.school_id = schools_0.id) INNER JOIN departments departments_0 ON (departments_0.id = school_department_0.department_id) WHERE departments_0.id = 1 AND (departments_0.id = 2 OR departments_0.id = 3)",
    );

    $tests["one to many join"] = array(
      "token" => array(
        "name" => "schools",
        "type" => "Segment",
        "fields" => array(
          array("name" => "id"),
          array("name" => "director.id")
        )
      ),
      "sql" => "SELECT schools_0.id, users_0.id FROM schools AS schools_0 INNER JOIN users users_0 ON (users_0.id = schools_0.director_id)",
    );


    $meta = $this->meta;

    $meta = new Meta($meta);

    $rows = array (
              array(1, "School #1", 1, "Department #1"),
              array(2, "School #2", 1, "Department #1"),
              array(1, "School #1", 2, "Department #2")
            );

    foreach ($tests as $key => $test) {
      $segment = new \fitch\fields\Segment();
      $builder = new \fitch\SegmentBuilder($meta);
      $segment = $builder->buildSegment($test["token"]);
      $generator = new \fitch\sql\QueryGenerator($segment, $meta);
      $queries = $generator->getQueries();
      $sql = $queries[0]->getSql($meta);

      $this->assertEquals($test["sql"], $sql, "SQL in OK in $key");

      $populator = new \fitch\sql\ArrayHydration($segment, $meta);

      $nested = $populator->getResult($rows);
      //print_r($nested);exit;
      if ($test["result"]) {
        $this->assertEquals($test["result"], $nested, "JSON result are OK in $key");
      }
    }
  }
}

?>