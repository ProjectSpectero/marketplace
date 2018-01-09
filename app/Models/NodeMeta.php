<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\MetaTrait;
use App\Libraries\Utility;

class NodeMeta extends Model
{

    use MetaTrait;

    protected $fillable = [ 'node_id', 'meta_key', 'value_type', 'meta_value' ];


    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
