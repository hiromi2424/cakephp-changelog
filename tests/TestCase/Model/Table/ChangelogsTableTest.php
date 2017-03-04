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
        $this->assertInstanceOf('\Changelog\Model\Table\ChangelogsTable', $this->Changelogs);
        $this->assertSame('id', $this->Changelogs->primaryKey());
        $this->assertTrue($this->Changelogs->behaviors()->has('Timestamp'));
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $entity = $this->Changelogs->newEntity([
            'id' => 'expectedInteger',
            'model' => '',
            'foreign_key' => '',
            'is_new' => 'expectedBoolean',
        ]);

        $errors = $entity->errors();
        $this->assertNotEmpty($errors);

        $this->assertArrayHasKey('id', $errors);
        $this->assertArrayHasKey('model', $errors);
        $this->assertArrayHasKey('foreign_key', $errors);
        $this->assertArrayHasKey('is_new', $errors);
    }
}
