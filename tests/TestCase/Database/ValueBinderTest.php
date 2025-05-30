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
 * @since         3.3.12
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\StatementInterface;
use Cake\Database\ValueBinder;
use Cake\TestSuite\TestCase;
use Mockery;

/**
 * Tests ValueBinder class
 */
class ValueBinderTest extends TestCase
{
    /**
     * test the bind method
     */
    public function testBind(): void
    {
        $valueBinder = new ValueBinder();
        $valueBinder->bind(':c0', 'value0');
        $valueBinder->bind(':c1', 1, 'int');
        $valueBinder->bind(':c2', 'value2');

        $this->assertCount(3, $valueBinder->bindings());

        $expected = [
            ':c0' => [
                'value' => 'value0',
                'type' => null,
                'placeholder' => 'c0',
            ],
            ':c1' => [
                'value' => 1,
                'type' => 'int',
                'placeholder' => 'c1',
            ],
            ':c2' => [
                'value' => 'value2',
                'type' => null,
                'placeholder' => 'c2',
            ],
        ];

        $bindings = $valueBinder->bindings();
        $this->assertEquals($expected, $bindings);
    }

    /**
     * test the placeholder method
     */
    public function testPlaceholder(): void
    {
        $valueBinder = new ValueBinder();
        $result = $valueBinder->placeholder('?');
        $this->assertSame('?', $result);

        $valueBinder = new ValueBinder();
        $result = $valueBinder->placeholder(':param');
        $this->assertSame(':param', $result);

        $valueBinder = new ValueBinder();
        $result = $valueBinder->placeholder('p');
        $this->assertSame(':p0', $result);
        $result = $valueBinder->placeholder('p');
        $this->assertSame(':p1', $result);
        $result = $valueBinder->placeholder('c');
        $this->assertSame(':c2', $result);
    }

    public function testGenerateManyNamed(): void
    {
        $valueBinder = new ValueBinder();
        $values = [
            'value0',
            'value1',
        ];

        $expected = [
            ':c0',
            ':c1',
        ];
        $placeholders = $valueBinder->generateManyNamed($values);
        $this->assertEquals($expected, $placeholders);
    }

    /**
     * test the reset method
     */
    public function testReset(): void
    {
        $valueBinder = new ValueBinder();
        $valueBinder->bind(':c0', 'value0');
        $valueBinder->bind(':c1', 'value1');

        $this->assertCount(2, $valueBinder->bindings());
        $valueBinder->reset();
        $this->assertCount(0, $valueBinder->bindings());

        $placeholder = $valueBinder->placeholder('c');
        $this->assertSame(':c0', $placeholder);
    }

    /**
     * test the resetCount method
     */
    public function testResetCount(): void
    {
        $valueBinder = new ValueBinder();

        // Ensure the _bindings array IS NOT affected by resetCount
        $valueBinder->bind(':c0', 'value0');
        $valueBinder->bind(':c1', 'value1');
        $this->assertCount(2, $valueBinder->bindings());

        // Ensure the placeholder generation IS affected by resetCount
        $valueBinder->placeholder('param');
        $valueBinder->placeholder('param');
        $result = $valueBinder->placeholder('param');
        $this->assertSame(':param2', $result);

        $valueBinder->resetCount();

        $placeholder = $valueBinder->placeholder('param');
        $this->assertSame(':param0', $placeholder);
        $this->assertCount(2, $valueBinder->bindings());
    }

    /**
     * tests the attachTo method
     */
    public function testAttachTo(): void
    {
        $statementMock = Mockery::spy(StatementInterface::class);

        $valueBinder = new ValueBinder();
        $valueBinder->attachTo($statementMock); //empty array shouldn't call statement
        $valueBinder->bind(':c0', 'value0', 'string');
        $valueBinder->bind(':c1', 'value1', 'string');
        $valueBinder->attachTo($statementMock);

        $statementMock
            ->shouldHaveReceived('bindValue')
            ->with('c0', 'value0', 'string')
            ->once();

        $statementMock
            ->shouldHaveReceived('bindValue')
            ->with('c1', 'value1', 'string')
            ->once();
    }

    /**
     * test the __debugInfo method
     */
    public function testDebugInfo(): void
    {
        $valueBinder = new ValueBinder();

        $valueBinder->bind(':c0', 'value0');
        $valueBinder->bind(':c1', 'value1');

        $data = $valueBinder->__debugInfo();
        $this->assertArrayHasKey('bindings', $data);
        $this->assertArrayHasKey(':c0', $data['bindings']);
    }
}
