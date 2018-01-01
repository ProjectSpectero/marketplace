<?php

namespace App\Http\Controllers\V1;

use Cache;

class DebugController
{
    private $key = 'cache.key';

    public function storeAction ()
    {
        Cache::put($this->key, "This is a cached element", 5);
        return "Everything went better than expected! :)";
    }

    public function retrieveAction ()
    {
        if (Cache::has($this->key))
            return Cache::get($this->key);

        return "Cache didn't have our key, boo!";
    }
}