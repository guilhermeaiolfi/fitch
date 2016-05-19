<?php

use \fitch\Meta as Meta;
use \fitch\parser\Parser as Parser;
error_reporting(E_ALL ^ E_NOTICE);
class NestedQueryGenerationTest extends PHPUnit_Framework_TestCase
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
          "cardinality" => "many",
          "on" => array(
            "schools.id" => "programs.school_id"
          )
        ),
        "departments" => array(
          "table" => "departments",
          "cardinality" => "many",
          "on" => array(
            "schools.id" => "school_department.school_id",
            "school_department.department_id" => "departments.id"
          )
        )
      )
    ),
    "programs" => array(
      "fields" => array("id"),
      "primary_key" => "id"
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
            "departments.id" => "department_course.department_id",
            "department_course.course_id" => "courses.id"
          )
        )
      )
    ),
    "courses" => array(
      "primary_key" => "id",
      "fields" => array("id", "name"),
      "foreign_keys" => array(
        "departments" => array(
          "table" => "departments",
          "cardinality" => "many",
          "on" => array(
            "courses.id" => "department_course.course_id",
            "department_course.department_id" => "departments.id"
          )
        )
      )
    ),
    "users" => array(
      "primary_key" => "id",
      "fields" => array("id", "name"),
      "foreign_keys" => array(
        "schools" => array(
          "cardinality" => "many",
          "table" => "schools",
          "on" => array(
            "users.id" => "schools.director_id"
          )
        )
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
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);

    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($segment, $meta);

    $nested = $populator->getResult($rows);

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
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);
    //print_r($segment->getMapping());exit;
    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($segment, $meta);

    $nested = $populator->getResult($rows);

    $result = array (
      "schools" => array (
        0 => array("name" => "School #1", "id" => 1, "departments.id" => array (0 => 1, 1 => 2)),
        1 => array("name" => "School #2", "id" => 2, "departments.id" => array (0 => 1))
      )
    );

    $this->assertEquals($result, $nested);
  }

  public function testOneToOne()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $rows = array (
      array(1, "School #1", 1, "Guilherme"),
      array(2, "School #2", 2, "Godofredo"),
    );

    $ql = "/schools{id, name, director{name}}";

    $segment = $parser->parse($ql);
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);
    //print_r($segment->getMapping());exit;
    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($segment, $meta);

    $nested = $populator->getResult($rows);

    $sql_expected = "SELECT schools_0.id, schools_0.name, users_1.id AS users_1_id, users_1.name AS users_1_name FROM schools AS schools_0 INNER JOIN (SELECT users_0.id, users_0.name FROM users AS users_0) users_1 ON (users_1.id = schools_0.director_id)";

    $this->assertEquals($sql_expected, $sql);
    $result = array (
      "schools" => array (
        0 => array("id" => 1, "name" => "School #1", "director" => array ("name" => "Guilherme")),
        1 => array("id" => 2, "name" => "School #2", "director" => array ("name" => "Godofredo"))
      )
    );

    $this->assertEquals($result, $nested);
  }

  public function testInvalidField()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $rows = array (
      array(1, "School #1"),
      array(2, "School #2"),
    );

    $ql = "/schools{id, name, NON_EXISTENT}";

    $segment = $parser->parse($ql);
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);
    //print_r($segment->getMapping());exit;
    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($segment, $meta);

    $nested = $populator->getResult($rows);

    $sql_expected = "SELECT schools_0.id, schools_0.name FROM schools AS schools_0";

    $this->assertEquals($sql_expected, $sql);
    $result = array (
      "schools" => array (
        0 => array("id" => 1, "name" => "School #1"),
        1 => array("id" => 2, "name" => "School #2")
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
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);

    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($segment, $meta);

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
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);

    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($segment, $meta);

    $nested = $populator->getResult($rows);

    $sql_expected = "SELECT schools_0.id, departments_1.id AS departments_1_id FROM schools AS schools_0 INNER JOIN school_department school_department_0 ON (school_department_0.school_id = schools_0.id) INNER JOIN (SELECT departments_0.id FROM departments AS departments_0) departments_1 ON (departments_1.id = school_department_0.department_id) WHERE departments_1.id = 1";

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
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);

    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($segment, $meta);

    $nested = $populator->getResult($rows);

    $sql_expected = 'SELECT schools_0.id, departments_1.id AS departments_1_id, departments_1.courses_1_id AS departments_1_courses_1_id FROM schools AS schools_0 INNER JOIN school_department school_department_0 ON (school_department_0.school_id = schools_0.id) INNER JOIN (SELECT departments_0.id, courses_1.id AS courses_1_id FROM departments AS departments_0 INNER JOIN department_course department_course_0 ON (department_course_0.department_id = departments_0.id) INNER JOIN (SELECT courses_0.id FROM courses AS courses_0) courses_1 ON (courses_1.id = department_course_0.course_id)) departments_1 ON (departments_1.id = school_department_0.department_id)';

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
      array(1, "Name #1", 1),
      array(2, "Name #2", 1),
      array(1, "Name #1", 2)
    );

    $ql = "/schools{name,departments.courses.id}";

    $segment = $parser->parse($ql);
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);

    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $populator = new \fitch\sql\ArrayHydration($segment, $meta);

    $nested = $populator->getResult($rows);

    $sql_expected = 'SELECT schools_0.id, schools_0.name, departments_1.courses_1_id AS departments_1_courses_1_id FROM schools AS schools_0 INNER JOIN school_department school_department_0 ON (school_department_0.school_id = schools_0.id) INNER JOIN (SELECT courses_1.id AS courses_1_id FROM departments AS departments_0 INNER JOIN department_course department_course_0 ON (department_course_0.department_id = departments_0.id) INNER JOIN (SELECT courses_0.id FROM courses AS courses_0) courses_1 ON (courses_1.id = department_course_0.course_id)) departments_1 ON (departments_1.id = school_department_0.department_id)';

    $this->assertEquals($sql_expected, $sql);

    $result = array (
      "schools" => array (
        0 => array("name" => "Name #1", "departments.courses.id" => array(1, 2)),
        1 => array("name" => "Name #2", "departments.courses.id" => array(1))
      )
    );
    $this->assertEquals($result, $nested);
  }

  public function testOneToMany()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $ql = "/schools{programs.id}";

    $segment = $parser->parse($ql);
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);

    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);

    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $sql_expected = "SELECT schools_0.id, programs_1.id AS programs_1_id FROM schools AS schools_0 INNER JOIN (SELECT programs_0.id FROM programs AS programs_0) programs_1 ON (programs_1.school_id = schools_0.id)";

    $this->assertEquals($sql_expected, $sql);

    $populator = new \fitch\sql\ArrayHydration($segment, $meta);

    $rows = array (
      array(1, 2),
      array(1, 3)
    );

    $nested = $populator->getResult($rows);

    $result = array (
      "schools" => array (
        0 => array("programs.id" => array(2, 3))
      )
    );

    $this->assertEquals($result, $nested);
  }

  public function testComposedSegment()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $ql = "/schools.departments";

    $segment = $parser->parse($ql);
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);
    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);

    $relation = NULL;
    $children = $segment->getChildren();
    foreach ($children as $child) {
      if ($child instanceof \fitch\fields\Relation) {
        $relation = $child;
        break;
      }
    }
    $queries = $generator->getQueries();
    $sql = $queries[0]->getSql($meta);

    $sql_expected = "SELECT schools_0.id, departments_1.id AS departments_1_id, departments_1.name AS departments_1_name FROM schools AS schools_0 INNER JOIN school_department school_department_0 ON (school_department_0.school_id = schools_0.id) INNER JOIN (SELECT departments_0.id, departments_0.name FROM departments AS departments_0) departments_1 ON (departments_1.id = school_department_0.department_id)";

    $this->assertEquals($sql_expected, $sql);

    $populator = new \fitch\sql\ArrayHydration($segment, $meta);

    $rows = array (
      array(1, 2, "Department #1"),
      array(2, 3, "Department #2")
    );

    $nested = $populator->getResult($rows);

    $result = array (
      "schools.departments" => array (
        0 => array("id" => 2, "name" => "Department #1"),
        1 => array("id" => 3, "name" => "Department #2")
      )
    );

    $this->assertEquals($result, $nested);
  }


  public function testNestedManyRelation()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $ql = "/schools{name, departments{courses}}";

    $segment = $parser->parse($ql);
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);
    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);

    $queries = $generator->getQueries();
    $sql_expected = 'SELECT schools_0.id, schools_0.name, departments_1.id AS departments_1_id, departments_1.courses_1_id AS departments_1_courses_1_id, departments_1.courses_1_name AS departments_1_courses_1_name FROM schools AS schools_0 INNER JOIN school_department school_department_0 ON (school_department_0.school_id = schools_0.id) INNER JOIN (SELECT departments_0.id, courses_1.id AS courses_1_id, courses_1.name AS courses_1_name FROM departments AS departments_0 INNER JOIN department_course department_course_0 ON (department_course_0.department_id = departments_0.id) INNER JOIN (SELECT courses_0.id, courses_0.name FROM courses AS courses_0) courses_1 ON (courses_1.id = department_course_0.course_id)) departments_1 ON (departments_1.id = school_department_0.department_id)';

    $sql = $queries[0]->getSql();
    $this->assertEquals($sql_expected, $sql);

    $populator = new \fitch\sql\ArrayHydration($segment, $meta);

    $results = array(
      array(1, "School #1", 1, 1, "Computer Science"),
      array(2, "School #2", 1, 1, "Computer Science")
    );

    $nested = $populator->getResult($results);
    //print_r($nested);
    $result = array (
      "schools" => array (
        0 => array(
          "name" => "School #1",
          "departments" => array(
            0 => array(
              "courses" => array(
                0 => array("id" => 1, "name" => "Computer Science")
              )
            )
          )
        ),
        1 => array(
            "name" => "School #2",
            "departments" => array(
              0 => array(
                "courses" => array(
                  0 => array("id" => 1, "name" => "Computer Science")
                )
              )
            )
          )
      )
    );
    //print_r($nested);
    $this->assertEquals($result, $nested);
  }

  public function testNestedManyRelationWithInnerCondition()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $ql = "/schools{name, departments{courses}}"; // TODO: /schools{name, departments?id=2{courses}}

    $segment = $parser->parse($ql);
    $segment["fields"][1]["conditions"] = array("field" => "id", "operator" => "=", "value" => 2);
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);
    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);

    $queries = $generator->getQueries();
    $sql_expected = 'SELECT schools_0.id, schools_0.name, departments_1.id AS departments_1_id, departments_1.courses_1_id AS departments_1_courses_1_id, departments_1.courses_1_name AS departments_1_courses_1_name FROM schools AS schools_0 INNER JOIN school_department school_department_0 ON (school_department_0.school_id = schools_0.id) INNER JOIN (SELECT departments_0.id, courses_1.id AS courses_1_id, courses_1.name AS courses_1_name FROM departments AS departments_0 INNER JOIN department_course department_course_0 ON (department_course_0.department_id = departments_0.id) INNER JOIN (SELECT courses_0.id, courses_0.name FROM courses AS courses_0) courses_1 ON (courses_1.id = department_course_0.course_id) WHERE departments_0.id = 2) departments_1 ON (departments_1.id = school_department_0.department_id)';

    $sql = $queries[0]->getSql();
    $this->assertEquals($sql_expected, $sql);
  }*/

  public function testNestedSideBySide()
  {
    $meta = $this->meta;
    $meta = new Meta($meta);

    $parser = new Parser();

    $ql = "/departments{name, schools{name}, courses{name}}";

    $segment = $parser->parse($ql);
    $builder = new \fitch\SegmentBuilder($meta);
    $segment = $builder->buildSegment($segment);
    $generator = new \fitch\sql\NestedQueryGenerator($segment, $meta);

    $queries = $generator->getQueries();
    $sql_expected = 'SELECT departments_0.id, departments_0.name, schools_1.id AS schools_1_id, schools_1.name AS schools_1_name, courses_1.id AS courses_1_id, courses_1.name AS courses_1_name FROM departments AS departments_0 INNER JOIN school_department school_department_0 ON (school_department_0.department_id = departments_0.id) INNER JOIN (SELECT schools_0.id, schools_0.name FROM schools AS schools_0) schools_1 ON (schools_1.id = school_department_0.school_id) INNER JOIN department_course department_course_0 ON (department_course_0.department_id = departments_0.id) INNER JOIN (SELECT courses_0.id, courses_0.name FROM courses AS courses_0) courses_1 ON (courses_1.id = department_course_0.course_id)';

    $sql = $queries[0]->getSql();
    $this->assertEquals($sql_expected, $sql);

    $populator = new \fitch\sql\ArrayHydration($segment, $meta);

    $results = array(
      array(1, 'Department #1', 1, 'School #1', 1, "Computer Science"),
      array(1, 'Department #1', 2, 'School #2', 1, "Computer Science")
    );

    $nested = $populator->getResult($results);
    print_r($nested);exit;
    $result = array (
      "departments" => array (
        0 => array(
          "name" => "Department #1",
          "schools" => array(
            0 => array(
              "name" => "School #1"
            ),
            1 => array(
              "name" => "School #2"
            )
          ),
          "courses" => array(
            0 => array(
              "name" => "Computer Science"
            )
          )
        )
      )
    );
    //print_r($nested);
    $this->assertEquals($result, $nested);
  }
}

?>