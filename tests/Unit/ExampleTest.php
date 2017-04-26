<?php
namespace Tests\Unit;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testOK()
    {
        $this->assertTrue(true);
    }

    public static function check()
    {
        echo 'test';
        die();
    }
}
