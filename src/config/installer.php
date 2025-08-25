<?php

use App\Enums\StatusEnum;

return [

    'app_name'    => "Xsender",
    'software_id' => "BX32DOTW4Q797ZF3",
    'version'     => "3.33",

    'cacheFile'   => 'X2ZpbGVjYWNoZWluZw==',

    'core' => [
        'appVersion' => '3.33',
        'minPhpVersion' => '8.2'
    ],

    'requirements' => [

        'php' => [
            'Core',
            'bcmath',
            'openssl',
            'pdo_mysql',
            'mbstring',
            'tokenizer',
            'json',
            'curl',
            'gd',
            'zip',
            'mbstring',


        ],
        'apache' => [
            'mod_rewrite',
        ],

    ],
    'permissions' => [
        '.env'              => '666',
        'storage'           => '775',
        'bootstrap/cache/'  => '775',
    ],

];
