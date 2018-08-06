<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use ErrorException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionMethod;

class BaseModel extends Model
{
    public static function findForUser (int $id)
    {
        return static::where('user_id', $id);
    }

    public static function findOrLogAndFail(int $id, array $payload = [])
    {
        try
        {
            $model = static::findOrFail($id);
        }
        catch (ModelNotFoundException $silenced)
        {
            Log::warning("Couldn't find id -> " . $id . ' when looking for ' . get_called_class() . ' (despite expecting to find it).', [ 'ctx' => $payload ]);
            throw $silenced;
        }

        return $model;
    }

    public function scopeNoEagerLoads($query)
    {
        return $query->setEagerLoads([]);
    }

    public function scopeWithRelationships($query, array $relationships, int $id)
    {
        return $query->with($relationships)->where('id', $id)->get();
    }

    public function relationships(array $loadOnly = [])
    {

        $model = new static;

        $relationships = [];

        foreach((new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
        {
            if ($method->class != get_class($model) ||
                !empty($method->getParameters()) ||
                $method->getName() == __FUNCTION__)
                    continue;

            try
            {
                $return = $method->invoke($model);

                if ($return instanceof Relation)
                {
                    if (! empty($loadOnly))
                    {
                        foreach ($loadOnly as $relationship)
                        {
                            if ($relationship == $method->getName())
                            {
                                $relationships[$method->getName()] = [
                                    'type' => (new ReflectionClass($return))->getShortName(),
                                    'model' => (new ReflectionClass($return->getRelated()))->getName()
                                ];
                            }

                        }
                    }
                    else
                    {
                        $relationships[$method->getName()] = [
                            'type' => (new ReflectionClass($return))->getShortName(),
                            'model' => (new ReflectionClass($return->getRelated()))->getName()
                        ];
                    }


                }
            }
            catch(ErrorException $e)
            {

            }
        }

        return $relationships;
    }
}
