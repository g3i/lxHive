<?php

namespace Tests\Integration\Parser;

use Tests\TestCase;

use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\RequestBody;
use Slim\Http\UploadedFile;
use Psr\Http\Message\RequestInterface;

use API\Parser\RequestParser;

class RequestParserTest extends TestCase
{
    const MOCK_STATEMENT = '{"actor":{"objectType":"Agent","name":"Buster Keaton","mbox":"mailto:buster@keaton.com"},"verb":{"id":"http://adlnet.gov/expapi/verbs/voided","display":{"en-US":"voided"}},"object":{"objectType":"StatementRef","id":"{{statementId}}"}}';

    public function testSingleJsonRequest()
    {
        $mockRequest = $this->mockJsonRequest('/statements', 'POST', self::MOCK_STATEMENT);
        $parser = new RequestParser($mockRequest);

        $parserResult = $parser->getData();
        $this->assertInstanceOf('\API\Parser\ParserResult', $parserResult);

        $payload = $parserResult->getPayload();
        $this->assertInstanceOf('stdClass', $payload);
    }

    private function mockRequest($uri, $method)
    {
        $env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'CONTENT_TYPE' => 'application/json;charset=utf8'
        ]);
        $uri = Uri::createFromString($uri);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $uploadedFiles = UploadedFile::createFromEnvironment($env);
        $request = new Request($method, $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles);
        $request->registerMediaTypeParser('application/json', function ($input) {
           return json_decode($input);
        });

        return $request;
    }

    private function mockJsonRequest($uri, $method, $body)
    {
        $request = $this->mockRequest($uri, $method);
        // Write the string into the body
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $body);
        rewind($stream);
        $body = new \Slim\Http\Stream($stream);
        $request = $request->withBody($body)->reparseBody();
        return $request;
    }
}
