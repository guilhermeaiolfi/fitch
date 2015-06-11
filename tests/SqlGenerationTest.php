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

  protected $relation_and_field_imploded = array(
    "name" => "schools",
    "type" => "Segment",
    "ids" => null,
    "functions" => array(),
    "fields" => array(
      array(
        "name" => "id",
        "alias" => "code"
      ),
      array(
        "name" => "name"
      ),
      array(
        "name" => "departments.name"
      )
    )
  );

  protected $double = array(
    array(
      "name" => "schools",
      "type" => "Segment",
      "ids" => null,
      "functions" => array(),
      "fields" => array(
        array(
          "name" => "id",
          "alias" => "code"
        ),
        array(
          "name" => "name"
        ),
        array(
          "name" => "departments",
          "fields" => array(
            array(
              "name" => "name"
            ),
            array(
              "name" => "id",
              "alias" => "coderelation1"
            )
          )
        )
        // array(
        //   "name" => "departments.id",
        //   "alias" => "code"
        // )
        // array(
        //   "name" => "program",
        //   "fields" => array(
        //     array(
        //       "name" => "name"
        //     )
        //   )
        // ),
        // array(
        //   "name" => "director",
        //   "fields" => array(
        //     array(
        //       "name" => "name"
        //     ),
        //     array(
        //       "name" => "id"
        //     )
        //   )
        // )
      )
    ),
    array(
      "name" => "table1",
      "type" => "Segment",
      "ids" => null,
      "functions" => array(),
      "fields" => array(
        array(
          "name" => "id",
          "alias" => "code"
        ),
        array(
          "name" => "name"
        ),
        array(
          "name" => "owner.name"
        )
      ),
      "conditions" => array(
        array(
          "left" => "id",
          "operator" => "=",
          "right" => 12
        ),
        array(
          "left" => "abc",
          "operator" => "=",
          "right" => "12"
        )
      )
    ),
    array(
      "name" => "table2",
      "alias" => "users",
      "type" => "Segment",
      "ids" => null,
      "functions" => array(),
      "fields" => null,
      "conditions" => array(
        array(
          "left" => "abc.are",
          "operator" => "=",
          "right" => 12
        )
      )
    )
  );

  public function testFirstLevel()
  {
    $double = $this->double;
    $meta = $this->meta;

    $token = is_array($double)? $double[0] : $double;
    $segment = new \fitch\fields\Segment($token);

    $generator = new \fitch\sql\SqlGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);
    $expected = "SELECT schools.id AS code, schools.name, departments.name, departments.id AS coderelation1 FROM schools AS schools LEFT JOIN school_department schools_departments ON (schools_departments.school_id = schools.id)  LEFT JOIN departments departments ON (departments.id = schools_departments.department_id)";

    $this->assertEquals($sql, $expected);


    $pdo = new PDO( "mysql:dbname=htsql;host=localhost", "root", "elogical" );

    $q = $pdo->query($sql);

    $populator = new \fitch\sql\ArrayHydration($q, $segment);
    $normalized = $populator->getResult();
    //print_r($normalized);
  }

}



?>