<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Libraries\Utility;

class NodeMeta extends Model
{

    protected $fillable = ['node_id', 'meta_key', 'value_type', 'meta_value'];


    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function scopeLoadMeta($query, $node, $key = '', $throwsException = false)
    {
        if (empty($key))
            return $node->nodeMeta;

        if ($throwsException)
            return $query->where(['node_id' => $node->id, 'meta_key' => $key])->firstOrFail();

        return $query->where(['node_id' => $node->id, 'meta_key' => $key])->first();
    }

    public function getValue()
    {
        $value = $this->meta_value;
        if (in_array($this->value_type, Utility::$metaDataTypes))
            settype($value, $this->value_type);

        return $value;
    }

    public static function addOrUpdateMeta($node, $key, $value)
    {
        $type = gettype($value);
        $type = in_array($type, Utility::$metaDataTypes) ? $type : 'string';

        if (!empty(static::loadMeta($node, $key)->all()))
        {

            /** @var NodeMeta $nodeMeta */
            $nodeMeta = NodeMeta::loadMeta($node, $key)->first();
            $nodeMeta->meta_value = $value;
            $nodeMeta->value_type = $type;
            $nodeMeta->save();
            return;
        }

        static::create([
            'node_id' => $node->id,
            'meta_key' => $key,
            'value_type' => $type,
            'meta_value' => $value
        ]);
    }

    public static function deleteMeta (Node $node, String $key)
    {
        $nodeMeta = static::loadMeta($node, $key)->first();
        if (! empty($nodeMeta))
            $nodeMeta->delete();
    }
}
