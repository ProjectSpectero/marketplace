<?php

namespace App;

use App\Constants\OrderResourceType;
use App\Errors\FatalException;
use App\Models\Opaque\CAInfo;
use App\Models\Traits\HasOrders;
use Illuminate\Database\Eloquent\SoftDeletes;

class Node extends BaseModel
{
    use HasOrders;

    protected $fillable = [
        'ip', 'port', 'protocol', 'access_token', 'install_id', 'status',' user_id'
    ];

    protected $hidden = [
        'app_settings', 'system_config', 'install_id', 'access_token', 'updated_at', 'deleted_at'
    ];

    protected $with = [
        'services'
    ];

    protected $casts = [
        'app_settings' => 'array',
        'system_config' => 'array',
        'system_data' => 'array'
    ];

    public $searchAble = [
        'ip', 'install_id', 'friendly_name', 'cc', 'asn', 'market_model', 'status', 'id', 'group_id'
    ];

    // Why alongside table names? Because this is used on a `select()` call on a joined data array instead of directly.
    public static $publicFields = [
        'nodes.id', 'nodes.status', 'nodes.friendly_name', 'nodes.market_model', 'nodes.price', 'nodes.city', 'nodes.cc', 'nodes.asn', 'nodes.group_id', 'nodes.created_at'
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

    public function meta ()
    {
        return $this->hasMany(NodeMeta::class);
    }

    public function services ()
    {
        return $this->hasMany(Service::class);
    }

    public function group ()
    {
        return $this->belongsTo(NodeGroup::class);
    }

    public function ipAddresses ()
    {
        return $this->hasMany(NodeIPAddress::class);
    }

    public function getOrders (String $status = null)
    {
        return $this->genericGetOrders($this, $status, OrderResourceType::NODE);
    }

    public function getEngagements (String $status = null)
    {
        $query = OrderLineItem::join('nodes', 'nodes.id', '=', 'order_line_items.resource')
            ->join('orders', 'orders.id', '=', 'order_line_items.order_id')
            ->where('order_line_items.type', OrderResourceType::NODE)
            ->where('order_line_items.resource', $this->id);

        if ($status != null)
            $query->where('orders.status', $status);

        $query->select([ 'order_line_items.*' ]);

        return $query;
    }

    public function getConfigKey (string $key) : string
    {
        foreach ($this->system_config as $config)
        {
            if ($config['key'] == $key)
                return $config['value'];
        }

        throw new FatalException("Could not find requested key ($key) in node $this->id");
    }

    public function getCertificate (string $type = 'ca') : CAInfo
    {
        // It's a list, index is nothing but numbers
        $ret = new CAInfo();

        $ret->blob = $this->getConfigKey("crypto.$type.blob");
        $ret->password = $this->getConfigKey("crypto.$type.password");

        return $ret;
    }

}
