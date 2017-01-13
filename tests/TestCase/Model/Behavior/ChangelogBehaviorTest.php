<?php
namespace Changelog\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Exception;

use Changelog\Model\Behavior\ChangelogBehavior;

class TestException extends Exception
{
}

class ArticlesTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Changelog.Changelog');
    }
}

class ChangelogBehaviorTest extends TestCase
{

    /**
     * Article test model
     *
     * @var ArticlesTable
     */
    public $Articles;

    /**
     * Load relevant fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.changelog.articles',
        'plugin.changelog.changelogs',
        'plugin.changelog.changelog_columns',
        //'core.users'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        $this->Articles = new ArticlesTable([
            'alias' => 'Articles',
            'table' => 'articles',
        ]);
        parent::setUp();
    }
    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Articles);
    }

    /**
     * Test initialize method
     *
     * @test
     */
    public function initialize()
    {
    }
}
