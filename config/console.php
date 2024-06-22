<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'class' => 'yii\redis\Cache',
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => $_ENV['REDIS_HOST'],
            'port' => $_ENV['REDIS_PORT'],
            'database' => $_ENV['REDIS_DATABASE'],
        ],
        'amqp' => [
            'class' => 'app\components\AmqpComponent',
            'host' =>  $_ENV['RABBITMQ_HOST'],
            'port' =>  $_ENV['RABBITMQ_PORT'],
            'user' =>  $_ENV['RABBITMQ_USER'],
            'password' =>  $_ENV['RABBITMQ_PASSWORD'],
            'queue' =>  $_ENV['RABBITMQ_QUEUE'],
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
    ],
    'params' => $params,
];

return $config;
