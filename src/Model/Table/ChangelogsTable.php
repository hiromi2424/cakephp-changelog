<?php
namespace Changelog\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Changelogs Model
 *
 * @property \Cake\ORM\Association\HasMany $ChangelogColumns
 *
 * @method \Changelog\Model\Entity\Changelog get($primaryKey, $options = [])
 * @method \Changelog\Model\Entity\Changelog newEntity($data = null, array $options = [])
 * @method \Changelog\Model\Entity\Changelog[] newEntities(array $data, array $options = [])
 * @method \Changelog\Model\Entity\Changelog|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Changelog\Model\Entity\Changelog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Changelog\Model\Entity\Changelog[] patchEntities($entities, array $data, array $options = [])
 * @method \Changelog\Model\Entity\Changelog findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ChangelogsTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('changelogs');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('ChangelogColumns', [
            'foreignKey' => 'changelog_id',
            'className' => 'Changelog.ChangelogColumns'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->requirePresence('model', 'create')
            ->notEmpty('model');

        $validator
            ->requirePresence('foreign_key', 'create')
            ->notEmpty('foreign_key');

        $validator
            ->boolean('is_new')
            ->requirePresence('is_new', 'create')
            ->notEmpty('is_new');

        return $validator;
    }

}
