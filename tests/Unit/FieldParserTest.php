<?php

namespace AutoCrud\Tests\Unit;

use AutoCrud\Generators\FieldParser;
use AutoCrud\Tests\TestCase;

class FieldParserTest extends TestCase
{
    protected FieldParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new FieldParser();
    }

    public function test_it_can_parse_simple_field(): void
    {
        $fields = $this->parser->parse('name:string');

        $this->assertCount(1, $fields);
        $this->assertEquals('name', $fields[0]['name']);
        $this->assertEquals('string', $fields[0]['type']);
    }

    public function test_it_can_parse_multiple_fields(): void
    {
        $fields = $this->parser->parse('name:string,email:string,age:integer');

        $this->assertCount(3, $fields);
        $this->assertEquals('name', $fields[0]['name']);
        $this->assertEquals('email', $fields[1]['name']);
        $this->assertEquals('age', $fields[2]['name']);
    }

    public function test_it_can_parse_nullable_modifier(): void
    {
        $fields = $this->parser->parse('description:text:nullable');

        $this->assertCount(1, $fields);
        $this->assertTrue($fields[0]['nullable']);
    }

    public function test_it_can_parse_unique_modifier(): void
    {
        $fields = $this->parser->parse('email:string:unique');

        $this->assertCount(1, $fields);
        $this->assertTrue($fields[0]['unique']);
    }

    public function test_it_returns_empty_array_for_empty_input(): void
    {
        $fields = $this->parser->parse('');

        $this->assertCount(0, $fields);
    }
}
