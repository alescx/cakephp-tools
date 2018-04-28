<?php

namespace Tools\Test\TestCase\Model\Behavior;

use App\Model\Table\UsersTable;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tools\Model\Behavior\TimestampBehavior Test Case
 */
class TimestampBehaviorTest extends TestCase
{

    public $fixtures = [
        'plugin.tools.timestamps'
    ];

    /**
     * Test subject
     *
     * @var \Tools\Model\Behavior\TimestampBehavior
     */
    public $Timestamp;

    /**
     * @var UsersTable;
     */
    public $Users;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Timestamp = TableRegistry::get('Timestamps');
        //  $this->Timestamp->setConnection(ConnectionManager::get('test'));
    }

    public function testBasic()
    {
        $this->Timestamp->addBehavior('Tools.Timestamp');

        $this->connection = ConnectionManager::get('test');
        $data = ['title' => 'Test'];
        $timestamp = $this->Timestamp->newEntity($data);
        $this->Timestamp->save($timestamp);

        $this->assertEquals(1, $timestamp->id);
        $this->assertGreaterThan(0, $timestamp->created);
        $this->assertGreaterThan(0, $timestamp->modified);
    }

    public function testModify()
    {
        $this->Timestamp->addBehavior('Tools.Timestamp');
        $entity = $this->Timestamp->newEntity([
            'title' => 'Baz'
        ]);

        $this->Timestamp->save($entity);
        $entity->set('title', 'Buz');
        sleep(2);
        $this->Timestamp->save($entity);
        $this->assertTrue(($entity->modified > $entity->created));
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Timestamp);
        parent::tearDown();
    }
}
