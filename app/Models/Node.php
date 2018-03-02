<?php

namespace App;

use App\Constants\OrderResourceType;
use App\Models\Traits\HasOrders;
use Illuminate\Database\Eloquent\SoftDeletes;

class Node extends BaseModel
{
    use HasOrders;
    use SoftDeletes;

    protected $fillable = [
        'ip', 'port', 'protocol', 'access_token', 'install_id', 'status',' user_id'
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

    public function getOrders (String $status = null)
    {
        return $this->genericGetOrders($this, $status);
    }

    public function getEngagements (String $status)
    {
        return $this->join('order_line_items', 'nodes.id', '=', 'order_line_items.resource')
            ->join('orders', 'orders.id', '=', 'order_line_items.order_id')
            ->where('order_line_items.type', OrderResourceType::NODE)
            ->where('order_line_items.resource', $this->id)
            ->where('orders.status', $status);
    }
}
