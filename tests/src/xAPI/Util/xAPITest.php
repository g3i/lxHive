<?php
namespace Tests\API\Util;

use Tests\TestCase;

use API\Util\xAPI;

class xAPITest extends TestCase
{
    public function testExtractUniqueIdentifier()
    {
        $this->assertNull(xAPI::extractUniqueIdentifier(null), 'casts arg to object (json_encode error)');

        $this->assertEquals(xAPI::extractUniqueIdentifier([
            'mbox' => 'mailto:test@unittest.test',
        ]), 'mbox', 'casts array to object');

        $this->assertNull(xAPI::extractUniqueIdentifier((object) [
            'invalid' => true,
        ]));

        $this->assertNull(xAPI::extractUniqueIdentifier((object) [
            'MBOX' => 'mailto:test@unittest.test',
        ]), 'strict name property check');

        $this->assertEquals(xAPI::extractUniqueIdentifier((object) [
            'mbox' => true,
        ]), 'mbox', 'considers only property name, does not validates the uid value');

        $this->assertEquals(xAPI::extractUniqueIdentifier((object) [
            'objectType' => 'Group',
            'mbox' => 'mailto:test@unittest.test',
            'member' => [
                (object)[
                    'mbox' => 'mailto:member@unittest.test',
                ]
            ]
        ]), 'mbox', 'includes identified groups');

        $this->assertNull(xAPI::extractUniqueIdentifier((object) [
            'member' => [
                (object)[
                    'mbox' => 'mailto:member@unittest.test',
                ]
            ]
        ]), 'excludes invalid identified groups (missing objectType)');

        // run through remaining props ("mbox" was tested above)
        // leaving prop values out deliberately to demonstrate that this is not a conformance validator method!

        $this->assertEquals(xAPI::extractUniqueIdentifier((object) [
            'mbox_sha1sum' => true,
        ]), 'mbox_sha1sum');

        $this->assertEquals(xAPI::extractUniqueIdentifier((object) [
            'account' => true,
        ]), 'account');

        $this->assertEquals(xAPI::extractUniqueIdentifier((object) [
            'openid' => true,
        ]), 'openid');
    }

    public function testExtractAgentIdentifier()
    {
        $this->assertNull(xAPI::extractAgentIdentifier(null), 'casts arg to object (json_encode error)');

        $this->assertEquals(xAPI::extractAgentIdentifier([
            'mbox' => 'mailto:test@unittest.test',
        ]), 'mbox', 'casts array to object');

        $this->assertNull(xAPI::extractAgentIdentifier((object) [
            'invalid' => true,
        ]));

        $this->assertNull(xAPI::extractAgentIdentifier((object) [
            'MBOX' => 'mailto:test@unittest.test',
        ]), 'strict name property check');

        $this->assertEquals(xAPI::extractAgentIdentifier((object) [
            'mbox' => true,
        ]), 'mbox', 'considers only property name, does not validates the uid value');

        $this->assertNull(xAPI::extractAgentIdentifier((object) [
            'objectType' => 'Group',
            'mbox' => 'mailto:test@unittest.test',
            'member' => [
                (object)[
                    'mbox' => 'mailto:member@unittest.test',
                ]
            ]
        ]), 'excludes identified groups');

        $this->assertNull(xAPI::extractAgentIdentifier((object) [
            'mbox' => 'mailto:test@unittest.test',
            'member' => [
                (object)[
                    'mbox' => 'mailto:member@unittest.test',
                ]
            ]
        ]), 'excludes invalid identified groups (missing objectType)');
    }
}
