<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NodeGroup extends Model
{
    public function nodes ()
    {
        return $this->hasMany(Node::class, 'group_id');
    }

    public static function findForUser (int $id)
    {
        return static::where('user_id', $id);
    }
}
