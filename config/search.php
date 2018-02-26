<?php

return [
    'resources' => [ 'user', 'node', 'order', 'invoice', 'transaction' ],
    'operators' => [ '>', '<', '>=', '<=', '=', '!=', 'LIKE' ],
    'maxExpiry' => 600,
    'modelFieldCacheMinutes' => 30,
];