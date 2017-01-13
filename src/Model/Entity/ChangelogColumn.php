<?php
namespace Changelog\Model\Entity;

use Cake\ORM\Entity;

/**
 * ChangelogColumn Entity
 *
 * @property int $id
 * @property int $changelog_id
 * @property string $column
 * @property string|resource $before
 * @property string|resource $after
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 *
 * @property \Changelog\Model\Entity\Changelog $changelog
 */
class ChangelogColumn extends Entity
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
