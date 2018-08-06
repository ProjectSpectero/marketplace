<?php


namespace App\Libraries;


use App\Constants\Errors;
use App\Constants\OrderResourceType;
use App\Errors\UserFriendlyException;
use App\Node;
use App\NodeGroup;

class ResourceUtils
{
    public static function resolve (string $type, int $id, bool $forMarket = true)
    {
        switch ($type)
        {
            case OrderResourceType::NODE:
                $resource = Node::findOrFail($id);
                break;

            case OrderResourceType::NODE_GROUP:
                $resource = NodeGroup::findOrFail($id);
                break;

            // TODO: Add proper handling for enterprise, and at that point get a proper verification routine going.
            case OrderResourceType::ENTERPRISE:
            default:
                throw new UserFriendlyException(Errors::RESOURCE_NOT_FOUND);

        }

        if ($forMarket)
        {
            if (! $resource->isMarketable())
                throw new UserFriendlyException(Errors::RESOURCE_STATUS_MISMATCH);
        }

        return $resource;
    }
}