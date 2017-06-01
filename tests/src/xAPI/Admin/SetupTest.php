<?php
namespace Tests\API;

use Tests\TestCase;

use API\Bootstrap;
use API\Admin\Setup;

class SetupTest extends TestCase
{

    protected function setUp()
    {
        Bootstrap::reset();
        Bootstrap::factory(Bootstrap::Testing);
    }

    public function testvalidatePassword()
    {
        $admin = new Setup();
        $admin->validatePassword('ValidPass999!');

        $this->expectException(\RuntimeException::class);
        $admin->validatePassword('Val'); // too short
        $admin->validatePassword('ValidPass!'); // requires number
        $admin->validatePassword('ValidPass999'); // requires non alphaNumeric
    }

    public function testValidatePermissionsInput()
    {
        $admin = new Setup();
        $permissions = ['one', 'two', 'third'];

        $admin->validatePermissionsInput( 0, $permissions);
        $admin->validatePermissionsInput('0', $permissions);
        $admin->validatePermissionsInput('0, 2', $permissions);

        $this->expectException(\RuntimeException::class);
        $admin->validatePermissionsInput(5, $permissions); // non-existing index
        $admin->validatePermissionsInput('1.2', $permissions); // wrong delimiter
        $admin->validatePermissionsInput('1,,2', $permissions); // wrong delimiter
        $admin->validatePermissionsInput('two', $permissions);
        $admin->validatePermissionsInput('0, a', $permissions);
        $admin->validatePermissionsInput('[0', $permissions); // invalid json
        $admin->validatePermissionsInput([], $permissions);
    }

}
