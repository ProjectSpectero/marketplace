<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NodeMeta extends Model
{

  protected $fillable = ['node_id', 'meta_key', 'value_type', 'meta_value'];

  protected static $dataTypes = ['boolean', 'integer', 'double', 'float', 'string'];

  public function node()
  {
    return $this->belongsTo(Node::class);
  }

  public function scopeLoadMeta($query, $node, $key = '')
  {
    if (empty($key)) {
      return $node->nodeMeta;
    }

    return $query->where(['node_id' => $node->id, 'meta_key' => $key])->get();
  }

  public function getValue()
  {
    $value = $this->meta_value;
    if (in_array($this->value_type, $this->dataTypes)) {
      settype($value, $this->value_type);
    } 

    return $value;
  }

  public static function addOrUpdateMeta($node, $key, $value)
  {
    $type = gettype($value);
    $type = in_array($type, self::$dataTypes) ? $type : 'string';

    if (!empty(static::loadMeta($node, $key)->all())) {
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
}
