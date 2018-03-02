<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MarketplaceController extends Controller
{
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

        $node = \DB::table('nodes')
            ->whereIn('market_model', \App\Constants\NodeMarketModel::getConstraints())
            ->where('price', $operator, $price)
            ->where('asn', $asn)
            ->where('city', $city)
            ->whereIn('cc', $cc)
            ->join('services', 'services.node_id', '=', 'nodes.id')
            ->whereIn('services.type', $service_types)
            ->join('service_ip_address', 'service_ip_address.service_id', '=', 'services.id');

        if ($node->count() > $numberOfIPs)
            return $node->get($fields);

        return null;
    }
}
