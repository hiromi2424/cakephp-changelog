<?php

namespace Changelog\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;
use Exception;

/**
 * TODO: impl
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE file
 */
class ChangelogBehavior extends Behavior
{

    use LocatorAwareTrait;

    /**
     * Default config
     *
     * These are merged with user-provided configuration when the behavior is used.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'changelogTable' => 'Changelog.Changelogs',
        'columnTable' => 'Changelog.ChangelogColumns',
        'filter' => ['\Changelog\Model\Behavior\ChangelogBehavior', 'filterChanges'],
        'ignoreColumns' => [
            'id',
            'created',
            'modified'
        ],
        'locator' => null,
        'logIsNew' => false,
        'logEmptyChanges' => false,
        'serializer' => 'serialize',
        'unserializer' => 'unserialize',
        'onAfterSave' => true
    ];

    /**
     * Initialize tableLocator also.
     *
     * {@inheritdoc}
     */
    public function initialize(array $config = [])
    {
        parent::initialize($config);
        if ($tableLocator = $this->config('tableLocator')) {
            $this->tableLocator($tableLocator);
        }
    }


    /**
     * afterSave callback.
     * This logs entities when `onAfterSave` option was turned on.
     *
     * {@inheritdoc}
     */
    public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        if ($this->config('onAfterSave')) {
            $this->saveChangelog($entity);
        }
    }

    /**
     * Saves changelogs for entity.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity object to log changes.
     * @return \Cake\Datasource\EntityInterface|bool Entity object when logged otherwise `false`.
     */
    public function saveChangelog(EntityInterface $entity)
    {
        $Changelogs = $this->getChangelogTable();
        $Columns = $this->getColumnTable();

        /**
         * Check if entity is new and whether log new or not.
         */
        if (!$this->config('logIsNew') && $entity->isNew()) {
            return false;
        }

        $columns = $this->_table->schema()->columns();
        $columns = array_filter($columns, function ($column) {
            return !in_array($column, $this->config('ignoreColumns'));
        });
        $beforeValues = $entity->extractOriginalChanged($columns);
        $afterValues = $entity->extract($columns, $isDirty = true);

        /**
         * Check whether change was done or not
         */
        if (!$this->config('logEmptyChanges')) {
            if (empty($beforeValues) || empty($afterValues)) {
                return false;
            }
        }

        /**
         * Before counts should equal to after counts
         */
        if (count($beforeValues) !== count($afterValues)) {
            return false;
        }

        /**
         * create saveRecord
         */
        $changelog = $Changelogs->newEntity([
            'model' => $this->_table->alias(),
            'foreign_key' => $entity->get($this->_table->primaryKey()),
            'is_new' => $entity->isNew()
        ]);

        $changelog = $Changelogs->save($changelog, [
            'atomic' => false
        ]);
        // check save results
        if (!$changelog) {
            return false;
        }

        /**
         * save childlen
         */
        $results = array_map(function ($column) use ($changelog, $beforeValues, $afterValues, $Columns) {
            $before = $beforeValues[$column];
            $after = $afterValues[$column];
            // filters changes
            $columnDef = $this->_table->schema()->column($column);
            $filter = $this->config('filter');
            if (!$filter($before, $after, $column, $columnDef)) {
                return true;
            }
            $column = $Columns->newEntity([
                'changelog_id' => $changelog->id,
                'column' => $column,
                'before' => $before,
                'after' => $after,
            ]);
            return $Columns->save($column, ['atomic' => false]);
        }, array_keys($beforeValues));

        if (in_array(false, $results)) {
            return false;
        }

        return $entity;
    }

    /**
     * Returns changelogs table
     *
     * @return \Cake\ORM\Table
     */
    public function getChangelogTable()
    {
        return $this->tableLocator()->get($this->config('changelogTable'));
    }

    /**
     * Returns changelogs table
     *
     * @return \Cake\ORM\Table
     */
    public function getColumnTable()
    {
        return $this->tableLocator()->get($this->config('columnTable'));
    }

    public static function filterChanges($before, $after, $column, $columnDef)
    {
        // filter null != ''
        return $before != $after;
    }

}
