<?php

namespace Changelog\Model\Behavior;

use ArrayObject;
use Cake\Database\Type;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Exception;
use UnexpectedValueException;

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
        'convertValues' => 'convertChangelogValues',
        'filterChanges' => 'filterChanges',
        'ignoreColumns' => [
            'id',
            'created',
            'modified'
        ],
        'locator' => null,
        'logIsNew' => false,
        'logEmptyChanges' => false,
        'onAfterSave' => true,
        'saveChangelogRecords' => 'saveChangelogRecords'
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
        $table = $this->_table;

        /**
         * Be sure whether log new entity or not.
         */
        if (!$this->config('logIsNew') && $entity->isNew()) {
            return false;
        }

        $columns = $table->schema()->columns();

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
             * Prepare values for events
             */
            $before = $beforeValues[$column];
            $after = $afterValues[$column];
            $columnDef = $table->schema()->column($column);

            /**
             * Event data. thes variables can be changed via registered events.
             */
            $eventData = new ArrayObject(compact([
                'entity',
                'before',
                'after',
                'beforeValues',
                'afterValues',
                'column',
                'columnDef',
                'table',
                'Columns'
            ]));

            /**
             * Dispatches convert event
             */
            $event = $table->dispatchEvent('Changelog.convertValues', [$eventData]);

            /**
             * Dispatches filter event
             */
            $event = $table->dispatchEvent('Changelog.filterChanges', [$eventData]);
            if (!$event->result) {
                continue;
            }

            /**
             * Determine changes from result
             */
            extract((array)$eventData);
            if ($event->result) {
                $changes[] = [
                    'column' => $column,
                    'before' => $before,
                    'after' => $after,
                ];
            }
        }

        /**
         * Be sure whether change was done or not
         */
        if (!$this->config('logEmptyChanges') && empty($changes)) {
            return false;
        }

        /**
         * Saves actually
         */
        $data = new ArrayObject([
            'model' => $table->alias(),
            'foreign_key' => $entity->get($table->primaryKey()),
            'is_new' => $entity->isNew(),
            'changelog_columns' => $changes,
        ]);
        $options = new ArrayObject([
            'associated' => 'ChangelogColumns',
            'atomic' => false
        ]);
        return $table->dispatchEvent('Changelog.saveChangelogRecords', compact('Changelogs', 'data', 'options'))->result;
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
            'Changelog.convertValues' => $this->config('convertValues'),
            'Changelog.filterChanges' => $this->config('filterChanges'),
            'Changelog.saveChangelogRecords' => $this->config('saveChangelogRecords')
        ];
    }

    /**
     * Default convert process
     *
     * @param \Cake\Event\Event $event The event for callback
     * @param ArrayObject $data The event data. contains:
     *                          - entity
     *                          - before
     *                          - after
     *                          - beforeValues
     *                          - afterValues
     *                          - column
     *                          - columnDef
     *                          - table
     *                          - Columns
     * @return array couple of $before, $after
     */
    public function convertChangelogValues(Event $event, ArrayObject $data)
    {
        extract((array)$data);
        /**
         * @var \Cake\ORM\Entity $entity
         * @var mixed $before
         * @var mixed $after
         * @var array $beforeValues
         * @var array $afterValues
         * @var string $column
         * @var array $columnDef
         * @var \Cake\ORM\Table $table
         * @var \Cake\ORM\Table $Columns
         */

        /**
         * Date inputs sometime represents string value in
         * entity. This converts value for comparison.
         */
        switch ($columnDef['type']) {
            case 'date':
            case 'datetime':
            case 'time':
                $baseType = $table->schema()->baseColumnType($column);
                if ($baseType && Type::map($baseType)) {
                    $driver = $table->connection()->driver();
                    $before = Type::build($baseType)->toPHP($before, $driver);
                    $after = Type::build($baseType)->toPHP($after, $driver);

                    /**
                     * Modify event data
                     */
                    $data['before'] = $before;
                    $data['after'] = $after;
                    $data['beforeValues'][$column] = $before;
                    $data['afterValues'][$column] = $after;
                }
                break;
        }
    }

    /**
     * Default filter
     *
     * @param \Cake\Event\Event $event The event for callback
     * @param ArrayObject $data The event data. contains:
     *                          - entity
     *                          - before
     *                          - after
     *                          - beforeValues
     *                          - afterValues
     *                          - column
     *                          - columnDef
     *                          - table
     *                          - Columns
     * @return bool column is changed or not
     */
    public function filterChanges(Event $event, ArrayObject $data)
    {
        extract((array)$data);
        /**
         * @var \Cake\ORM\Entity $entity
         * @var mixed $before
         * @var mixed $after
         * @var array $beforeValues
         * @var array $afterValues
         * @var string $column
         * @var array $columnDef
         * @var \Cake\ORM\Table $table
         * @var \Cake\ORM\Table $Columns
         */

        /**
         * filter null != ''
         */
        return $before != $after;
    }

    /**
     * Default save process
     *
     * @param \Cake\Event\Event $event The event for callback
     * @param \Cake\ORM\Table $Changelogs The table for parent
     * @param ArrayObject $data save data
     * @param ArrayObject $options save options
     * @return bool column is changed or not
     */
    public function saveChangelogRecords(Event $event, Table $Changelogs, ArrayObject $data, ArrayObject $options)
    {
        /**
         * create saveRecord
         */
        $changelog = $Changelogs->newEntity((array)$data);
        return $Changelogs->save($changelog, (array)$options);
    }

}
