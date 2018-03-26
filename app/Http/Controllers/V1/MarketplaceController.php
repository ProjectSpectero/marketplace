<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\NodeMarketModel;
use App\Constants\NodeStatus;
use App\Constants\OrderResourceType;
use App\Constants\OrderStatus;
use App\Constants\ResponseType;
use App\Constants\ServiceType;
use App\Errors\UserFriendlyException;
use App\Libraries\PaginationManager;
use App\Libraries\Utility;
use App\Node;
use App\NodeGroup;
use App\NodeIPAddress;
use function GuzzleHttp\default_ca_bundle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MarketplaceController extends V1Controller
{
    /*
     * Yes, the team is aware that this is a total shitshow. Rewrite incoming very shortly with ElasticSearch or Algolia (Post-MVP).
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
                    "operator": "RANGE", <-- ONLY supported value(s)
                    "value": { "start": 5, "end": 10 } <-- NUMERIC (float) only.
                },
                {
                    "field": "nodes.asn",
                    "operator": "IN",
                    "value": [ "12345" ]
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
                    "field": "nodes.service_type",
                    "operator": "IN", <-- ONLY supported value(s)
                    "value": [ "HTTPProxy", "OpenVPN" ] <-- Array only, min 1 element if this rule is given. Valid values in the ServiceType constants array. The relationship here is however not an OR, but rather an AND (i.e: node MUST have ALL of these services.)
                },
                {
                    "field": "nodes.ip_count",
                    "operator": "= | >= | >", <-- ONLY supported value(s)
                    "value": "9001" <-- INTEGER only.
                }
            ]
        }
     */

    public function search(Request $request)
    {
        $rules = [
            'rules' => 'sometimes|array',
            'rules.*.field' => 'required_with:rules',
            'rules.*.operator' => 'required_with:rules',
            'rules.*.value' => 'required_with:rules'
        ];
        $this->validate($request, $rules);

        $originalQuery = Node::query();

        // Never pick up on unlisted nodes, and only return nodes that are verified/confirmed.
        // Don't return nodes that are a part of a group.
        $originalQuery->where('nodes.market_model', '!=', NodeMarketModel::UNLISTED)
            ->where('nodes.status', NodeStatus::CONFIRMED);

        $query = clone $originalQuery;

        $includeGrouped = $request->has('includeGrouped') ? true : false;

        if (! $includeGrouped)
            $query->where('nodes.group_id', null);

        $sortParams = null;

        foreach ($request->get('rules', []) as $rule)
        {
            $value = $rule['value'];
            $operator = strtoupper($rule['operator']);
            $field = $rule['field'];

            if ($operator == 'SORT')
                $this->verifySort($sortParams, $value, $field);


            switch ($field)
            {
                case 'nodes.price':
                    if ( ! in_array($operator, [ 'SORT', 'RANGE' ]))
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    switch ($operator)
                    {
                        case 'SORT':
                            $sortParams = [
                                'field' => $field,
                                'value' => $value,
                                'predicate' => $field
                            ];
                            break;

                        default:
                            if (! is_array($value)
                                || ! isset($value['start'])
                                || ! isset($value['end'])
                                || ! is_numeric($value['start'])
                                || ! is_numeric($value['end'])
                                || $value['start'] > $value['end']
                            )
                                throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);
                                $query->whereBetween($field, [ $value['start'], $value['end'] ]);
                    }

                    break;

                case 'nodes.asn':
                    if ($operator !== 'IN' || ! is_array($value) || sizeof($value) < 1 || ! $this->is_int_array($value))
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    $query->whereIn($field, $value);
                    break;

                case 'nodes.market_model':
                    if ( ! in_array($operator, [ 'SORT', '=' ]))
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    switch ($operator)
                    {
                        case 'SORT':
                            $sortParams = [
                                'field' => $field,
                                'value' => $value,
                                'predicate' => $field
                            ];

                            break;

                        default:

                            if ($value == NodeMarketModel::UNLISTED
                                || ! in_array($value, NodeMarketModel::getConstants())
                            )
                                throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                            $query->where($field, $operator, $value);
                    }
                    break;

                case 'nodes.city':
                    if ($operator !== '=' || ! Utility::alphaDashRule($value))
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    $query->where($field, $operator, $value);
                    break;

                case 'nodes.cc':
                    if (! is_array($value) || sizeof($value) < 1 || $operator !== 'IN')
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

                        $query->havingRaw(sprintf('SUM(services.type = "%s") > 0', $serviceType));
                    }

                    break;

                case 'nodes.ip_count':
                    $query->leftJoin('node_ip_addresses', 'node_ip_addresses.node_id', '=', 'nodes.id');

                    switch ($operator)
                    {
                        case 'SORT':
                            $sortParams = [
                                'field' => $field,
                                'value' => $value,
                                'predicate' => 'count(node_ip_addresses.id)'
                            ];

                            break;

                        default:
                            if (! in_array($operator, ['=', '>=', '<=', '>', '<']) || ! is_int($value))
                                throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                            $query->havingRaw('count(node_ip_addresses.id) ' . $operator . ' ' . $value);
                    }
                    break;

                default:
                    throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);
                    break;
            }
        }

        $query->groupBy([ 'nodes.id' ]);

        if (is_array($sortParams))
        {
            switch ($sortParams['field'])
            {
                case 'nodes.ip_count':
                    $query->orderByRaw($sortParams['predicate'] . ' ' . $sortParams['value']);
                    break;

                default:
                    $query->orderBy($sortParams['predicate'], $sortParams['value']);
            }
        }


        $query->select(Node::$publicFields)
            ->noEagerLoads();

        $results = PaginationManager::internalPaginate($request, $query);

        $data = [];

        if ($results->total() != 0)
        {
            // Means we actually found something, let's go validate them.
            foreach ($results->items() as $node)
            {
                $groupId = $node->group_id;
                if ($groupId != null)
                {
                    $resource = NodeGroup::noEagerLoads()->find($groupId);
                    if ($resource != null)
                    {
                        $resource = $this->prepareGroup($resource);
                        $resource->type = OrderResourceType::NODE_GROUP;
                    }
                }
                else
                {
                    $resource = $this->prepareNode($node);
                    $resource->type = OrderResourceType::NODE;
                }

                // Builder happens when the scope needs to return null, lame.
                if ($resource != null)
                {
                    if ($resource instanceof Builder)
                        continue;

                    if ($resource->market_model == NodeMarketModel::LISTED_DEDICATED
                    && $resource->getEngagements(OrderStatus::ACTIVE)->count() != 0)
                        continue;
                }
                $data[] = $resource;
            }
        }

        $paginationParameters = $results->toArray();
        $paginationParameters['filtered'] = $results->total() - count($data);
        unset($paginationParameters['data']);

        return $this->respond($data, [], null, ResponseType::OK, [], $paginationParameters);
    }

    public function resource(Request $request, String $type, int $id): JsonResponse
    {
        // TODO: Figure out what to hide, and what to show.
        $data = null;
        switch ($type)
        {
            case 'node':
                $node = Node::noEagerLoads()->findOrFail($id);
                $data = $this->prepareNode($node);

                break;
            case 'group':
                $group = NodeGroup::noEagerLoads()->findOrFail($id);
                $data = $this->prepareGroup($group);
                break;
            default:
                throw new UserFriendlyException(Errors::RESOURCE_NOT_FOUND);
        }

        if ($data->market_model == NodeMarketModel::UNLISTED)
            throw new UserFriendlyException(Errors::UNAUTHORIZED, ResponseType::FORBIDDEN);

        return $this->respond($data->toArray());
    }

    // まだ心の準備ができていないからね
    private function prepareNode (Node $node, bool $groupExceptionOverride = false)
    {
        if (! $groupExceptionOverride && $node->status != NodeStatus::CONFIRMED)
        {
            throw new UserFriendlyException(Errors::UNAUTHORIZED, ResponseType::FORBIDDEN);
        }


        $ipCollection = [];
        foreach (NodeIPAddress::where('node_id', $node->id)->get() as $ip)
        {
            $ipCollection[] = [
                'id' => $ip->id,
                'asn' => $ip->asn,
                'city' => $ip->city,
                'cc' => $ip->cc
            ];
        }

        $node->ip_addresses = $ipCollection;
        $hiddenBase = [ 'ip', 'port', 'protocol', 'user_id', 'deleted_at' ];

        if ($groupExceptionOverride)
        {
            $hiddenBase = array_merge($hiddenBase, [
                'group_id', 'plan', 'market_model', 'price'
            ]);
        }

        $node->makeHidden($hiddenBase);

        return $node;
    }

    //俺も同じだよ
    private function prepareGroup (NodeGroup $group)
    {
        $nodes = [];
        foreach (Node::where('group_id', $group->id)->get() as $node)
        {
            $nodes[] = $this->prepareNode($node, true);
        }
        $group->nodes = $nodes;

        return $group;
    }

    private function is_int_array (Array $values): bool
    {
        foreach ($values as $value)
        {
            if (! is_int($value))
                return false;
        }
        return true;
    }

    private function verifySort ($sortParameters, String $value, String $field)
    {
        if ($sortParameters != null && ! in_array(strtoupper($value), [ 'ASC', 'DESC' ]))
            throw new UserFriendlyException(Errors::FIELD_INVALID . ':SORT:' . $field);
    }
}

