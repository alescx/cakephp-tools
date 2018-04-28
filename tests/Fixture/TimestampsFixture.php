<?php

namespace Tools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TimestampsFixture extends TestFixture
{
    /**
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'title' => ['type' => 'string', 'length' => 255],
        'modified' => ['type' => 'integer'],
        'created' => ['type' => 'integer']
    ];

    /**
     * @var array
     */
    public $records = [
    ];

}