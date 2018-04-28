<?php

namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TimestampsFixture extends TestFixture
{
    public $connection = 'test';

    /**
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'title' => ['type' => 'string', 'length' => 255],
        'modified' => ['type' => 'integer'],
        'created' => ['type' => 'integer'],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * @var array
     */
    public $records = [
    ];

}