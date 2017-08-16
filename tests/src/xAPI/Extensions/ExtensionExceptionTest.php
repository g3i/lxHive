<?php
namespace Tests\Extensions;

use Tests\TestCase;


use API\HttpException;
use API\Extensions\ExtensionException;

class ExtensionExceptionTest extends TestCase
{
    public function testExtendsHttpException()
    {
        $data = new \DateTime();

        $ex = new ExtensionException('Test', 500, $data);
        $gets = $ex->getData();
        $this->assertEquals($gets, $data, 'inherits public method HttpException::getData()');

        $this->expectException(HttpException::class);
        throw $ex;
    }

}
