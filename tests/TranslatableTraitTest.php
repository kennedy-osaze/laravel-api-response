<?php

namespace KennedyOsaze\LaravelApiResponse\Tests;

use KennedyOsaze\LaravelApiResponse\Tests\Fakes\TranslatableDummyClass;

class TranslatableTraitTest extends TestCase
{
    private TranslatableDummyClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->class = new TranslatableDummyClass();
    }

    /**
     * @dataProvider getTranslatableStringProvider
     */
    public function testStringCanBeParsedAndTranslatableToParameterArray($input, $output)
    {
        $result = $this->class->parseStringToTranslationParameters($input);

        $this->assertSame($output, $result);
    }

    public function getTranslatableStringProvider()
    {
        return [
            ['', ['name' => '', 'attributes' => []]],
            ['string', ['name' => 'string', 'attributes' => []]],
            ['string:key1', ['name' => 'string', 'attributes' => ['key1' => '']]],
            ['string:key1=', ['name' => 'string', 'attributes' => ['key1' => '']]],
            ['string:key1=value1', ['name' => 'string', 'attributes' => ['key1' => 'value1']]],
            ['string:key1=value1|', ['name' => 'string', 'attributes' => ['key1' => 'value1']]],
            ['string:key1=value1|key2=value2', ['name' => 'string', 'attributes' => ['key1' => 'value1', 'key2' => 'value2']]],
            ['api-response::string', ['name' => 'api-response::string', 'attributes' => []]],
            ['api-response::string:key1=value1', ['name' => 'api-response::string', 'attributes' => ['key1' => 'value1']]],
        ];
    }

    /**
     * @dataProvider getTranslatableArrayProvider
     */
    public function testTranslatableParameterArrayCanBeConvertedToString($string, $attributes, $output)
    {
        $result = $this->class->transformToTranslatableString($string, $attributes);

        $this->assertSame($output, $result);
    }

    public function getTranslatableArrayProvider()
    {
        return [
            ['', [], ''],
            ['string', ['key1' => null], 'string'],
            ['string', [], 'string'],
            ['string', ['key1' => 'value1'], 'string:key1=value1'],
            ['string', ['key1' => 'value1', 'key2' => 'value2'], 'string:key1=value1|key2=value2'],
        ];
    }

    /**
     * @dataProvider getTranslationProvider
     */
    public function testGetTranslatedStringArray($key, $attributes, $prefix, $output)
    {
        $prefix = $prefix ? 'api-response::'.$prefix : $prefix;

        $result = $this->class->getTranslatedStringArray($key, $attributes, $prefix);

        $this->assertSame($output, $result);
    }

    public function getTranslationProvider()
    {
        return [
            ['', [], null, ['key' => '', 'message' => '']],
            ['example_code', [], null, ['key' => null, 'message' => 'example_code']],
            ['example_code', ['status' => 'dummy'], null, ['key' => null, 'message' => 'example_code']],
            ['example_code', ['status' => 'dummy'], 'success', ['key' => 'example_code', 'message' => 'Example success message, dummy']],
        ];
    }

    /**
     * @dataProvider getIsTranslationKeyProvider
     */
    public function testIsTranslationKeyReturnsOutputCorrectly($input, $output)
    {
        $result = $this->class->isTranslationKey($input);

        $this->assertSame($output, $result);
    }

    public function getIsTranslationKeyProvider()
    {
        return [
            ['', false],
            ['dummy message', false],
            ['api-response::errors.example_code', true],
            ['api-response::errors.error_code.error_code_name', true],
        ];
    }
}
