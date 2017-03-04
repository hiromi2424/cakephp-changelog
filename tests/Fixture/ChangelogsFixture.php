<?php
namespace Changelog\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ChangelogsFixture
 *
 */
class ChangelogsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'model' => ['type' => 'string', 'fixed' => true, 'length' => 30, 'null' => false, 'default' => '', 'comment' => '', 'precision' => null],
        'foreign_key' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => '', 'comment' => '', 'precision' => null],
        'is_new' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_indexes' => [
            'model_foreign_key' => ['type' => 'index', 'columns' => ['model', 'foreign_key'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 1,
            'model' => 'Lorem ipsum dolor sit amet',
            'foreign_key' => 'dc0e06a5-102e-40af-96a8-6cd07013d5c8',
            'is_new' => 1,
            'created' => '2017-01-13 06:02:03',
            'modified' => '2017-01-13 06:02:03'
        ],
    ];
}
