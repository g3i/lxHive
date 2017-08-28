<?php
namespace Tests\API;

use Tests\TestCase;

use JsonSchema;
use JsonSchema\Constraints\Factory as JsonSchemaFactory;

use API\Container;
use API\Validator;
use API\Validator\JsonSchema\Constraints\Factory as CustomFactory;
use API\Validator\JsonSchema\Constraints\FormatConstraint as CustomFormatConstraint;

//TODO un-clutter, migrate CustomFactory and CustomFormat tests

class ValidatorTest extends TestCase
{
    public function testcreateSchemaValidator()
    {
        $factory = new JsonSchemaFactory();
        $v =  new Validator(new Container());
        $sv = $v->createSchemaValidator($factory);

        $this->assertInstanceOf(JsonSchema\Validator::class, $sv);
    }

    //TODO re-locate to Tests\API\Validator\JsonSchema\Constraints\Factory
    public function testCreateCustomFactory()
    {
        $factory = new CustomFactory();
        $constraints = $factory->getConstraintMap();
        $this->assertTrue(!empty($constraints));
        // check extends justinrainbow/json-schema
        $this->assertArrayHasKey('format', $constraints, '\API\Validator\JsonSchema\Constraints\Factory inherits constraintMap from \API\Validator\JsonSchema\Constraints\Factory');
    }

    /**
     * @depends testCreateCustomFactory
     */
    //TODO re-locate to Tests\API\Validator\JsonSchema\Constraints\Factory
    public function testCreateCustomFormatConstraint()
    {
        $factory = new CustomFactory();
        $constraints = $factory->getConstraintMap();

        $formatConstraint = new $constraints['format']();
        $this->assertInstanceOf(CustomFormatConstraint::class, $formatConstraint);
    }

    /**
     * @depends testCreateCustomFactory
     */
     //TODO re-locate to Tests\API\Validator\JsonSchema\Constraints\Factory
    public function testcreateSchemaValidatorWithCustomFactory()
    {
        $factory = new CustomFactory();
        $v =  new Validator(new Container());
        $sv = $v->createSchemaValidator($factory);

        $this->assertInstanceOf(JsonSchema\Validator::class, $sv);
        // check extends justinrainbow/json-schema
        $this->assertTrue(method_exists($sv, 'isValid'), 'instance inherits method "isValid" from \JsonSchema\Validator\Contstraint');
    }

    /**
     * Tests JsonSchema validation is functional
     * This might look out of scope, bu reasoning behind this it that the current JustinRainbow JSonSchema version returns zero errors (valid) if the schema is passed in empty or null
     *
     * @depends testCreateCustomFactory
     */
    public function testDefaultValidation()
    {
        $factory = new JsonSchemaFactory();
        $v =  new Validator(new Container());
        $sv = $v->createSchemaValidator($factory);

        $schema = (object) [
            "type"=>"object",
            "properties"=> (object)[
                "processRefund"=>(object)[
                    "type" => "boolean"
                ],
                "refundAmount"=> (object)[
                    "type" => "number"
                ],
                "color"=> (object)[
                    "type" => "string",
                    'format' => 'color'
                ]
            ],
        ];

        $sv->check((object)[
            'processRefund' => true,
            'refundAmount'=> 17,
            'color' => 'black'
        ], $schema);
        $this->assertTrue($sv->isValid(), 'validates with JsonSchema core');
        $sv->reset();

        $sv->check((object)[
            'processRefund' => "true",
            'refundAmount'=> 17,
            'color' => 'black'
        ], $schema);
        $this->assertFalse($sv->isValid(), 'in-validates with JsonSchema core type');
        $sv->reset();

        $sv->check((object)[
            'processRefund' => "true",
            'refundAmount'=> 17,
            'color' => 'invalid'
        ], $schema);
        $this->assertFalse($sv->isValid(), 'in-validates with JsonSchema core format');
        $sv->reset();
    }

    /**
     * Tests if both core and custom JsonSchema format are functional
     * @depends testCreateCustomFactory
     */
    //TODO re-locate to Tests\API\Validator\JsonSchema\Constraints\FormatConstraint
    public function testValidateCustomFormats()
    {
        $factory = new CustomFactory();
        $v =  new Validator(new Container());
        $sv = $v->createSchemaValidator($factory);

        $schema = (object)[
            'type'=>'object',
            'properties' => (object)[
                'iri' => (object)[
                    'type'=>'string',
                     'format'=>'iri'
                ],
                'url' => (object)[
                    'type'=>'string',
                    'format'=>'uri'
                ],
                "color"=> (object)[
                    "type" => "string",
                    'format' => 'color'
                ],
            ]
        ];

        $sv->check((object)[
            'iri' => 'https://fa.wikipedia.org/wiki/یوآرآی',
            'uri' => 'https://fa.wikipedia.org/wiki/%DB%8C%D9%88%D8%A2%D8%B1%D8%A2%DB%8C',
            'color' => 'black',
        ], $schema);
        $this->assertTrue($sv->isValid(), 'validates with JsonSchema core format (not affected)');
        $sv->reset();

        $sv->check((object)[
            'iri' => 'invalid',
            'uri' => 'https://fa.wikipedia.org/wiki/%DB%8C%D9%88%D8%A2%D8%B1%D8%A2%DB%8C',
            'color' => 'black',
        ], $schema);
        $this->assertFalse($sv->isValid(), 'in-validates with custom format "iri"');
        $sv->reset();
    }

