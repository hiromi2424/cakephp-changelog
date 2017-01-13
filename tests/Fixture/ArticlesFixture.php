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
        'body' => ['type' => 'text', 'null' => false],
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
            'slug' => 'first_article', 'views' => 2, 'comments' => 1,
            'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
        ],
        ['title' => 'Second Article', 'body' => 'Second Article',
            'slug' => 'second_article', 'views' => 1, 'comments' => 2,
            'created' => '2007-03-18 10:41:23', 'updated' => '2007-03-18 10:43:31'
        ],
        ['title' => 'Third Article', 'body' => 'Third Article',
            'slug' => 'third_article', 'views' => 2, 'comments' => 3,
            'created' => '2007-03-18 10:43:23', 'updated' => '2007-03-18 10:45:31'
        ],
    ];

}