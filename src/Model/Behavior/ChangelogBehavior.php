<?php

namespace Changelog\Model\Behavior;

use ArrayObject;
use Cake\Database\Type;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Association;
use Cake\ORM\Association\BelongsTo;
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
        'autoSave' => true,
        'changelogTable' => 'Changelog.Changelogs',
        'columnTable' => 'Changelog.ChangelogColumns',
        'collectChangesOnBeforeSave' => true,
        'combinations' => [],
        'convertAssociations' => true,
        'convertForeignKeys' => true,
        'convertDatetimeColumns' => true,
        'equalComparison' => true,
        'exchangeForeignKey' => true,
        'filterForeignKeys' => true,
        'ignoreColumns' => [
            'id',
            'created',
            'modified'
        ],
        'logIsNew' => false,
        'logEmptyChanges' => false,
        'saveChangelogOnAfterSave' => true,
        'tableLocator' => null
    ];

    /**
     * Holds collected before values
     *
     * @var array
     */
    protected $_collectedBeforeValues = null;

    /**
     * Holds changes to save
     *
     * @var array
     */
    protected $_changes = [];

    /**
     * olds combination columns
     *
     * @var array
     */
    protected $_combinationColumns = [];

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
     * beforeSave callback.
     * Collects original values of associations.
     *
     * {@inheritdoc}
     */
    public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        if ($this->config('collectChangesOnBeforeSave')) {
            $this->collectChanges($entity, $options);
        }
    }

    public function collectChanges(EntityInterface $entity, ArrayObject $options = null)
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

        /**
         * Extract column names from original values
         */
        $columns = array_keys($entity->getOriginalValues());

        /**
         * Extract dirty columns.
         * Exchange columns to actually dirty ones.
         */
        $afterValues = $entity->extract($columns, $isDirty = true);
        $columns = array_keys($afterValues);

        /**
         * Adds extra columns when combinations was given.
         */
        $this->_combinationColumns = [];
        if ($combinations = $this->config('combinations')) {
            foreach ($combinations as $name => $settings) {
                $settings = $this->_normalizeCombinationSettings($settings);
                $this->_combinationColumns = array_merge($this->_combinationColumns, $settings['columns']);
            }
            $this->_combinationColumns = array_values(array_unique($this->_combinationColumns));

            $columns = array_values(array_unique(array_merge($columns, $this->_combinationColumns)));
            $afterValues = $entity->extract($columns);
        }

        /**
         * Extract original value from decided columns.
         */
        $beforeValues = $entity->extractOriginal($columns);

        /**
         * Filters ignored columns
         */
        $columns = array_diff($columns, $this->config('ignoreColumns'));

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
        $associations = $this->_associationsIndexedByProperty();
        $foreignKeys = $this->_associationsForForeignKey();
        foreach ($columns as $column) {
            /**
             * Prepare values for events
             */
            $before = $beforeValues[$column];
            $after = $afterValues[$column];

            $isAssociation = array_key_exists($column, $associations);
            $isForeignKey = array_key_exists($column, $foreignKeys);

            /**
             * Prepare association/column info
             */
            if ($isAssociation) {
                $columnDef = null;
                $association = $associations[$column];
            } else {
                $columnDef = $table->schema()->column($column);
                $association = null;
                if ($isForeignKey) {
                    $association = $foreignKeys[$column];
                }
            }

            /**
             * Event data. These variables can be changed via registered events.
             */
            $eventData = new ArrayObject(compact([
                'entity',
                'isForeignKey',
                'isAssociation',
                'before',
                'after',
                'beforeValues',
                'afterValues',
                'column',
                'columnDef',
                'association',
                'table',
                'Columns'
            ]));

            /**
             * Dispatches convert event
             */
            $event = $table->dispatchEvent('Changelog.convertValues', [$eventData]);
            extract((array)$eventData);

            if (!$this->_combinationColumns || !in_array($column, $this->_combinationColumns)) {
                /**
                 * Dispatches filter event
                 */
                $event = $table->dispatchEvent('Changelog.filterChanges', [$eventData]);
                if (!$event->result) {
                    continue;
                }
            }

            /**
             * Determine changes from result
             */
            extract((array)$eventData);
            $changes[] = [
                'column' => $column,
                'before' => $before,
                'after' => $after,
            ];
        }

        /**
         * Make combinations
         */
        if ($combinations = $this->config('combinations')) {
            $changes = $this->makeChangelogCombinations($entity, $changes, $combinations);
        }

        return $this->_changes = $changes;
    }

    public function makeChangelogCombinations(EntityInterface $entity, array $changes, array $combinations)
    {
        $indexedByColumn = collection($changes)->indexBy('column')->toArray();
        $removeKeys = [];
        $foreignKeys = $this->_associationsForForeignKey();
        foreach ($combinations as $name => $settings) {
            $settings = $this->_normalizeCombinationSettings($settings);

            $values = [];
            foreach ($settings['columns'] as $column) {
                $removeKeys[] = $column;

                if ($association = Hash::get($foreignKeys, $column)) {
                    $property = $association->property();
                    if (isset($indexedByColumn[$property])) {
                        $values['before'][$column] = $indexedByColumn[$property]['before'];
                        $values['after'][$column] = $indexedByColumn[$property]['after'];
                        $removeKeys[] = $property;
                    } elseif (isset($indexedByColumn[$column])) {
                        $values['before'][$column] = $indexedByColumn[$column]['before'];
                        $values['after'][$column] = $indexedByColumn[$column]['after'];
                    } else {
                        $values['before'][$column] = $entity->get($column);
                        $values['after'][$column] = $entity->get($column);
                    }
                } elseif (isset($indexedByColumn[$column])) {
                    $values['before'][$column] = $indexedByColumn[$column]['before'];
                    $values['after'][$column] = $indexedByColumn[$column]['after'];
                } else {
                    $values['before'][$column] = $entity->get($column);
                    $values['after'][$column] = $entity->get($column);
                }
            }

            if (isset($settings['convert'])) {
                $convert = $settings['convert'];
                $converted = $convert($name, $values['before'], $values['after']);
            } else {
                $converted = [
                    'column' => $name,
                    'before' => implode(' ', array_filter($values['before'])),
                    'after' => implode(' ', array_filter($values['after'])),
                ];
            }

            /**
             * Filters converted value not equal to. also remove keys from indexedColumn
             * to prevent changes remaining.
             */
            if ($converted['before'] == $converted['after']) {
                unset($combinations[$name]);
                continue;
            }
            $indexedByColumn[$name] = $converted;
        }

        $removeKeys = array_diff($removeKeys, array_keys($combinations));
        $indexedByColumn = array_diff_key($indexedByColumn, array_flip($removeKeys));
        return array_values($indexedByColumn);
    }

    protected function _normalizeCombinationSettings($settings)
    {
        if (!is_array($settings)) {
            throw new UnexpectedValueException(__d('changelog', 'Changelog: `combinations` option should be array'));
        }

        /**
         * If numric keys e.g. ['first_name', 'last_name'] given, Handles it
         * as a list of columns.
         */
        if (Hash::numeric(array_keys($settings))) {
            $settings = ['columns' => $settings];
        }

        if (!isset($settings['columns']) || !is_array($settings['columns'])) {
            throw new UnexpectedValueException(__d('changelog', 'Changelog: `combinations` option should have `columns` key and value as array of columns'));
        }

        return $settings;
    }

    /**
     * afterSave callback.
     * This logs entities when `onAfterSave` option was turned on.
     *
     * {@inheritdoc}
     */
    public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options)
    {
        if ($this->config('saveChangelogOnAfterSave')) {
            $this->saveChangelog($entity, $this->_changes);
        }
    }

    /**
     * Saves changelogs for entity.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity object to log changes.
     * @return \Cake\Datasource\EntityInterface|bool Entity object when logged otherwise `false`.
     */
    public function saveChangelog(EntityInterface $entity, $changes = [])
    {
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
            'model' => $this->_table->alias(),
            'foreign_key' => $entity->get($this->_table->primaryKey()),
            'is_new' => $entity->isNew(),
            'changelog_columns' => $changes,
        ]);
        $options = new ArrayObject([
            'associated' => 'ChangelogColumns',
            'atomic' => false
        ]);

        $Changelogs = $this->getChangelogTable();
        return $this->_table->dispatchEvent('Changelog.saveChangelogRecords', compact('Changelogs', 'data', 'options'))->result;
    }

    /**
     * Helper method to get table associations array
     * indexed by these properties.
     *
     * @return \Cake\ORM\Association[]
     */
    protected function _associationsIndexedByProperty()
    {
        return collection($this->_table->associations())
            ->combine(function ($association) {
                return $association->property();
            }, function ($association) {
                return $association;
            })->toArray();
    }

    /**
     * Helper method to get associations array that table has
     * foreign key (means BelongsTo) indexed by foreign key.
     *
     * @return \Cake\ORM\Association[]
     */
    protected function _associationsForForeignKey()
    {
        return collection($this->_table->associations())
            ->filter(function ($association) {
                return $association instanceof BelongsTo;
            })->combine(function ($association) {
                return $association->foreignKey();
            }, function ($association) {
                return $association;
            })->toArray();
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
            'Changelog.convertValues' => 'convertChangelogValues',
            'Changelog.filterChanges' => 'filterChanges',
            'Changelog.saveChangelogRecords' => 'saveChangelogRecords',
        ];
    }

    /**
     * Default convert process
     *
     * @param \Cake\Event\Event $event The event for callback
     * @param ArrayObject $data The event data. contains:
     *                          - entity
     *                          - isForeignKey
     *                          - isAssociation
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
         * @var bool $isForeignKey
         * @var bool $isAssociation
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
        if ($this->config('convertDatetimeColumns') && !$isAssociation && isset($columnDef['type'])) {
            switch ($columnDef['type']) {
                case 'date':
                case 'datetime':
                case 'time':
                // For Postgres
                case 'timestamp':
                    $baseType = $table->schema()->baseColumnType($column);
                    if ($baseType && Type::map($baseType)) {
                        $driver = $table->connection()->driver();
                        $before = Type::build($baseType)->toPHP($before, $driver);
                        $after = Type::build($baseType)->toPHP($after, $driver);
                    }
                    break;
            }
        }

        /**
         * Converts foreign keys. This converts belongsTo ID columns to associated
         * entity. Then it takes display field for the table.
         */
        if ($isForeignKey && $this->config('convertForeignKeys')) {
            if ($this->config('exchangeForeignKey')) {
                unset($data['beforeValues'][$column]);
                unset($data['afterValues'][$column]);
                $column = $association->property();
                $data['column'] = $column;
                $this->_combinationColumns[] = $column;
            }

            $before = $association->findById($before)->first(); 
            $after = $association->findById($after)->first();
            $before = $this->convertAssociationChangeValue($before, $association, 'before');
            $after = $this->convertAssociationChangeValue($after, $association, 'after');
        }

        /**
         * Converts associations
         */
        $converter = $this->config('convertAssociations');
        if ($isAssociation && $converter) {
            /** 
             * If array was given, handles it as whitelist of associations
             */
            if (!is_array($converter) || is_callable($converter) || in_array($column, $converter)) {
                $before = $this->convertAssociationChangeValue($before, $association, 'before');
                $after = $this->convertAssociationChangeValue($after, $association, 'after');
            }
        }

        /**
         * Modifies event data
         */
        $data['before'] = $before;
        $data['after'] = $after;
        $data['beforeValues'][$column] = $before;
        $data['afterValues'][$column] = $after;
    }

    /**
     * Default converter for association values
     */
    public function convertAssociationChangeValue($value, $association, $kind)
    {
        if (!$value) {
            return is_array($value) ? null : $value;
        }

        $isMany = in_array($association->type(), [Association::MANY_TO_MANY, Association::ONE_TO_MANY]);
        $property = $association->property();

        /**
         * Call actual converter. callable can be set with `convertAssociations`
         * option.
         */
        $converter = $this->config('convertAssociations');
        $callable = is_callable($converter) ? $converter : [$this, 'defaultConvertAssociation'];
        $arguments = [$property, $value, $kind, $association, $isMany];

        return call_user_func_array($callable, $arguments);
    }

    /**
     * Default converter for association values.
     *
     * @param string $property association property name
     * @param mixed $value expects EntityInterface/EntityInterface[]
     * @param string $kind either 'before'/'after'
     * @param \Cake\ORM\Association $association association object for the value
     * @param boolean $isMany true => [hasMany, belongsToMany] false => [hasOne, belongsTo]
     * @param array $beforeValue association original values. indexed by association properties.
     * @return mixed converted value
     */
    public function defaultConvertAssociation($property, $value, $kind, $association, $isMany)
    {
        if (!$value) {
            return null;
        }
        $displayField = $association->displayField();

        // hasMany, belongsToMany
        if ($isMany) {
            $values = (array)$value;
            return implode(', ', collection($values)
                ->sortBy($displayField, SORT_ASC, SORT_NATURAL)
                ->extract($displayField)
                ->filter()
                ->toArray());
        // hasOne, belongsTo
        } else {
            if (!$value instanceof EntityInterface) {
                return $value;
            }

            if ($kind === 'before') {
                return $value->getOriginal($displayField);
            } else {
                return $value->get($displayField);
            }
        }
    }

    /**
     * Default filter
     *
     * @param \Cake\Event\Event $event The event for callback
     * @param ArrayObject $data The event data. contains:
     *                          - entity
     *                          - isForeignKey
     *                          - isAssociation
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
         * @var bool $isForeignKey
         * @var bool $isAssociation
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
         * Filter e.g. null != ''
         */
        if ($this->config('equalComparison')) {
            return $before != $after;
        }

        /**
         * Filter foreign keys
         */
        if ($this->config('filterForeignKeys')) {
            return !$isForeignKey;
        }

        return true;
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
         * Save changes to database
         */
        if ($this->config('autoSave')) {
            $changelog = $Changelogs->newEntity((array)$data);
            return $Changelogs->save($changelog, (array)$options);
        }
    }

}
