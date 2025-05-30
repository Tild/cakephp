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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Core\Configure;
use Cake\Http\UriFactory;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Uri;

/**
 * Test case for the uri factory.
 */
class UriFactoryTest extends TestCase
{
    public function testCreateUri(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('https://cakephp.org');

        $this->assertInstanceOf(Uri::class, $uri);
    }

    public function testMarshalUriAndBaseFromSapi()
    {
        Configure::write('App.baseUrl', '/index.php');
        $result = UriFactory::marshalUriAndBaseFromSapi([]);

        $this->assertInstanceOf(Uri::class, $result['uri']);
        $this->assertSame('/index.php', $result['base']);
        $this->assertSame('/webroot/', $result['webroot']);

        Configure::delete('App.baseUrl');
    }
}
