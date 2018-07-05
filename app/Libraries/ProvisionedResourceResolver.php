<?php

namespace App\Libraries;

use App\Constants\Errors;
use App\Constants\OrderResourceType;
use App\Constants\OrderStatus;
use App\Constants\ServiceType;
use App\EnterpriseResource;
use App\Errors\FatalException;
use App\Errors\UserFriendlyException;
use App\Libraries\Services\OpenVPNConfigResolver;
use App\Node;
use App\NodeGroup;
use App\Order;
use App\OrderLineItem;
use App\Service;

class ProvisionedResourceResolver
{
    public static function resolve (Order $order) : array
    {
        if ($order->status != OrderStatus::ACTIVE)
            throw new UserFriendlyException(Errors::ORDER_NOT_ACTIVE_YET);

        $resources = [];

        foreach ($order->lineItems as $item)
        {
            // Do NOT return access details for cancelled/pending things.
            if ($item->status == OrderStatus::ACTIVE)
                $resources[] = self::getConnectionResources($item);
        }


        $out = [
            'accessor' => $order->accessor,
            'resources' => $resources
        ];

        return $out;
    }

    private static function resolveService(Service $service, Order $againstOrder)
    {
        // Why a dedicated function? Because not all services can be resolved in the same way.
        $serviceType = $service->type;

        $ret = [
            'type' => $serviceType,
            'resource' => [
                'accessConfig' => null,
                'accessCredentials' => 'SPECTERO_USERNAME_PASSWORD',
                'accessReference' => null
            ]
        ];

        switch ($serviceType)
        {
            case ServiceType::HTTPProxy:
                $ret['resource'] = $service->connection_resource;
                break;

            case ServiceType::OpenVPN:
                $ret['resource']['accessConfig'] = OpenVPNConfigResolver::resolveOrderConfig($service->node, $againstOrder);
                break;

        }

        return $ret;
    }

    private static function resolveNode (Node $node, Order $againstOrder)
    {
        $ret = [];

        foreach ($node->services as $service)
        {
            $ret[] = self::resolveService($service, $againstOrder);
        }

        return $ret;
    }

    private static function getConnectionResources(OrderLineItem $item)
    {
        $connectionResources = [
            'id' => $item->id,
            'resource' => [
                'id' => $item->resource,
                'type' => $item->type,
                'reference' => []
            ]
        ];

        switch ($item->type)
        {
            case OrderResourceType::NODE:
                $node = Node::find($item->resource);
                $connectionResources['resource']['reference'][] = self::resolveNode($node, $item->order);
                break;

            case OrderResourceType::NODE_GROUP:
                $nodeGroup = NodeGroup::find($item->resource);
                foreach ($nodeGroup->nodes as $node)
                {
                    $connectionResources['resource']['reference'][] = self::resolveNode($node, $item->order);
                }
                break;

            case OrderResourceType::ENTERPRISE:
                // Our magnum opus
                $enterpriseResources = EnterpriseResource::findForOrderLineItem($item)->get();

                $skeletonResource = [
                    'accessConfig' => null,
                    'accessCredentials' => 'SPECTERO_USERNAME_PASSWORD',
                    'accessReference' => []
                ];


                /** @var EnterpriseResource $enterpriseResource */
                foreach ($enterpriseResources as $enterpriseResource)
                {
                    $skeletonResource['accessReference'][] = $enterpriseResource->accessor();
                }

                $connectionResources['resource']['reference'][] = [
                    'type' => ServiceType::HTTPProxy,
                    'resource' => $skeletonResource
                ];

                break;

            default:
                throw new FatalException("This resource resolver does NOT know how to resolve for $item->type");
        }

        return $connectionResources;
    }
}