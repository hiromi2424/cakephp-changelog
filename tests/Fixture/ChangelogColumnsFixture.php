<?php
namespace Changelog\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ChangelogColumnsFixture
 *
 */
class ChangelogColumnsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'changelog_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'column' => ['type' => 'string', 'fixed' => true, 'length' => 50, 'null' => false, 'default' => '', 'comment' => '', 'precision' => null],
        'before' => ['type' => 'text', 'length' => 4294967295, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'after' => ['type' => 'text', 'length' => 4294967295, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_indexes' => [
            'changelog_id' => ['type' => 'index', 'columns' => ['changelog_id'], 'length' => []],
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
            'changelog_id' => 1,
            'column' => 'Lorem ipsum dolor sit amet',
            'before' => 'Lorem ipsum dolor sit amet',
            'after' => 'Lorem ipsum dolor sit amet',
            'created' => '2017-01-13 05:35:50',
            'modified' => '2017-01-13 05:35:50'
        ],
    ];
}
