<?php namespace Sugarcrm\Bean\Cache;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * FieldMetadata
 *
 * @property mixed          $options_list
 * @property integer        $id
 * @property string         $parent_type
 * @property string         $field_name
 * @property string         $display_name
 * @property string         $field_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\FieldMetadata whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\FieldMetadata whereParentType($value)
 * @method static \Illuminate\Database\Query\Builder|\FieldMetadata whereFieldName($value)
 * @method static \Illuminate\Database\Query\Builder|\FieldMetadata whereDisplayName($value)
 * @method static \Illuminate\Database\Query\Builder|\FieldMetadata whereFieldType($value)
 * @method static \Illuminate\Database\Query\Builder|\FieldMetadata whereOptionsList($value)
 * @method static \Illuminate\Database\Query\Builder|\FieldMetadata whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\FieldMetadata whereUpdatedAt($value)
 */
class FieldMetadata extends Eloquent
{
    protected $table    = 'field_metadatas';
    protected $fillable = array('parent_type', 'field_name', 'display_name', 'field_type', 'options_list');

    public function getOptionsListAttribute($value)
    {
        $value = json_decode($value, true);
        if (!is_array($value)) {
            $value = json_decode($value, true);
        }

        return $value;
    }

    public function setOptionsListAttribute($value)
    {
        if (is_string($value)) {
            $this->attributes['options_list'] = $value;

            return;
        }
        $this->attributes['options_list'] = json_encode($value);
    }

    public function findByModule($module)
    {
        return $this->where('parent_type', '=', $module)->get();
    }

}
