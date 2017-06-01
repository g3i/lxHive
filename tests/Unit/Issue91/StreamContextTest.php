<?php
namespace Tests\Unit\Issue91;

use Tests\TestCase;

require('Stream.php');

class StreamContextTest extends TestCase
{
    protected $lrs;
    protected $mbox;

    protected function setUp()
    {

        if(!class_exists('\Tests\Config')){
            $this->markTestIncomplete(
              'class \Tests\Config does not exist or is invalid.'
            );
            return;
        }

        $this->lrs = \Tests\Config::$lrs['production'];

        $this->stream = new \Stream(
            $this->lrs['baseuri'],
            $this->lrs['version'],
            $this->lrs['user'],
            $this->lrs['password']
        );
    }

    public function testPostWithFullUri()
    {
        $json = '{
            "actor":{
                "mbox":"mailto:lxunit@lxhive.com"
            },
            "verb":{
                "id":"http://adlnet.gov/expapi/verbs/attempted"
            },
            "object":{
                "id":"http://lxhive.com/activities/lxunit/streamcontexttest"
            }
        }';

        $res = $this->stream->postJson('/statements', $json, [
            'request_fulluri' => 1
        ]);
        $data = json_decode($res['content']);
        $statementId = $data[0];

        $this->assertEquals(json_last_error(), \JSON_ERROR_NONE);
        $this->assertEquals(substr_count($statementId, '-'), 4);
        $this->assertEquals($res['options']['request_fulluri'], 1);
        $this->assertFalse($res['meta']['timed_out']);
        $this->assertContains('200 OK', $res['meta']['wrapper_data'][0]);
    }

    /**
     * @depends testPostWithFullUri
     */
    public function testGetWithFullUri()
    {
        $res = $this->stream->getJson('/statements?limit=2', [
            'request_fulluri' => 1
        ]);
        $data = json_decode($res['content']);

        $this->assertEquals(json_last_error(), \JSON_ERROR_NONE);
        $this->assertEquals($res['options']['request_fulluri'], 1);
        $this->assertFalse($res['meta']['timed_out']);
        $this->assertContains('200 OK', $res['meta']['wrapper_data'][0]);
    }
}
