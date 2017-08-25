<?php
namespace Tests\API\Util;

use Tests\TestCase;

use API\Util\xAPI;

class xAPITest extends TestCase
{
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
