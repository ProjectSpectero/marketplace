<?php

namespace App\Traits;

use App\Errors\FatalException;
use App\Libraries\Utility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Debug\Exception\FatalErrorException;

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
        $modelName = $this::getModelName($model);
        $modelMeta = $modelName . 'Meta';

        if (empty($key))
            return $model->$modelMeta;

        $constraint = $query->where([$modelName.'_id' => $model->id, 'meta_key' => $key]);

        return $throwsException ? $constraint->firstOrFail() : $constraint->first();
    }

    public static function addOrUpdateMeta (Model $model, String $key, $value)
    {
        $modelName = self::getModelName($model);
        $modelMeta = null;
        $resolvedType = gettype($value);
        $type = in_array($resolvedType, Utility::$metaDataTypes) ? $resolvedType : 'string';

        if ($type == 'string' && strlen($value) > 255)
            throw new FatalException();

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
        $modelMeta = static::loadMeta($model, $key);
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

    private static function getModelName(Model $model)
    {
        return str_singular($model->getTable());
    }
}
