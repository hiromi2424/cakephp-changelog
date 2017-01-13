<?php
namespace Changelog\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Article Fixture
 */
class ArticlesFixture extends TestFixture {

/**
 * Fields
 *
 * @var array $fields
 */
    public $fields = [
        'id' => ['type' => 'integer'],
        'title' => ['type' => 'string', 'null' => false],
        'body' => ['type' => 'text', 'null' => true],
        'created' => 'datetime',
        'updated' => 'datetime',
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

/**
 * Records
 *
 * @var array $records
 */
    public $records = [
        ['title' => 'First Article', 'body' => 'First Article',
            'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
        ],
        ['title' => 'Second Article', 'body' => null,
            'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
        ],
    ];

}