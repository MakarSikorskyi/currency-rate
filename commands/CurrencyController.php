<?php

namespace app\commands;

use Carbon\Carbon;
use phpseclib3\Crypt\EC;
use yii\console\Controller;
use yii\console\ExitCode;
use Yii;

class CurrencyController extends Controller
{
    public function actionFetchRates($date, $currencyCode, $baseCurrencyCode = 'RUR')
    {
        $rate = $this->getRates($date, $currencyCode, $baseCurrencyCode);

        $prevDate = $this->getPreviousTradingDay($date);
        $prevRate = $this->getRates($prevDate, $currencyCode, $baseCurrencyCode);

        $difference = $rate - $prevRate;
        echo "Difference with previous trading day: $difference".PHP_EOL;

        return ExitCode::OK;
    }

    private function getRates($date, $currencyCode, $baseCurrencyCode)
    {
        $rate = $this->getCachedExchangeRate($date, $currencyCode, $baseCurrencyCode);
        if (!$rate) {
            $rate = $this->getExchangeRate($date, $currencyCode, $baseCurrencyCode);
            if ($rate) {
                $this->cacheExchangeRate($date, $currencyCode, $baseCurrencyCode, $rate);
                echo "Fetched and cached rate for $currencyCode - $baseCurrencyCode on $date: $rate".PHP_EOL;
            } else {
                echo "Failed to fetch rate from source.".PHP_EOL;
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } else {
            echo "Rate for $currencyCode - $baseCurrencyCode on $date found in cache: $rate".PHP_EOL;
        }
        return $rate;
    }

    private function getExchangeRate($date, $currencyCode, $baseCurrencyCode)
    {
        $url = "https://www.cbr.ru/scripts/XML_daily.asp?date_req=" . $date;
        $xml = @simplexml_load_file($url);

        if ($xml === false) {
            return false;
        }

        $currencyRate = null;
        $baseCurrencyRate = null;

        foreach ($xml->Valute as $valute) {
            if ((string)$valute->CharCode === $currencyCode) {
                $currencyRate = (float)str_replace(',', '.', (string)$valute->Value);
            }
            if ((string)$valute->CharCode === $baseCurrencyCode) {
                $baseCurrencyRate = (float)str_replace(',', '.', (string)$valute->Value);
            }
        }

        if ($baseCurrencyCode === 'RUR') {
            return $currencyRate;
        }

        if ($currencyRate === null || $baseCurrencyRate === null) {
            return false;
        }

        $rate = $currencyRate / $baseCurrencyRate;
        return $rate;
    }

    private function cacheExchangeRate($date, $currencyCode, $baseCurrencyCode, $rate)
    {
        $cache = Yii::$app->redis;
        $cacheKey = "{$date}_{$currencyCode}_{$baseCurrencyCode}";
        $cache->set($cacheKey, $rate, 'EX', 86400);
    }

    private function getCachedExchangeRate($date, $currencyCode, $baseCurrencyCode)
    {
        $cache = Yii::$app->redis;
        $cacheKey = "{$date}_{$currencyCode}_{$baseCurrencyCode}";
        return $cache->get($cacheKey);
    }

    private function getPreviousTradingDay($date)
    {
        $carbonDate = Carbon::createFromFormat('d/m/Y', $date);

        do {
            $carbonDate->subDay();
        } while ($carbonDate->isWeekend());

        return $carbonDate->format('d/m/Y');
    }

    public function actionQueueRates($currencyCode = 'EUR', $baseCurrencyCode = 'USD')
    {
        $amqp = Yii::$app->amqp;

        for ($i = 0; $i < 180; $i++) {
            $date = Carbon::now()->subDays($i);
            if (!$date->isWeekend()) {
                $formattedDate = $date->format('d/m/Y');
                $message = json_encode(['date' => $formattedDate, 'currencyCode' => $currencyCode, 'baseCurrencyCode' => $baseCurrencyCode]);
                $amqp->sendMessage($message);
                echo "Sent queue message for $currencyCode - $baseCurrencyCode on date: $formattedDate\n";
            }
        }

        return ExitCode::OK;
    }
}
