<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\NodeMarketModel;
use App\Constants\NodeStatus;
use App\Errors\UserFriendlyException;
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
        $fields = ['market_model', 'price', 'asn', 'city', 'cc'];
        $price = $request->get('price');
        $operator = $request->get('operator');
        $asn = $request->get('asn');
        $city = $request->get('city');
        $cc = $request->get('cc');
        $service_types = $request->get('service_types');
        $numberOfIPs = $request->get('no_of_ips');

        $query = \DB::table('nodes')
            ->join('services', 'services.node_id', '=', 'nodes.id')
            ->join('service_ip_address', 'service_ip_address.service_id', '=', 'services.id');

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
                    if ($operator !== '=' && $operator !== NodeMarketModel::UNLISTED
                    && ! in_array($value, NodeMarketModel::getConstants()))
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    $query->where($field, $operator, $value);
                    break;

                // TODO: write parsers for the rest

            }
        }

        /*
            ->where('asn', $asn)
            ->where('city', $city)
            ->whereIn('cc', $cc)
            ->whereIn('services.type', $service_types);

            // This is wrong, and we CAN NOT filter in PHP. EVERYTHING needs to come from SQL, cannot sustain loops on these tables (too much data eventually). See requirement again.
            if ($node->count() > $numberOfIPs)
                return $node->get($fields);
        */

        return null;
    }
}
