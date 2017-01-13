<?php
namespace Changelog\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Changelog\Model\Table\ChangelogColumnsTable;

/**
 * Changelog\Model\Table\ChangelogColumnsTable Test Case
 */
class ChangelogColumnsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Changelog\Model\Table\ChangelogColumnsTable
     */
    public $ChangelogColumns;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.changelog.changelog_columns',
        'plugin.changelog.changelogs'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('ChangelogColumns') ? [] : ['className' => 'Changelog\Model\Table\ChangelogColumnsTable'];
        $this->ChangelogColumns = TableRegistry::get('ChangelogColumns', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->ChangelogColumns);

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

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
    }
}
