<?php
namespace Tests\API;

use Tests\TestCase;

use API\Bootstrap;
use API\Admin\Setup;

class SetupTest extends TestCase
{

    public function testvalidatePassword()
    {
        $admin = new Setup();
        $admin->validatePassword('ValidPass999!');

        $this->expectException(\RuntimeException::class);
        $admin->validatePassword('Val'); // too short
        $admin->validatePassword('ValidPass!'); // requires number
        $admin->validatePassword('ValidPass999'); // requires non alphaNumeric
    }

}
