<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Node extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ip', 'port', 'protocol', 'access_token', 'install_id'
    ];

    protected $hidden = [
        'ip', 'port', 'protocol', 'access_token', 'updated_at'
    ];

    /**
     * Scope to find the node by its install_id
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param String $installId
     * @return Node
     */

    public function scopeFindByInstallIdOrFail ($query, String $installId) : Node
    {
        return $query->where('install_id', '=', $installId)->firstOrFail();
    }

    public function nodeMeta()
    {
        return $this->hasMany(NodeMeta::class);
    }

    public function accessor () : String
    {
        return sprintf('%s://%s:%d', $this->protocol, $this->ip, $this->port);
    }

    public function user ()
    {
        return $this->belongsTo(User::class);
    }
}
