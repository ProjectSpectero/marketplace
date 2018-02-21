<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Node extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'ip', 'port', 'protocol', 'access_token', 'install_id', 'status'
    ];

    protected $hidden = [
        'ip', 'port', 'protocol', 'install_id', 'access_token', 'updated_at'
    ];

    public $searchAble = [
        'ip', 'install_id', 'friendly_name', 'cc', 'asn'
    ];

    /**
     * Scope to find the node by its install_id
     * This is an indexed query
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param String $installId
     * @return Node
     */

    public function scopeFindByInstallIdOrFail ($query, String $installId) : Node
    {
        return $query->where('install_id', '=', $installId)->firstOrFail();
    }

    /**
     * Scope to find the node by its IP Address
     * This is an indexed query
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param String $ipAddress
     * @return Node
     */

    public function scopeFindByIPAddressOrFail ($query, String $ipAddress) : Node
    {
        return $query->where('ip', '=', $ipAddress)->firstOrFail();
    }

    /**
     * Scope to find the node by its IP Address
     * This is an indexed query
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param String $installId
     * @param String $ipAddress
     * @return Node
     */

    public function scopeFindByIPOrInstallIdOrFail ($query, String $installId, String $ipAddress) : Node
    {
        return $query->where('ip', '=', $ipAddress)
            ->orWhere('install_id', '=', $installId)
            ->firstOrFail();
    }

    public function getActiveOrders(Node $node)
    {
        return \DB::table('orders')
            ->join('order_line_items', 'orders.id', '=', 'order_line_items.id')
            ->select('orders.*', 'order_line_items.*')
            ->where([
                ['status', '=', 'active'],
                ['order_line_items.type', '=', 'NODE'],
                ['order_line_items.resource', '=', (string) $node->id]
            ])
            ->get();
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

    public function services ()
    {
        return $this->hasMany(Service::class);
    }

    public function group ()
    {
        return $this->belongsTo(NodeGroup::class);
    }
}
