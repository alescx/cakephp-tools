<?php

namespace Tools\Test\TestCase\Controller\Component;

use Cake\Controller\Controller;
use Cake\TestSuite\TestCase;
use TestApp\Controller\AjaxComponentTestController;

/**
 * Tools\Controller\Component\AjaxComponent Test Case
 */
class AjaxComponentTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Tools\Controller\Component\AjaxComponent
     */
    public $Ajax;

    /**
     * @var Controller
     */
    public $Controller;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Controller = new AjaxComponentTestController(new \Cake\Network\Request());
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Ajax);

        parent::tearDown();
    }

    /**
     * Test setResponse method
     *
     * @return void
     */
    public function testSetResponse()
    {
        $this->Controller->loadComponent('Tools.Ajax');

        $ex = new \Exception('Test', 500);
        $this->Controller->Ajax->setResponse([
            'foo' => 'bar'
        ], $ex);


    }

    /**
     * Test setResponseError method
     *
     * @return void
     */
    public function testSetResponseError()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
