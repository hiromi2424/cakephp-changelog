<?php
namespace Changelog\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Exception;

use Changelog\Model\Behavior\ChangelogBehavior;

class ArticlesTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        /**
         * make associations for test
         */
        $this->belongsTo('Users');
        $this->hasOne('EyeCatchImages');
        $this->hasMany('Comments');
        $this->belongsToMany('Tags', [
            'through' => 'Tagged',
        ]);
        $this->addBehavior('Changelog.Changelog');
    }
}

class UsersTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->primaryKey('id');
        $this->displayField('username');
    }
}

class EyeCatchImagesTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->primaryKey('id');
        $this->displayField('file');
    }
}

class CommentsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->primaryKey('id');
        $this->displayField('id');
    }
}

class TaggedTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->primaryKey('id');
        $this->displayField('id');
    }
}

class TagsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->primaryKey('id');
        $this->displayField('name');
    }
}

class ChangelogBehaviorTest extends TestCase
{

    /**
     * Test connection
     *
     * @var \Cake\Datasource\ConnectionInterface
     */
    public $connection;

    /**
     * Article test model
     *
     * @var ArticlesTable
     */
    public $Articles;

    /**
     * User test model
     *
     * @var UsersTable
     */
    public $Users;

    /**
     * Changelog test model
     *
     * @var ChangelogsTable
     */
    public $Changelogs;

    /**
     * ChangelogColumn test model
     *
     * @var ChangelogColumnsTable
     */
    public $ChangelogColumns;

    /**
     * Load relevant fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.changelog.articles',
        'plugin.changelog.changelogs',
        'plugin.changelog.changelog_columns',
        'plugin.changelog.comments',
        'plugin.changelog.eye_catch_images',
        'plugin.changelog.tagged',
        'plugin.changelog.tags',
        'plugin.changelog.users'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->connection = ConnectionManager::get('test');

        $this->Articles = new ArticlesTable([
            'alias' => 'Articles',
            'table' => 'articles',
            'connection' => $this->connection
        ]);

        $this->Articles = new ArticlesTable([
            'alias' => 'Articles',
            'table' => 'articles',
            'connection' => $this->connection
        ]);
        $this->Changelogs = TableRegistry::get('Changelog.Changelogs');
        $this->ChangelogColumns = TableRegistry::get('Changelog.ChangelogColumns');
    }
    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset(
            $this->connection,
            $this->Articles,
            $this->Changelogs,
            $this->ChangelogColumns
            );
        parent::tearDown();
    }

    /**
     * Test saveChangelog() method
     *
     * @test
     */
    public function saveChangelog()
    {
        // update article's title and body
        $article = $this->Articles->get(1);
        $article = $this->Articles->patchEntity($article, [
            'title' => 'Changed title',
            'body' => 'Changed body'
        ]);
        $result = $this->Articles->saveChangelog($article);
        $this->assertNotEmpty($result);

        // find parent table changelogs record
        $changelog = $this->Changelogs->find()
            ->where([
                'model' => 'Articles',
                'foreign_key' => 1
            ])
            ->first();
        $this->assertNotEmpty($changelog);
        $this->assertSame(false, $changelog->is_new);

        // asserts title column(VARCHAR)
        $titleChange = $this->ChangelogColumns->find()
            ->where([
                'ChangelogColumns.changelog_id' => $changelog->id,
                'ChangelogColumns.column' => 'title',
            ])
            ->first();
        $this->assertNotEmpty($titleChange);
        $this->assertSame('First Article', $titleChange->before);
        $this->assertSame('Changed title', $titleChange->after);

        // asserts body column(TEXT)
        $bodyChange = $this->ChangelogColumns->find()
            ->where([
                'ChangelogColumns.changelog_id' => $changelog->id,
                'ChangelogColumns.column' => 'body',
            ])
            ->first();
        $this->assertNotEmpty($bodyChange);
        $this->assertSame('First Article', $bodyChange->before);
        $this->assertSame('Changed body', $bodyChange->after);
    }

    /**
     * Test saveChangelog() method
     * Belows are test for NULL => '' is not change for text column.
     * This scenario is happen when body input was not placed at your
     * form on add action and was placed on edit action.
     * So before value was NULL and after value was '' so that entity
     * handles these as different values.
     *
     * @test
     */
    public function saveChangelogNullAndEmptyString()
    {
        // articles->id = 2 holds body column as null value
        $article = $this->Articles->get(2);
        // Set empty string to body
        $article = $this->Articles->patchEntity($article, [
            'title' => 'Changed title',
            'body' => ''
        ]);
        $result = $this->Articles->saveChangelog($article);
        $this->assertNotEmpty($result);

        // find parent table changelogs record
        $changelog = $this->Changelogs->find()
            ->where([
                'model' => 'Articles',
                'foreign_key' => 2
            ])
            ->first();
        $this->assertNotEmpty($changelog);

        // find record for body column
        $bodyChange = $this->ChangelogColumns->find()
            ->where([
                'ChangelogColumns.changelog_id' => $changelog->id,
                'ChangelogColumns.column' => 'body',
            ])
            ->first();
        // Expects NO change found
        $this->assertEmpty($bodyChange);
    }

    /**
     * Test saveChangelog() method
     * Belows are test for NULL => '' is not change for text column.
     * This scenario is happen when body input was not placed at your
     * form on add action and was placed on edit action.
     * So before value was NULL and after value was '' so that entity
     * handles these as different values.
     *
     * @test
     */
    public function saveChangelogDateField()
    {
        // articles->id = 3 holds date column filled
        $article = $this->Articles->get(3);
        // Set only date string expecting input from a form
        // Database record holds '2017/01/14 00:00:00' as value
        // and no change found is right result
        $article = $this->Articles->patchEntity($article, [
            'publish_at' => '2017/01/14 00:00'
        ]);
        $result = $this->Articles->saveChangelog($article);
        debug($result);
        $this->assertEmpty($result);
    }

    /**
     * Test saveChangelog() method
     * Belows are test for NULL => '' is not change for text column.
     * This scenario is happen when body input was not placed at your
     * form on add action and was placed on edit action.
     * So before value was NULL and after value was '' so that entity
     * handles these as different values.
     *
     * @test
     */
    public function saveChangelogAssociation()
    {
        // articles->id = 1 has all association data
        $article = $this->Articles->get(1, [
            'Users',
            'EyeCatchImages',
            'Comments',
            'Tags',
        ]);
        // modigy all associations
        $article = $this->Articles->patchEntity($article, [
            'publish_at' => '2017/01/14 00:00',
            // belongsTo
            'user_id' => 2,
            // hasOne
            'eye_catch_image' => [
                'id' => 1,
                'file' => 'after.png',
            ],
            // hasMany
            'comments' => [
                ['body' => 'Second Comment'],
            ],
            // belongsToMany
            'tags' => [
                '_ids' => [1, 2]
            ],
        ]);
        //debug($article);
        $result = $this->Articles->saveChangelog($article);
        $this->assertNotEmpty($result);
    }

}