    /**
     * Tests custom JsonSchema format "iri" validation
     *
     * @depends testValidateCustomFormats
     */
    //TODO re-locate to Tests\API\Validator\JsonSchema\Constraints\FormatConstraint
    public function testValidateCustomFormatConstraintIrI()
    {
        $factory = new CustomFactory();
        $v =  new Validator(new Container());
        $sv = $v->createSchemaValidator($factory);

        $schema = (object)[
            'type'=>'object',
            'properties' => (object)[
                'iri' => (object)[
                    'type'=>'string',
                     'format'=>'iri'
                ],
            ]
        ];

        $sv->check((object)[
            'iri' => 'https://fa.wikipedia.org/wiki/یوآرآی',
        ], $schema);
        $this->assertTrue($sv->isValid(), 'validates with custom format "iri" non-ansi uri');
        $sv->reset();

        $sv->check((object)[
            'iri' => 'https://en.wikipedia.org/wiki/Uniform_Resource_Identifier',
        ], $schema);
        $this->assertTrue($sv->isValid(), 'validates with custom format "iri" rfc2396 uri');
        $sv->reset();

        $sv->check((object)[
            'iri' => 'http://müsic.example/motörhead',
        ], $schema);
        $this->assertTrue($sv->isValid(), 'validates with JsonSchema core format (not affected)');
        $sv->reset();

        $sv->check((object)[
            'iri' => '//example.org/scheme-relative/URI/with/absolute/path/to/resource',
        ], $schema);
        $this->assertTrue($sv->isValid(), 'validates with custom format "iri" against empty uri-scheme');
        $sv->reset();

        $sv->check((object)[
            'iri' => 'urn:ISSN:1535-3613',
        ], $schema);
        $this->assertTrue($sv->isValid(), 'validates with custom format "iri" against urn');
        $sv->reset();

        $sv->check((object)[
            'iri' => '//fa.wikipedia.org',
        ], $schema);
        $this->assertTrue($sv->isValid(), 'validates with custom format "iri" against empty uri-scheme and empty path');
        $sv->reset();

        $sv->check((object)[
            'iri' => '---',
        ], $schema);
        $this->assertFalse($sv->isValid(), 'in-validates invalid custom format "iri"');
        $sv->reset();

        $sv->check((object)[
            'iri' => 23,
        ], $schema);
        $this->assertFalse($sv->isValid(), 'in-validates wrong type of custom format "iri"');
        $sv->reset();

        $sv->check((object)[
            'iri' => '',
        ], $schema);
        $this->assertFalse($sv->isValid(), 'in-validates empty custom format "iri"');
        $sv->reset();
    }

    /**
     * Tests custom JsonSchema format "uuid" validation
     *
     * @depends testValidateCustomFormats
     */
    //TODO re-locate to Tests\API\Validator\JsonSchema\Constraints\FormatConstraint
    public function testValidateCustomFormatConstraintUuid()
    {
        $factory = new CustomFactory();
        $v =  new Validator(new Container());
        $sv = $v->createSchemaValidator($factory);

        $schema = (object)[
            'type'=>'object',
            'properties' => (object)[
                'id' => (object)[
                    'type'=>'string',
                     'format'=>'uuid'
                ],
            ]
        ];

        ////
        // extends tests in https://github.com/ramsey/uuid/blob/master/src/Uuid.php
        ///

        $sv->check((object)[
            'id' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
        ], $schema);
        $this->assertTrue($sv->isValid(), 'validates with custom format "uuid" goodVersion1');
        $sv->reset();

        $sv->check((object)[
            'id' => '{ff6f8cb0-c57d-11e1-9b21-0800200c9a66}',
        ], $schema);
        $this->assertTrue($sv->isValid(), 'validates with custom format "uuid" {goodVersion1} (brackets)');
        $sv->reset();

        $sv->check((object)[
            'id' => 'urn:uuid:e05aa883-acaf-40ad-bf54-02c8ce485fb0',
        ], $schema);
        $this->assertTrue($sv->isValid(), 'validates with custom format "uuid" legacy urn version');
        $sv->reset();

        $sv->check((object)[
            'id' => '{urn:uuid:e05aa883-acaf-40ad-bf54-02c8ce485fb0}',
        ], $schema);
        $this->assertTrue($sv->isValid(), 'validates with custom format "uuid" {legacy urn version} (brackets)');
        $sv->reset();

        $sv->check((object)[
            'id' => 'invalid',
        ], $schema);
        $this->assertFalse($sv->isValid(), 'in-validates empty invalid format "uuid"');
        $sv->reset();

        $sv->check((object)[
            'id' => [],
        ], $schema);
        $this->assertFalse($sv->isValid(), 'in-validates wrong type of invalid format "uuid"');
        $sv->reset();

        $sv->check((object)[
            'id' => '',
        ], $schema);
        $this->assertFalse($sv->isValid(), 'in-validates empty custom format "uuid"');
        $sv->reset();
    }
}
