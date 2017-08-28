<?php
namespace Tests\API\Admin;

use Tests\TestCase;

use API\Config;
use API\Admin\Validator;
use API\Admin\AdminException;

class ValidatorTest extends TestCase
{
    public function testvalidateName()
    {
        $v = new Validator();
        $v->validateName('valid');
        $v->validateName('valid whitespace');
        $v->validateName('valid.chars');

        $this->expectException(AdminException::class);
        $v->validateName('');
        $v->validateName(false); // empty space
        $v->validateName(2);
        $v->validateName('a');
    }

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

    public function testValidateXapiPermissions()
    {
        $available = Config::get(['xAPI', 'supported_auth_scopes'], []);

        $v = new Validator();
        $v->validateXapiPermissions(['all'], $available);
        $v->validateXapiPermissions(['all', 'statements/read'], $available);

        $this->expectException(AdminException::class);
        $v->validateXapiPermissions([], $available);
        $v->validateXapiPermissions(['invalid'], $available);
        $v->validateXapiPermissions(['invalid', 'statements/read'], $available);
    }

    public function testValidateRedirectUri()
    {
        $v = new Validator();
        $v->validateRedirectUri('http://test');

        $this->expectException(AdminException::class);
        $v->validateRedirectUri('');
        $v->validateRedirectUri(true);
        $v->validateRedirectUri('invalid');
        $v->validateRedirectUri('//');
        $v->validateRedirectUri('//test');
    }

    /**
     * @see https://docs.mongodb.com/manual/reference/limits/
     */
    public function testValidateMongoName()
    {
        $v = new Validator();
        $v->validateMongoName('valid');
        $v->validateMongoName('valid_underscore');
        $v->validateMongoName('valid-dash');
        $v->validateMongoName('valid999');

        $this->expectException(AdminException::class);
        $v->validateMongoName('');
        $v->validateMongoName(true);
        $v->validateMongoName('s');
        $v->validateMongoName('IsLlongerThan64BgZEz7U1CDItpbSnnxYWP9pmKLIW46XGaWJau18sLwqUNgc8aLtCDlXCw9IwsKgx');
        $v->validateMongoName('.noPointsAllowed');
        $v->validateMongoName('$noDollarAllowed');
        $v->validateMongoName('no WhitespaceAllowed');
        $v->validateMongoName('/noSlashAllowed');
        $v->validateMongoName('\\noBackSlashAllowd');
        $v->validateMongoName('"noQuoteAllowed"');
        $v->validateMongoName('*noStarAllowed"');
        $v->validateMongoName('*noStarAllowed"');
        $v->validateMongoName('>noTagAllowed');
        $v->validateMongoName('<noTagAllowed');
        $v->validateMongoName(':noColonAllowed');
        $v->validateMongoName('|noPipeAllowed');
        $v->validateMongoName('noQuestionmarkAllowed?');
    }
}
