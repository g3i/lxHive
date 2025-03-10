<?php
namespace Tests\API\Util;

use Tests\TestCase;

use API\Util\Parser;

class ParserTest extends TestCase
{
    public function testIsApplicationJson()
    {
        $this->assertFalse(Parser::isApplicationJson(null));
        $this->assertFalse(Parser::isApplicationJson([]));
        $this->assertFalse(Parser::isApplicationJson(''));
        $this->assertFalse(Parser::isApplicationJson('json'));

        $this->assertTrue(Parser::isApplicationJson('application/json'));
        $this->assertTrue(Parser::isApplicationJson('application/json;charset=utf-8'));
        $this->assertTrue(Parser::isApplicationJson('Content-Type: application/json'));
        $this->assertTrue(Parser::isApplicationJson('Content-Type: application/json;charset=utf-8'));

    }

}
