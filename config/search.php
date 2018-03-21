<?php

return [
    'resources' => [ 'user', 'node', 'order', 'invoice', 'transaction' ],
    'operators' => [ '>', '<', '>=', '<=', '=', '!=', 'LIKE', 'SORT' ],
    'maxExpiry' => 600,
    'modelFieldCacheMinutes' => 30,
];