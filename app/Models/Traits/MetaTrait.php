<?php

namespace App\Traits;

use App\Libraries\Utility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait MetaTrait
{
    /**
     * Custom scope that returns Model meta_value by key
     *
     * @param Model $model
     * @param string $key
     * @param bool $throwsException
     *
     * @return Collection ModelMeta
     */

    public function scopeLoadMeta($query, Model $model, $key = '', $throwsException = false)
    {
        $modelName = str_singular($model->getTable());
        $modelMeta = $modelName . 'Meta';

        if (empty($key))
            return $model->$modelMeta;

        $constraint = $query->where([$modelName.'_id' => $model->id, 'meta_key' => $key]);

        return $throwsException ? $constraint->firstOrFail() : $constraint->first();
    }

    public static function addOrUpdateMeta (Model $model, String $key, $value)
    {
        $modelName = str_singular($model->getTable());
        $resolvedType = gettype($value);
        $type = in_array($resolvedType, Utility::$metaDataTypes) ? $resolvedType : 'string';
        $modelMeta = null;

        try
        {
            $modelMeta = static::loadMeta($model, $key, true);
            $modelMeta->meta_value = $value;
            $modelMeta->value_type = $type;
            $modelMeta->save();
        }
        catch (ModelNotFoundException $silenced)
        {
            $modelMeta = static::create([
                $modelName.'_id' => $model->id,
                'meta_key' => $key,
                'value_type' => $type,
                'meta_value' => $value
            ]);
        }

        return $modelMeta;
    }

    public static function deleteMeta (Model $model, String $key)
    {
        $modelMeta = static::loadMeta($model, $key)->first();
        if (! empty($modelMeta))
            $modelMeta->delete();
    }

    public function getValue()
    {
        $value = $this->meta_value;
        if (in_array($this->value_type, Utility::$metaDataTypes))
            settype($value, $this->value_type);

        return $value;
    }
}
