<?php

return [

    'connection' => env('AZURE_STORAGE_CONNECTION', 'azure'),

    'connections' => [
        'azure' => [
            'connection_string' => env('AZURE_STORAGE_CONNECTION_STRING', 'UseDevelopmentStorage=true'),
            'retry' => [
                'tries' => env('AZURE_STORAGE_RETRY_TRIES', 5),
                'interval' => env('AZURE_STORAGE_RETRY_INTERVAL', 1000)
            ],
            'blob' => [
                'public_endpoint' => env('AZURE_STORAGE_BLOB_PUBLIC_ENDPOINT')
            ]
        ]
    ]

];
