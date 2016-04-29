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
        //var_dump($result);exit;
        $expected = array(
          "name"=> 0,
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

}



?>