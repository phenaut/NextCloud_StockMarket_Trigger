<?php
return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'settings#get', 'url' => '/api/settings', 'verb' => 'GET'],
        ['name' => 'settings#save', 'url' => '/api/settings', 'verb' => 'PUT'],
        ['name' => 'watchlist#index', 'url' => '/api/watchlist', 'verb' => 'GET'],
        ['name' => 'watchlist#create', 'url' => '/api/watchlist', 'verb' => 'POST'],
        ['name' => 'watchlist#update', 'url' => '/api/watchlist/{id}', 'verb' => 'PUT'],
        ['name' => 'watchlist#destroy', 'url' => '/api/watchlist/{id}', 'verb' => 'DELETE'],
        ['name' => 'watchlist#quotes', 'url' => '/api/quotes', 'verb' => 'GET'],
    ],
];