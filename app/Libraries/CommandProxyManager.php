<?php


namespace App\Libraries;


use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Errors\FatalException;
use App\Node;

class CommandProxyManager
{
    // Returns the command proxy that is most optimal for use with this node
    public static function resolve (Node $node) : string
    {
        $workers = config('pools', []);
        if (count($workers) == 0)
            throw new FatalException(Messages::COMMAND_PROXY_POOL_EMPTY, ResponseType::SERVICE_UNAVAILABLE);

        // For now, we're choosing randomly. TODO: Make this process optimized so same region workers and nodes are matched up for performance
        $chosenWorker = array_random($workers);

        $uri = sprintf($chosenWorker['base'], mt_rand($chosenWorker['start'], $chosenWorker['end']));
        return $uri;
    }
}