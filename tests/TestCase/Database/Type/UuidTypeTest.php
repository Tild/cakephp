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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Driver;
use Cake\Database\TypeFactory;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Test for the Uuid type.
 */
class UuidTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\UuidType
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
        $this->type = TypeFactory::build('uuid');
        $this->driver = $this->getMockBuilder(Driver::class)->getMock();
    }

    /**
     * Test toPHP
     */
    public function testToPHP(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));

        $result = $this->type->toPHP('some data', $this->driver);
        $this->assertSame('some data', $result);

        $result = $this->type->toPHP(2, $this->driver);
        $this->assertSame('2', $result);
    }

    /**
     * Test converting to database format
     */
    public function testToDatabase(): void
    {
        $result = $this->type->toDatabase('some data', $this->driver);
        $this->assertSame('some data', $result);

        $result = $this->type->toDatabase(2, $this->driver);
        $this->assertSame('2', $result);

        $result = $this->type->toDatabase(null, $this->driver);
        $this->assertNull($result);

        $result = $this->type->toDatabase('', $this->driver);
        $this->assertNull($result);

        $result = $this->type->toDatabase(false, $this->driver);
        $this->assertNull($result);
    }

    /**
     * Test that the PDO binding type is correct.
     */
    public function testToStatement(): void
    {
        $this->assertSame(PDO::PARAM_STR, $this->type->toStatement('', $this->driver));
    }

    /**
     * Test generating new ids
     */
    public function testNewId(): void
    {
        $one = $this->type->newId();
        $two = $this->type->newId();

        $this->assertNotEquals($one, $two, 'Should be different values');
        $this->assertMatchesRegularExpression('/^[a-f0-9-]+$/', $one, 'Should quack like a uuid');
        $this->assertMatchesRegularExpression('/^[a-f0-9-]+$/', $two, 'Should quack like a uuid');
    }

    /**
     * Tests that marshalling an empty string results in null
     */
    public function testMarshal(): void
    {
        $this->assertNull($this->type->marshal(''));
        $this->assertSame('2', $this->type->marshal(2));
        $this->assertSame('word', $this->type->marshal('word'));
        $this->assertNull($this->type->marshal([1, 2]));
    }
}
