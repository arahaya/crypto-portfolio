<?php

define('CACHE_DIR', './cache');
define('CACHE_LIFETIME', 60);
define('POLONIEX_CURRENCY_API', 'https://poloniex.com/public?command=returnTicker');
define('BITTREX_CURRENCY_API', 'https://bittrex.com/api/v1.1/public/getmarketsummaries');
define('BITFLYER_PRICE_API', 'https://bitflyer.jp/api/echo/price');
define('KRAKEN_ASSETPAIR_API', 'https://api.kraken.com/0/public/AssetPairs');
define('KRAKEN_CURRENCY_API', 'https://api.kraken.com/0/public/Ticker');
define('CRYPTOPIA_CURRENCY_API', 'https://www.cryptopia.co.nz/api/GetMarkets/BTC');

function get_from_cache_or_remote($cache, $remote) {
    $cache_path = CACHE_DIR . '/' . $cache;

    if (!file_exists(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0777, true);
    }

    if (!file_exists($cache_path) || time() - filemtime($cache_path) > CACHE_LIFETIME) {
        if (!file_exists($cache_path)) {
            $fp = fopen($cache_path, 'w');
        } else {
            $fp = fopen($cache_path, 'r+');
        }

        if (flock($fp, LOCK_EX | LOCK_NB)) {
            $contents = @file_get_contents($remote);

            if ($contents) {
                ftruncate($fp, 0);
                fwrite($fp, $contents);
                fflush($fp);
            }

            flock($fp, LOCK_UN);
        }

        fclose($fp);
    }

    return file_get_contents($cache_path);
}

function fetch_poloniex_currency_market() {
    return get_from_cache_or_remote('poloniex.json', POLONIEX_CURRENCY_API);
}

function fetch_bittrex_currency_market() {
    return get_from_cache_or_remote('bittrex.json', BITTREX_CURRENCY_API);
}

function fetch_kraken_currency_market() {
    $assets =  json_decode(get_from_cache_or_remote('kraken-assets.json', KRAKEN_ASSETPAIR_API), true);

    $pairs = [];
    foreach ($assets['result'] as $name => $asset) {
        if ($asset['quote'] === 'XXBT') {
            $pairs[] = $name;
        }
    }

    return get_from_cache_or_remote('kraken.json', KRAKEN_CURRENCY_API . "?pair=" . implode(',', $pairs));
}

function fetch_cryptopia_currency_market() {
    return get_from_cache_or_remote('cryptopia.json', CRYPTOPIA_CURRENCY_API);
}

function fetch_btc_rate() {
    return get_from_cache_or_remote('btc_rate.json', BITFLYER_PRICE_API);
}

$poloniex = fetch_poloniex_currency_market();
$bittrex = fetch_bittrex_currency_market();
$kraken = fetch_kraken_currency_market();
$cryptopia = fetch_cryptopia_currency_market();
$btc_rate = fetch_btc_rate();

?>
<!DOCTYPE>
<html>
<head>

</head>
<body>

    <app></app>

    <script src="utils.js"></script>
    <script src="app.html" type="riot/tag"></script>
    <script src="riot-compiler.min.js"></script>

    <script>
        var poloniex = <?= $poloniex ?>;
        var bittrex = <?= $bittrex ?>;
        var kraken = <?= $kraken ?>;
        var cryptopia = <?= $cryptopia ?>;
        var btc_rate = <?= $btc_rate ?>;

        riot.mount('app', {
            poloniex: poloniex,
            bittrex: bittrex,
            kraken: kraken,
            cryptopia: cryptopia,
            btc_rate: btc_rate
        });
    </script>

</body>
</html>
