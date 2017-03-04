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
        $this->assertInstanceOf('\Changelog\Model\Table\ChangelogColumnsTable', $this->ChangelogColumns);
        $this->assertSame('id', $this->ChangelogColumns->primaryKey());
        $this->assertTrue($this->ChangelogColumns->behaviors()->has('Timestamp'));
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $entity = $this->ChangelogColumns->newEntity([
            'id' => 'expectedInteger',
        ]);

        $errors = $entity->errors();
        $this->assertNotEmpty($errors);

        $this->assertArrayHasKey('id', $errors);
        $this->assertArrayHasKey('column', $errors);
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $entity = $this->ChangelogColumns->newEntity([
            'changelog_id' => 'not_exist',
        ]);
        $result = $this->ChangelogColumns->checkRules($entity);
        $this->assertFalse($result);

        $entity = $this->ChangelogColumns->newEntity([
            'changelog_id' => 1,
        ]);
        $result = $this->ChangelogColumns->checkRules($entity);
        $this->assertTrue($result);
    }
}
