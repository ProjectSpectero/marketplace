<?php

return [
    'resources' => [ 'user', 'node', 'node_group', 'order', 'invoice', 'transaction' ],
    'operators' => [ '>', '<', '>=', '<=', '=', '!=', 'LIKE', 'SORT' ],
    'maxExpiry' => 600,
    'modelFieldCacheMinutes' => 30,
];