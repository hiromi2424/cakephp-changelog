<?php
namespace Changelog\Model\Entity;

use Cake\ORM\Entity;

/**
 * Changelog Entity
 *
 * @property int $id
 * @property string $model
 * @property string $foreign_key
 * @property bool $is_new
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 *
 * @property \Changelog\Model\Entity\ChangelogColumn[] $changelog_columns
 */
class Changelog extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false
    ];
}
