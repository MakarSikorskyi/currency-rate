<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';

use yii\console\Application;
use yii\di\Container;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$config = require __DIR__ . '/config/console.php';

Yii::$container = new Container();
$app = new Application($config);

$amqp = Yii::$app->amqp;
$callback = function($msg) {
    $data = json_decode($msg->body, true);
    if (isset($data['date'], $data['currencyCode'], $data['baseCurrencyCode'])) {
        $date = $data['date'];
        $currencyCode = $data['currencyCode'];
        $baseCurrencyCode = $data['baseCurrencyCode'];
        Yii::$app->runAction('currency/fetch-rates', [$date, $currencyCode, $baseCurrencyCode]);
    }
};

$amqp->consume($callback);
