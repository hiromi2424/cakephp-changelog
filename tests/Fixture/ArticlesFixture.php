<?php
namespace Changelog\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Articles Fixture
 */
class ArticlesFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer'],
        'user_id' => ['type' => 'integer'],
        'title' => ['type' => 'string', 'null' => false],
        'body' => ['type' => 'text', 'null' => true],
        'publish_at' => ['type' => 'datetime', 'null' => true],
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
        ['title' => 'First Article', 'body' => 'First Article',
            'publish_at' => null, 'user_id' => null,
            'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
        ],
        ['title' => 'Second Article', 'body' => null,
            'publish_at' => null, 'user_id' => null,
            'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
        ],
        ['title' => 'Third Article', 'body' => null,
            'publish_at' => '2017-01-14 00:00:00', 'user_id' => 1,
            'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
        ],
    ];
}
