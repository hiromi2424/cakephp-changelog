<?php
namespace Changelog\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * EyeCatchImagesFixture
 */
class EyeCatchImagesFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer'],
        'article_id' => ['type' => 'integer'],
        'file' => ['type' => 'string', 'null' => true],
        'created' => ['type' => 'timestamp', 'null' => true],
        'updated' => ['type' => 'timestamp', 'null' => true],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        ['article_id' => 1, 'file' => 'before.png',
            'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'],
    ];
}
