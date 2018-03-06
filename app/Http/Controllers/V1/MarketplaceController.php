<?php

namespace App\Http\Controllers\V1;

use App\Constants\Errors;
use App\Constants\NodeMarketModel;
use App\Constants\NodeStatus;
use App\Constants\ServiceType;
use App\Errors\UserFriendlyException;
use App\Libraries\Utility;
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

                    foreach ($value as $serviceType)
                        $query->where($field, $operator, $serviceType);

                    break;

                case 'service.ips.count':
                    if (! in_array($operator, ['=', '>=', '>']) || ! is_numeric($value))
                        throw new UserFriendlyException(Errors::FIELD_INVALID .':' . $field);

                    $query->select($field)->selectRaw('count(*)')
                        ->havingRaw('count(*) ' . $operator . ' ' . $value);
                    break;
                default:
                    dd($field);
                    break;


            }
        }

        return $query->toSql();

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

