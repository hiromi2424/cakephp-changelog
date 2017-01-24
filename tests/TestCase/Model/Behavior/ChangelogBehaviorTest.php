<?php
namespace Changelog\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\I18n;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Exception;

use Changelog\Model\Behavior\ChangelogBehavior;

function table($name) {
    $table = Inflector::underscore($name);
    $alias = Inflector::camelize($name);
    $class = __NAMESPACE__ . '\\' . $alias . 'Table';
    $connection = ConnectionManager::get('test');
    return new $class([
        'alias' => $alias,
        'table' => $table,
        'connection' => $connection
    ]);
}

class ArticlesTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        /**
         * make associations for test
         */
        $this->belongsTo('Users', [
            'targetTable' => table('Users'),
        ]);
        $this->hasOne('EyeCatchImages', [
            'targetTable' => table('EyeCatchImages'),
        ]);
        $this->hasMany('Comments', [
            'targetTable' => table('Comments'),
        ]);
        $this->belongsToMany('Tags', [
            'targetTable' => table('Tags'),
            'through' => table('Tagged'),
        ]);
        $this->addBehavior('Changelog.Changelog', [
            'convertAssociations' => [$this, 'convertAssociations']
        ]);
    }

    public function convertAssociations($property, $value, $kind, $association, $isMany, $beforeValues)
    {
        if ($property === 'comments') {
            $values = $kind === 'before' ? $beforeValues : $value;
            return implode(', ', collection($values)->extract('body')
                ->filter()
                ->map(function ($body) {
                    return Text::truncate($body, 10);
                })
                ->toArray());
        }

        return $this->defaultConvertAssociation($property, $value, $kind, $association, $isMany, $beforeValues);
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

        $this->Articles = table('Articles');
        $this->Changelogs = TableRegistry::get('Changelog.Changelogs');
        $this->ChangelogColumns = TableRegistry::get('Changelog.ChangelogColumns');

        $this->backupLocale = I18n::locale();
        I18n::locale('en_US');
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
        I18n::locale($this->backupLocale);
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
        $this->assertEmpty($result);
    }

    /**
     * Test saveChangelog() method
     * Associations should be converted to display field.
     *
     * @test
     */
    public function saveChangelogAssociation()
    {
        // articles->id = 1 has all association data
        $assocs = [
            'Users',
            'EyeCatchImages',
            'Comments',
            'Tags',
        ];
        $article = $this->Articles->get(1, [
            'contain' => $assocs,
        ]);
        // modify all associations
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
        // Dirty states of belongsToMany properties are not changed.
        // Making dirty can be done on userland's code.
        $article->dirty('tags', true);

        // do save actually
        $result = $this->Articles->save($article, ['associated' => $assocs]);
        $this->assertNotEmpty($result);

        // find parent table changelogs record
        $changelog = $this->Changelogs->find()
            ->where([
                'model' => 'Articles',
                'foreign_key' => 1
            ])->contain('ChangelogColumns')
            ->first();
        $this->assertNotEmpty($changelog);

        // find record for belongsTo association
        $userChange = $this->ChangelogColumns->find()
            ->where([
                'ChangelogColumns.changelog_id' => $changelog->id,
                'ChangelogColumns.column' => 'user',
            ])
            ->first();
        $this->assertNotEmpty($userChange);
        $this->assertSame('nate', $userChange->after);

        // find record for hasOne association
        $eyeCatchChange = $this->ChangelogColumns->find()
            ->where([
                'ChangelogColumns.changelog_id' => $changelog->id,
                'ChangelogColumns.column' => 'eye_catch_image',
            ])
            ->first();
        $this->assertNotEmpty($eyeCatchChange);
        $this->assertSame('before.png', $eyeCatchChange->before);
        $this->assertSame('after.png', $eyeCatchChange->after);

        // find record for hasMany association
        $commentsChange = $this->ChangelogColumns->find()
            ->where([
                'ChangelogColumns.changelog_id' => $changelog->id,
                'ChangelogColumns.column' => 'comments',
            ])
            ->first();
        $this->assertNotEmpty($commentsChange);
        // Comments table is associated with hasMany
        // that defaults 'append' save strategy.
        // so that added entity will be changed value.
        // TODO: consider this
        $this->assertSame('First C...', $commentsChange->before);
        $this->assertSame('Second ...', $commentsChange->after);

        // find record for belongsTo association
        $tagsChange = $this->ChangelogColumns->find()
            ->where([
                'ChangelogColumns.changelog_id' => $changelog->id,
                'ChangelogColumns.column' => 'tags',
            ])
            ->first();
        $this->assertNotEmpty($tagsChange);
    }

    /**
     * Test saveChangelog() method
     * Associations should be converted to display field.
     *
     * @test
     */
    public function saveChangelogCombinations()
    {
        $this->Articles->behaviors()->get('Changelog')->config('combinations', [
            'publish_at_title' => ['publish_at', 'title'],
        ]);
        // articles->id = 1 has all association data.
        $article = $this->Articles->get(1);
        // modify all associations
        $article = $this->Articles->patchEntity($article, [
            'publish_at' => '2017/01/14 00:00',
            'title' => 'changed title',
        ]);

        // do save actually
        $result = $this->Articles->save($article);
        $this->assertNotEmpty($result);

        // find parent table changelogs record
        $changelog = $this->Changelogs->find()
            ->where([
                'model' => 'Articles',
                'foreign_key' => 1
            ])
            ->first();
        $this->assertNotEmpty($changelog);

        // find combined column
        $combinedChange = $this->ChangelogColumns->find()
            ->where([
                'ChangelogColumns.changelog_id' => $changelog->id,
                'ChangelogColumns.column' => 'publish_at_title',
            ])
            ->first();
        $this->assertNotEmpty($combinedChange);
        $this->assertSame('First Article', $combinedChange->before);
        $this->assertSame('1/14/17, 12:00 AM changed title', $combinedChange->after);
    }

}
