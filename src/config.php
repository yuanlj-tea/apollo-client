<?php
return [

    //apollo 服务端base url
    'base_url' => env('APOLLO_BASE_URL', ''),

    //apollo APP ID
    'app_id' => env('APOLLO_APP_ID', ''),

    //要拉取的apollo命名空间
    'namespace' => explode(',', env('APOLLO_NAMESPACE', '')),
];
