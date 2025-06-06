<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\JsonType;
use Cake\Database\TypeFactory;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PDO;
use stdClass;

/**
 * Test for the String type.
 */
class JsonTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\JsonType
     */
    protected $type;

    /**
     * @var \Cake\Database\Driver
     */
    protected $driver;

    /**
     * Setup
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->type = TypeFactory::build('json');
        $this->driver = $this->getMockBuilder(Driver::class)->getMock();
    }

    /**
     * Test toPHP
     */
    public function testToPHP(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));
        $this->assertSame('word', $this->type->toPHP(json_encode('word'), $this->driver));
        $this->assertSame(2.123, $this->type->toPHP(json_encode(2.123), $this->driver));
    }

    /**
     * Test converting JSON strings to PHP values.
     */
    public function testManyToPHP(): void
    {
        $values = [
            'a' => null,
            'b' => json_encode([1, 2, 3]),
            'c' => json_encode('123'),
            'd' => json_encode(2.3),
        ];
        $expected = [
            'a' => null,
            'b' => [1, 2, 3],
            'c' => 123,
            'd' => 2.3,
        ];
        $this->assertEquals(
            $expected,
            $this->type->manyToPHP($values, array_keys($values), $this->driver),
        );
    }

    /**
     * Test converting to database format
     */
    public function testToDatabase(): void
    {
        $this->assertNull($this->type->toDatabase(null, $this->driver));
        $this->assertSame(json_encode('word'), $this->type->toDatabase('word', $this->driver));
        $this->assertSame(json_encode(2.123), $this->type->toDatabase(2.123, $this->driver));
        $this->assertSame(json_encode(['a' => 'b']), $this->type->toDatabase(['a' => 'b'], $this->driver));
    }

    /**
     * Tests that passing an invalid value will throw an exception
     */
    public function testToDatabaseInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $value = fopen(__FILE__, 'r');
        $this->type->toDatabase($value, $this->driver);
    }

    /**
     * Test marshalling
     */
    public function testMarshal(): void
    {
        $this->assertNull($this->type->marshal(null));
        $this->assertSame('', $this->type->marshal(''));
        $this->assertSame('word', $this->type->marshal('word'));
        $this->assertSame(2.123, $this->type->marshal(2.123));
        $this->assertSame([1, 2, 3], $this->type->marshal([1, 2, 3]));
        $this->assertSame(['a' => 1, 2, 3], $this->type->marshal(['a' => 1, 2, 3]));
    }

    /**
     * Test that the PDO binding type is correct.
     */
    public function testToStatement(): void
    {
        $this->assertSame(PDO::PARAM_STR, $this->type->toStatement('', $this->driver));
    }

    /**
     * Test encoding options
     *
     * @return void
     */
    public function testEncodingOptions(): void
    {
        // New instance to prevent others tests breaking
        $instance = new JsonType();
        $instance->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $result = $instance->toDatabase(['é', 'https://cakephp.org/'], $this->driver);
        $this->assertSame('["é","https://cakephp.org/"]', $result);
    }

    /**
     * Test decoding options
     *
     * @return void
     */
    public function testDecodingOptions(): void
    {
        // New instance to prevent others tests breaking
        $instance = new JsonType();
        $instance->setDecodingOptions(0);

        $result = $instance->toPHP('{"foo":"bar"}', $this->driver);
        $expected = new stdClass();
        $expected->foo = 'bar';
        $this->assertEquals($expected, $result);
    }
}
