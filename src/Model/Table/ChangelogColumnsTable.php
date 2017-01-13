<?php
namespace Changelog\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ChangelogColumns Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Changelogs
 *
 * @method \Changelog\Model\Entity\ChangelogColumn get($primaryKey, $options = [])
 * @method \Changelog\Model\Entity\ChangelogColumn newEntity($data = null, array $options = [])
 * @method \Changelog\Model\Entity\ChangelogColumn[] newEntities(array $data, array $options = [])
 * @method \Changelog\Model\Entity\ChangelogColumn|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Changelog\Model\Entity\ChangelogColumn patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Changelog\Model\Entity\ChangelogColumn[] patchEntities($entities, array $data, array $options = [])
 * @method \Changelog\Model\Entity\ChangelogColumn findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ChangelogColumnsTable extends Table
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

        $this->table('changelog_columns');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Changelogs', [
            'foreignKey' => 'changelog_id',
            'joinType' => 'INNER',
            'className' => 'Changelog.Changelogs'
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
            ->requirePresence('column', 'create')
            ->notEmpty('column');

        $validator
            ->allowEmpty('before');

        $validator
            ->allowEmpty('after');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['changelog_id'], 'Changelogs'));

        return $rules;
    }
}
