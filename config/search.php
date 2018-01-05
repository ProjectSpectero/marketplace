<?php

return [
    'resources' => [ 'user', 'node' ],
    'operators' => [ '>', '<', '>=', '<=', '=', '!=', 'LIKE' ],
    'maxExpiry' => 600,
    'modelFieldCacheMinutes' => 30,
];