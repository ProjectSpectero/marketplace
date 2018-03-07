<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\NodeMarketModel;
use App\Constants\NodeStatus;
use App\Constants\ServiceType;
use App\Errors\UserFriendlyException;
use App\Libraries\PaginationManager;
use App\Libraries\Utility;
use App\Node;
use function GuzzleHttp\default_ca_bundle;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MarketplaceController extends Controller
{
    /*
     * Formal definition of the marketplace query language:
     * These rules MAY NOT always exist together, no field is mandatory. We'll only apply filters if the specific rule is encountered.
     * Strict validation is needed, as is normal elsewhere on this app. The constraints / ONLY supported value claims need to be followed

        {
            "rules": [
                {
                    "field": "nodes.market_mode",
                    "operator": "=", <-- ONLY supported value
                    "value": "LISTED_SHARED | LISTED_DEDICATED" <-- ONLY supported value(s), if rule is missing, we simply need to filter out UNLISTED and already sold LISTED_DEDICATED (missing currently)
                },
                {
                    "field": "nodes.price",
                    "operator": "= | < | > | <= | >=", <-- ONLY supported value(s)
                    "value": "10.5" <-- NUMERIC (float) only.
                },
                {
                    "field": "nodes.asn",
                    "operator": "=", <-- ONLY supported value(s)
                    "value": "9001" <-- INTEGER only.
                },
                {
                    "field": "nodes.city",
                    "operator": "=", <-- ONLY supported value(s)
                    "value": "Seattle" <-- Alpha-Dash String only.
                },
                {
                    "field": "nodes.cc",
                    "operator": "IN", <-- ONLY supported value(s)
                    "value": [ "JP", "SG" ] <-- Array, min 1 element if this rule is given.
                },
                {
                    "field": "service.type",
                    "operator": "IN", <-- ONLY supported value(s)
                    "value": [ "HTTPProxy", "OpenVPN" ] <-- Array only, min 1 element if this rule is given. Valid values in the ServiceType constants array. The relationship here is however not an OR, but rather an AND (i.e: node MUST have ALL of these services.)
                },
                {
                    "field": "service.ips.count",
                    "operator": "= | >= | >", <-- ONLY supported value(s)
                    "value": "9001" <-- INTEGER only.
                }
            ]
        }
     */

    public function search(Request $request)
    {

        $query = Node::query();

        // Never pick up on unlisted nodes, and only return nodes that are verified/confirmed.
        // Don't return nodes that are a part of a group.
        $query->where('nodes.market_model', '!=', NodeMarketModel::UNLISTED)
            ->where('nodes.status', NodeStatus::CONFIRMED)
            ->where('nodes.group_id', null);

        foreach ($request->get('rules', []) as $rule)
        {
            $value = $rule['value'];
            $operator = $rule['operator'];
            $field = $rule['field'];

            switch ($field)
            {
                case 'nodes.price':
                    if (! in_array($operator, [ '=', '<', '>', '<=', '>=' ]))
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    $query->where($field, $operator, $value);
                    break;

                case 'nodes.asn':
                    if ($operator !== '=' && ! is_int($value))
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    $query->where($field, $operator, $value);
                    break;

                case 'nodes.market_model':
                    if ($operator !== '=' || $value == NodeMarketModel::UNLISTED
                    || ! in_array($value, NodeMarketModel::getConstants()))
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    $query->where($field, $operator, $value);
                    break;

                case 'nodes.city':
                    if ($operator !== '=' || ! Utility::alphaDashRule($value))
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    $query->where($field, $operator, $value);
                    break;

                case 'nodes.cc':
                    if (!is_array($value) || sizeof($value) < 1)
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    $query->whereIn($field, $value);
                    break;

                case 'nodes.service_type':
                    if (! is_array($value) || count($value) == 0
                        || $operator !== 'ALL')
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    // Join, because now it's needed.
                    $query->leftJoin('services', 'services.node_id', '=', 'nodes.id');

                    foreach ($value as $serviceType)
                    {
                        // SQLI prevention, we have a raw call below.
                        // This is a classic example of a set within sets query
                        if (! in_array($serviceType, ServiceType::getConstants()))
                            throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                        $query->havingRaw(sprintf('sum(services.type="%s") > 0', $serviceType));
                    }

                    break;

                case 'nodes.ip_count':
                    if (! in_array($operator, ['=', '>=', '>']) || ! is_numeric($value))
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    $query->leftJoin('node_ip_addresses', 'node_ip_addresses.node_id', '=', 'nodes.id');

                    $query->havingRaw('count(node_ip_addresses.id) > ' . $value);
                    break;

                default:
                    throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);
                    break;
            }
        }

        $query->groupBy([ 'nodes.id' ]);
        dd($query->toSql(), $query->get()->toArray());

        return PaginationManager::paginate($request, $query);
    }
}

