<?php

return [
    'resources' => [ 'user', 'node', 'order', 'invoice' ],
    'operators' => [ '>', '<', '>=', '<=', '=', '!=', 'LIKE' ],
    'maxExpiry' => 600,
    'modelFieldCacheMinutes' => 30,
];