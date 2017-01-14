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
        'filter' => 'filterChanges',
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
         * Be sure whether log new entity or not.
         */
        if (!$this->config('logIsNew') && $entity->isNew()) {
            return false;
        }

        $columns = $this->_table->schema()->columns();

        /**
         * Filters ignored columns
         */
        $columns = array_diff($columns, $this->config('ignoreColumns'));
        $beforeValues = $entity->extractOriginalChanged($columns);
        $afterValues = $entity->extract($columns, $isDirty = true);

        /**
         * Exception, before counts should equal to after counts
         */
        if (count($beforeValues) !== count($afterValues)) {
            return false;
        }

        /**
         * Filters changes
         */
        $changes = [];
        foreach (array_keys($beforeValues) as $column) {
            /**
             * Dispatches filter event
             */
            $before = $beforeValues[$column];
            $after = $afterValues[$column];
            $columnDef = $this->_table->schema()->column($column);
            $table = $this->_table;
            $event = $this->_table->behaviors()->dispatchEvent('Changelog.filterChanges', compact([
                'entity',
                'before',
                'after',
                'column',
                'columnDef',
                'table',
                'Columns'
            ]));
            // TODO: fix changelogID
            if ($event->result) {
                $changes[] = $Columns->newEntity([
                    'changelog_id' => $changelog->id,
                    'column' => $column,
                    'before' => $before,
                    'after' => $after,
                ]);
            }
        }

        /**
         * Be sure  whether change was done or not
         */
        if (!$this->config('logEmptyChanges') && empty($changes)) {
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

    /**
     * Define additional events for filter
     *
     * {@inheritdoc}
     */
    public function implementedEvents()
    {
        return parent::implementedEvents() + [
            'Changelog.filterChanges' => [
                'callable' => $this->config('filter')
            ]
        ];
    }

    /**
     * Default filter
     *
     * @return bool column is changed or not
     */
    public function filterChanges(Event $event)
    {
        /**
         * @var \Cake\ORM\Entity $entity
         * @var mixed $before
         * @var mixed $after
         * @var string $column
         * @var array $columnDef
         * @var \Cake\ORM\Table $table
         * @var \Cake\ORM\Table $Columns
         */
        extract($event->data());

        /**
         * Date inputs sometime represents string value in
         * entity. This converts value for comparison.
         */
        if (!empty($after)) {
            switch ($columnDef['type']) {
                case 'date':
                case 'datetime':
                case 'time':
                    $baseType = $table->baseColumnType($column);
                    if ($baseType && Type::map($baseType)) {
                        $after = Type::build($baseType)->toPHP($after);
                    }
                    break;
            }
        }

        // filter null != ''
        return $data['before'] != ['after'];
    }

}
