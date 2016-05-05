<?php

use \fitch\parser\Parser as Parser;
error_reporting(E_ALL ^ E_NOTICE);
class ParserTest extends PHPUnit_Framework_TestCase
{
  public function testParser()
  {
    try
    {
        $parser = new Parser;
        $result = $parser->parse("/school");
        $expected = array(
          "name"=> "school",
          "type"=> "Segment",
          "ids" => NULL,
          "functions"=> array(),
          "fields"=> NULL,
          "conditions"=> NULL
        );
        $this->assertEquals($result, $expected);
    }
    catch (PhpPegJs\SyntaxError $ex)
    {
        // Handle parsing error
        // [...]
    }
  }
  public function testSingleConditionParser()
  {
    try
    {
        $parser = new Parser;
        $result = $parser->parse("/school?(c=12)&a=1");
        $expected = array(
          "name"=> "school",
          "type"=> "Segment",
          "ids" => NULL,
          "functions"=> array(),
          "fields"=> NULL,
          "conditions"=> array(
            array(
              array("field" => "c", "operator" => "=", "value" => 12)
            ),
            "&",
            array("field" => "a", "operator" => "=", "value" => 1)
          )
        );
        $this->assertEquals($result, $expected);
    }
    catch (PhpPegJs\SyntaxError $ex)
    {
        // Handle parsing error
        // [...]
    }
  }

}



?>