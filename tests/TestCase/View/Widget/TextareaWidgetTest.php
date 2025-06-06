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
namespace Cake\Test\TestCase\View\Widget;

use Cake\TestSuite\TestCase;
use Cake\View\Form\NullContext;
use Cake\View\StringTemplate;
use Cake\View\Widget\TextareaWidget;

/**
 * Textarea input test.
 */
class TextareaWidgetTest extends TestCase
{
    /**
     * @var \Cake\View\Form\NullContext
     */
    protected $context;

    /**
     * @var \Cake\View\StringTemplate
     */
    protected $templates;

    /**
     * setup
     */
    protected function setUp(): void
    {
        parent::setUp();
        $templates = [
            'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
        ];
        $this->context = new NullContext([]);
        $this->templates = new StringTemplate($templates);
    }

    /**
     * Test render in a simple case.
     */
    public function testRenderSimple(): void
    {
        $input = new TextareaWidget($this->templates);
        $result = $input->render(['name' => 'comment'], $this->context);
        $expected = [
            'textarea' => ['name' => 'comment', 'rows' => 5],
            '/textarea',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test render with a value
     */
    public function testRenderWithValue(): void
    {
        $input = new TextareaWidget($this->templates);
        $data = ['name' => 'comment', 'data-foo' => '<val>', 'val' => 'some <html>'];
        $result = $input->render($data, $this->context);
        $expected = [
            'textarea' => ['name' => 'comment', 'rows' => 5, 'data-foo' => '&lt;val&gt;'],
            'some &lt;html&gt;',
            '/textarea',
        ];
        $this->assertHtml($expected, $result);

        $data['escape'] = false;
        $result = $input->render($data, $this->context);
        $expected = [
            'textarea' => ['name' => 'comment', 'rows' => 5, 'data-foo' => '<val>'],
            'some <html>',
            '/textarea',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Ensure templateVars option is hooked up.
     */
    public function testRenderTemplateVars(): void
    {
        $this->templates->add([
            'textarea' => '<textarea custom="{{custom}}" name="{{name}}"{{attrs}}>{{value}}</textarea>',
        ]);

        $input = new TextareaWidget($this->templates);
        $data = [
            'templateVars' => ['custom' => 'value'],
            'name' => 'comment',
            'val' => 'body',
        ];
        $result = $input->render($data, $this->context);
        $expected = [
            'textarea' => ['name' => 'comment', 'rows' => 5, 'custom' => 'value'],
            'body',
            '/textarea',
        ];
        $this->assertHtml($expected, $result);
    }
}
