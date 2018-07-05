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
            'items' => $resources
        ];

        return $out;
    }

    private static function getConnectionResources(OrderLineItem $item)
    {
        $connectionResources = [
            'id' => $item->id,
            'resource' => [
                'id' => $item->resource,
                'type' => $item->type,
                'reference' => null
            ]
        ];

        switch ($item->type)
        {
            case OrderResourceType::NODE:
                /** @var Node $node */
                $node = Node::find($item->resource);

                if($node->isMarketable())
                {
                    $connectionResources['resource']['reference'] = self::resolveNode($node, $item->order);
                }
                break;

            case OrderResourceType::NODE_GROUP:
                /** @var NodeGroup $nodeGroup */
                $nodeGroup = NodeGroup::find($item->resource);

                if ($nodeGroup->isMarketable())
                {
                    foreach ($nodeGroup->nodes as $node)
                    {
                        $data = [
                            'from' => $node->id,
                            'services' => self::resolveNode($node, $item->order)
                        ];

                        $connectionResources['resource']['reference'][] = $data;
                    }
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
                    'connector' => $skeletonResource
                ];

                break;

            default:
                throw new FatalException("This resource resolver does NOT know how to resolve for $item->type");
        }

        return $connectionResources;
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

    private static function resolveService(Service $service, Order $againstOrder)
    {
        // Why a dedicated function? Because not all services can be resolved in the same way.
        $serviceType = $service->type;

        $ret = [
            'type' => $serviceType,
            'connector' => [
                'accessConfig' => null,
                'accessCredentials' => 'SPECTERO_USERNAME_PASSWORD',
                'accessReference' => null
            ]
        ];

        switch ($serviceType)
        {
            case ServiceType::HTTPProxy:
                $ret['connector'] = $service->connection_resource;
                break;

            case ServiceType::OpenVPN:
                $ret['connector']['accessConfig'] = OpenVPNConfigResolver::resolveOrderConfig($service->node, $againstOrder);
                break;

        }

        return $ret;
    }
}