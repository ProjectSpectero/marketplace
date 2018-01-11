<?php


namespace App\Listeners;


use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;

class BaseListener implements ShouldQueue
{
    public function failed (Exception $exception)
    {
        // TODO: implement default action when a job fails, perhaps to notify us in Slack?
    }
}