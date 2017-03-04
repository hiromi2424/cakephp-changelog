<?php
namespace Changelog\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Tags Fixture
 */
class TagsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer'],
        'name' => ['type' => 'string', 'null' => true],
        'created' => 'datetime',
        'updated' => 'datetime',
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        ['name' => 'One',
            'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
        ],
        ['name' => 'Two',
            'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
        ],
    ];
}
