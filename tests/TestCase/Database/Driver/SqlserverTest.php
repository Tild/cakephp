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

namespace Cake\Test\TestCase\Database\Driver;

use Cake\Database\Connection;
use Cake\Database\Driver\Sqlserver;
use Cake\Database\DriverFeatureEnum;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Query\InsertQuery;
use Cake\Database\Query\SelectQuery;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Mockery;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests Sqlserver driver
 */
class SqlserverTest extends TestCase
{
    /**
     * @var bool
     */
    protected $missingExtension;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->missingExtension = !defined('PDO::SQLSRV_ENCODING_UTF8');
    }

    /**
     * data provider for testDnsString
     *
     * @return array
     */
    public static function dnsStringDataProvider(): array
    {
        return [
            [
                [
                    'app' => 'CakePHP-Testapp',
                    'encoding' => '',
                    'connectionPooling' => true,
                    'failoverPartner' => 'failover.local',
                    'loginTimeout' => 10,
                    'multiSubnetFailover' => 'failover.local',
                ],
                'sqlsrv:Server=localhost\SQLEXPRESS;Database=cake;MultipleActiveResultSets=false;APP=CakePHP-Testapp;ConnectionPooling=1;Failover_Partner=failover.local;LoginTimeout=10;MultiSubnetFailover=failover.local',
            ],
            [
                [
                    'app' => 'CakePHP-Testapp',
                    'encoding' => '',
                    'failoverPartner' => 'failover.local',
                    'multiSubnetFailover' => 'failover.local',
                ],
                'sqlsrv:Server=localhost\SQLEXPRESS;Database=cake;MultipleActiveResultSets=false;APP=CakePHP-Testapp;Failover_Partner=failover.local;MultiSubnetFailover=failover.local',
            ],
            [
                [
                    'encoding' => '',
                ],
                'sqlsrv:Server=localhost\SQLEXPRESS;Database=cake;MultipleActiveResultSets=false',
            ],
            [
                [
                    'app' => 'CakePHP-Testapp',
                    'encoding' => '',
                    'host' => 'localhost\SQLEXPRESS',
                    'port' => 9001,
                ],
                'sqlsrv:Server=localhost\SQLEXPRESS,9001;Database=cake;MultipleActiveResultSets=false;APP=CakePHP-Testapp',
            ],
        ];
    }

    /**
     * Test if all options in dns string are set
     *
     * @param array $constructorArgs
     * @param string $dnsString
     */
    #[DataProvider('dnsStringDataProvider')]
    public function testDnsString($constructorArgs, $dnsString): void
    {
        $driver = $this->getMockBuilder(Sqlserver::class)
            ->onlyMethods(['createPdo'])
            ->setConstructorArgs([$constructorArgs])
            ->getMock();

        $driver->method('createPdo')
            ->with($this->callback(function ($dns) use ($dnsString) {
                $this->assertSame($dns, $dnsString);

                return true;
            }));

        $driver->connect();
    }

    /**
     * Test connecting to Sqlserver with custom configuration
     */
    public function testConnectionConfigCustom(): void
    {
        $this->skipIf($this->missingExtension, 'pdo_sqlsrv is not installed.');
        $config = [
            'host' => 'foo',
            'username' => 'Administrator',
            'password' => 'blablabla',
            'database' => 'bar',
            'encoding' => 'a-language',
            'flags' => [1 => true, 2 => false],
            'init' => ['Execute this', 'this too'],
            'settings' => ['config1' => 'value1', 'config2' => 'value2'],
        ];
        $driver = $this->getMockBuilder(Sqlserver::class)
            ->onlyMethods(['createPdo', 'getPdo'])
            ->setConstructorArgs([$config])
            ->getMock();
        $dsn = 'sqlsrv:Server=foo;Database=bar;MultipleActiveResultSets=false';

        $expected = $config;
        $expected['flags'] += [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::SQLSRV_ATTR_ENCODING => 'a-language',
        ];
        $expected['attributes'] = [];
        $expected['app'] = null;
        $expected['connectionPooling'] = null;
        $expected['failoverPartner'] = null;
        $expected['loginTimeout'] = null;
        $expected['multiSubnetFailover'] = null;
        $expected['port'] = null;
        $expected['log'] = false;
        $expected['encrypt'] = null;
        $expected['trustServerCertificate'] = null;

        $connection = Mockery::mock(PDO::class);

        $connection->shouldReceive('quote')
            ->andReturnArg(0);

        $connection->shouldReceive('exec')->with('Execute this')->once();
        $connection->shouldReceive('exec')->with('this too')->once();
        $connection->shouldReceive('exec')->with('SET config1 value1')->once();
        $connection->shouldReceive('exec')->with('SET config2 value2')->once();

        $driver->expects($this->once())->method('createPdo')
            ->with($dsn, $expected)
            ->willReturn($connection);

        $driver->connect();
    }

    /**
     * Test connecting to Sqlserver with persistent set to false
     */
    public function testConnectionPersistentFalse(): void
    {
        $this->skipIf($this->missingExtension, 'pdo_sqlsrv is not installed.');

        $driver = new Sqlserver([
            'persistent' => false,
            'host' => 'shouldnotexist',
            'username' => 'Administrator',
            'password' => 'blablabla',
            'database' => 'bar',
            'loginTimeout' => 1,
        ]);

        // This should not throw an InvalidArgumentException because
        // persistent is false (the default).
        $this->expectException(MissingConnectionException::class);
        $driver->connect();
    }

    /**
     * Test if attempting to connect with the driver throws an exception when
     * using an invalid config setting.
     */
    public function testConnectionPersistentTrueException(): void
    {
        $this->skipIf($this->missingExtension, 'pdo_sqlsrv is not installed.');

        $driver = new Sqlserver([
            'persistent' => true,
            'host' => 'shouldnotexist',
            'username' => 'Administrator',
            'password' => 'blablabla',
            'database' => 'bar',
            'loginTimeout' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Config setting "persistent" cannot be set to true, as the Sqlserver PDO driver does not support PDO::ATTR_PERSISTENT');
        $driver->connect();
    }

    /**
     * Test setting/skipping of client side buffering options based on output of
     * SelectQuery::isBufferedResultsEnabled()
     *
     * @return void
     */
    public function testPrepare(): void
    {
        $this->skipIf($this->missingExtension, 'pdo_sqlsrv is not installed.');

        $driver = $this->getMockBuilder(Sqlserver::class)
            ->onlyMethods(['getPdo'])
            ->disableOriginalConstructor()
            ->getMock();

        $pdo = Mockery::mock(PDO::class);

        $statement = $this->getMockBuilder(PDOStatement::class)
            ->getMock();

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $driver->method('getPdo')
            ->willReturn($pdo);

        $pdo->shouldReceive('prepare')
            ->with('', [
                PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL,
                PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED,
            ])
            ->andReturn($statement)
            ->once();

        $pdo->shouldReceive('prepare')
            ->with('', [])
            ->andReturn($statement)
            ->once();

        $query = new SelectQuery($connection);
        $driver->prepare($query);

        $query->disableBufferedResults();
        $driver->prepare($query);
    }

    /**
     * Test select with limit only and SQLServer2012+
     */
    public function testSelectLimitVersion12(): void
    {
        $driver = $this->getMockBuilder(Sqlserver::class)
            ->onlyMethods(['createPdo', 'getPdo', 'version', 'enabled'])
            ->setConstructorArgs([[]])
            ->getMock();
        $driver->method('version')
            ->willReturn('12');
        $driver->method('enabled')
            ->willReturn(true);

        $connection = new Connection(['driver' => $driver, 'log' => false]);

        $query = new SelectQuery($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->orderBy(['id'])
            ->offset(10);
        $this->assertSame('SELECT id, title FROM articles ORDER BY id OFFSET 10 ROWS', $query->sql());

        $query = new SelectQuery($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->orderBy(['id'])
            ->limit(10)
            ->offset(50);
        $this->assertSame('SELECT id, title FROM articles ORDER BY id OFFSET 50 ROWS FETCH FIRST 10 ROWS ONLY', $query->sql());

        $query = new SelectQuery($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->offset(10);
        $this->assertSame('SELECT id, title FROM articles ORDER BY (SELECT NULL) OFFSET 10 ROWS', $query->sql());

        $query = new SelectQuery($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->limit(10);
        $this->assertSame('SELECT TOP 10 id, title FROM articles', $query->sql());
    }

    /**
     * Test select with limit on lte SQLServer2008
     */
    public function testSelectLimitOldServer(): void
    {
        $driver = $this->getMockBuilder(Sqlserver::class)
            ->onlyMethods(['createPdo', 'getPdo', 'version', 'enabled'])
            ->setConstructorArgs([[]])
            ->getMock();
        $driver->expects($this->any())
            ->method('version')
            ->willReturn('8');
        $driver->method('enabled')
            ->willReturn(true);

        $connection = new Connection(['driver' => $driver, 'log' => false]);

        $query = new SelectQuery($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->limit(10);
        $expected = 'SELECT TOP 10 id, title FROM articles';
        $this->assertSame($expected, $query->sql());

        $query = new SelectQuery($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->offset(10);
        $identifier = '_cake_page_rownum_';
        if ($connection->getDriver()->isAutoQuotingEnabled()) {
            $identifier = $connection->getDriver()->quoteIdentifier($identifier);
        }
        $expected = 'SELECT * FROM (SELECT id, title, (ROW_NUMBER() OVER (ORDER BY (SELECT NULL))) AS ' . $identifier . ' ' .
            'FROM articles) _cake_paging_ ' .
            'WHERE _cake_paging_._cake_page_rownum_ > 10';
        $this->assertSame($expected, $query->sql());

        $query = new SelectQuery($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->orderBy(['id'])
            ->offset(10);
        $expected = 'SELECT * FROM (SELECT id, title, (ROW_NUMBER() OVER (ORDER BY id)) AS ' . $identifier . ' ' .
            'FROM articles) _cake_paging_ ' .
            'WHERE _cake_paging_._cake_page_rownum_ > 10';
        $this->assertSame($expected, $query->sql());

        $query = new SelectQuery($connection);
        $query->select(['id', 'title'])
            ->from('articles')
            ->orderBy(['id'])
            ->where(['title' => 'Something'])
            ->limit(10)
            ->offset(50);
        $expected = 'SELECT * FROM (SELECT id, title, (ROW_NUMBER() OVER (ORDER BY id)) AS ' . $identifier . ' ' .
            'FROM articles WHERE title = :c0) _cake_paging_ ' .
            'WHERE (_cake_paging_._cake_page_rownum_ > 50 AND _cake_paging_._cake_page_rownum_ <= 60)';
        $this->assertSame($expected, $query->sql());

        $query = new SelectQuery($connection);
        $subquery = new SelectQuery($connection);
        $subquery->select(1);
        $query
            ->select([
                'id',
                'computed' => $subquery,
            ])
            ->from('articles')
            ->orderBy([
                'computed' => 'ASC',
            ])
            ->offset(10);
        $expected =
            'SELECT * FROM (' .
                'SELECT id, (SELECT 1) AS computed, ' .
                '(ROW_NUMBER() OVER (ORDER BY (SELECT 1) ASC)) AS _cake_page_rownum_ FROM articles' .
            ') _cake_paging_ ' .
            'WHERE _cake_paging_._cake_page_rownum_ > 10';
        $this->assertSame($expected, $query->sql());

        $subqueryA = new SelectQuery($connection);
        $subqueryA
            ->select('count(*)')
            ->from(['a' => 'articles'])
            ->where([
                'a.id = articles.id',
                'a.published' => 'Y',
            ]);

        $subqueryB = new SelectQuery($connection);
        $subqueryB
            ->select('count(*)')
            ->from(['b' => 'articles'])
            ->where([
                'b.id = articles.id',
                'b.published' => 'N',
            ]);

        $query = new SelectQuery($connection);
        $query
            ->select([
                'id',
                'computedA' => $subqueryA,
                'computedB' => $subqueryB,
            ])
            ->from('articles')
            ->orderBy([
                'computedA' => 'ASC',
            ])
            ->offset(10);

        $this->assertSame(
            'SELECT * FROM (' .
                'SELECT id, ' .
                '(SELECT count(*) FROM articles a WHERE (a.id = articles.id AND a.published = :c0)) AS computedA, ' .
                '(SELECT count(*) FROM articles b WHERE (b.id = articles.id AND b.published = :c1)) AS computedB, ' .
                '(ROW_NUMBER() OVER (ORDER BY (SELECT count(*) FROM articles a WHERE (a.id = articles.id AND a.published = :c2)) ASC)) AS _cake_page_rownum_ FROM articles' .
            ') _cake_paging_ ' .
            'WHERE _cake_paging_._cake_page_rownum_ > 10',
            $query->sql(),
        );
    }

    /**
     * Test that insert queries have results available to them.
     */
    public function testInsertUsesOutput(): void
    {
        $driver = $this->getMockBuilder(Sqlserver::class)
            ->onlyMethods(['createPdo', 'getPdo', 'enabled'])
            ->setConstructorArgs([[]])
            ->getMock();
        $driver->method('enabled')
            ->willReturn(true);
        $connection = new Connection(['driver' => $driver, 'log' => false]);
        $query = new InsertQuery($connection);
        $query->insert(['title'])
            ->into('articles')
            ->values(['title' => 'A new article']);
        $expected = 'INSERT INTO articles (title) OUTPUT INSERTED.* VALUES (:c0)';
        $this->assertSame($expected, $query->sql());
    }

    /**
     * Test that having queries replace the aggregated alias field.
     */
    public function testHavingReplacesAlias(): void
    {
        $driver = $this->getMockBuilder(Sqlserver::class)
            ->onlyMethods(['connect', 'getPdo', 'version', 'enabled'])
            ->setConstructorArgs([[]])
            ->getMock();
        $driver->expects($this->any())
            ->method('version')
            ->willReturn('8');
        $driver->method('enabled')
            ->willReturn(true);

        $connection = new Connection(['driver' => $driver, 'log' => false]);

        $query = new SelectQuery($connection);
        $query
            ->select([
                'posts.author_id',
                'post_count' => $query->func()->count('posts.id'),
            ])
            ->groupBy(['posts.author_id'])
            ->having([$query->newExpr()->gte('post_count', 2, 'integer')]);

        $expected = 'SELECT posts.author_id, (COUNT(posts.id)) AS post_count ' .
            'GROUP BY posts.author_id HAVING COUNT(posts.id) >= :c0';
        $this->assertSame($expected, $query->sql());
    }

    /**
     * Test that having queries replaces nothing is no alias is used.
     */
    public function testHavingWhenNoAliasIsUsed(): void
    {
        $driver = $this->getMockBuilder(Sqlserver::class)
            ->onlyMethods(['connect', 'getPdo', 'version', 'enabled'])
            ->setConstructorArgs([[]])
            ->getMock();
        $driver->expects($this->any())
            ->method('version')
            ->willReturn('8');
        $driver->method('enabled')
            ->willReturn(true);

        $connection = new Connection(['driver' => $driver, 'log' => false]);

        $query = new SelectQuery($connection);
        $query
            ->select([
                'posts.author_id',
                'post_count' => $query->func()->count('posts.id'),
            ])
            ->groupBy(['posts.author_id'])
            ->having([$query->newExpr()->gte('posts.author_id', 2, 'integer')]);

        $expected = 'SELECT posts.author_id, (COUNT(posts.id)) AS post_count ' .
            'GROUP BY posts.author_id HAVING posts.author_id >= :c0';
        $this->assertSame($expected, $query->sql());
    }

    public function testExceedingMaxParameters(): void
    {
        $connection = ConnectionManager::get('test');
        $this->skipIf(!$connection->getDriver() instanceof Sqlserver);

        $query = $connection->selectQuery()
            ->from('articles')
            ->whereInList('id', range(0, 2100));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Exceeded maximum number of parameters (2100) for prepared statements in Sql Server',
        );
        $connection->getDriver()->prepare($query);
    }

    /**
     * Tests driver-specific feature support check.
     */
    public function testSupports(): void
    {
        $driver = ConnectionManager::get('test')->getDriver();
        $this->skipIf(!$driver instanceof Sqlserver);

        $this->assertTrue($driver->supports(DriverFeatureEnum::CTE));
        $this->assertTrue($driver->supports(DriverFeatureEnum::DISABLE_CONSTRAINT_WITHOUT_TRANSACTION));
        $this->assertTrue($driver->supports(DriverFeatureEnum::SAVEPOINT));
        $this->assertTrue($driver->supports(DriverFeatureEnum::TRUNCATE_WITH_CONSTRAINTS));
        $this->assertTrue($driver->supports(DriverFeatureEnum::WINDOW));
        $this->assertTrue($driver->supports(DriverFeatureEnum::INTERSECT));

        $this->assertFalse($driver->supports(DriverFeatureEnum::INTERSECT_ALL));
        $this->assertFalse($driver->supports(DriverFeatureEnum::JSON));
        $this->assertFalse($driver->supports(DriverFeatureEnum::SET_OPERATIONS_ORDER_BY));
    }
}
