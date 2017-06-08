<?php
namespace Tests\API;

use Tests\TestCase;

use API\Config;
use API\Admin\Validator;
use API\Admin\AdminException;

class ValidatorTest extends TestCase
{

    public function testValidateEmail()
    {
        $v = new Validator();
        $v->validateEmail('valid@email.com');

        $this->expectException(AdminException::class);
        $v->validateEmail('invalid');
        $v->validateEmail(' valid@email.com'); // empty space
        $v->validateEmail('invalid@');
    }

    public function testvalidatePassword()
    {
        $v = new Validator();
        $v->validatePassword('ValidPass999!');

        $this->expectException(AdminException::class);
        $v->validatePassword('Val'); // too short
        $v->validatePassword('ValidPass!'); // requires number
        $v->validatePassword('ValidPass999'); // requires non alphaNumeric
    }

    public function validateXapiPermissions()
    {
        $available = Config::get(['xAPI', 'supported_auth_scopes'], []);

        $v = new Validator();
        $v->validateXapiPermissions(['all'], $available);
        $v->validateXapiPermissions(['all', 'statements/read'], $available);

        $this->expectException(AdminException::class);
        $v->validateXapiPermissions('not an array', $available);
        $v->validateXapiPermissions([], $available);
        $v->validateXapiPermissions(['invalid'], $available);
        $v->validateXapiPermissions(['invalid', 'statements/read'], $available);
    }

}
