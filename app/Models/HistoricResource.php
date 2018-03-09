<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistoricResource extends BaseModel
{
    protected $fillable = [ 'user_id', 'model', 'resource', 'created_at', 'updated_at' ];

    public static function createCopy(Model $model, array $relationships, User $user = null)
    {
        return self::create([
            'user_id' => $user !== null ? $user->id : null,
            'model' => $model->getMorphClass(),
            'resource' => $model::withRelationships($relationships, $model->id)->toJson(),
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at
        ]);
    }
}
