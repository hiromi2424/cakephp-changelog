<?php
namespace Changelog\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Changelog\Model\Table\ChangelogsTable;

/**
 * Changelog\Model\Table\ChangelogsTable Test Case
 */
class ChangelogsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Changelog\Model\Table\ChangelogsTable
     */
    public $Changelogs;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.changelog.changelogs',
        'plugin.changelog.changelog_columns'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Changelogs') ? [] : ['className' => 'Changelog\Model\Table\ChangelogsTable'];
        $this->Changelogs = TableRegistry::get('Changelogs', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Changelogs);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
    }
}
