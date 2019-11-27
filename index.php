<?php
// Relative path to the cache directory
define('CACHE_DIR', './cache');
// Seconds to cache the ticker API results
define('TICKER_CACHE_LIFETIME', 30);
// Ticker API endpoint
define('TICKER_API', 'https://api.coingecko.com/api/v3/coins/markets?per_page=250&price_change_percentage=24h%2C7d');


function get_from_cache_or_remote($cache, $remote, $cache_lifetime = TICKER_CACHE_LIFETIME) {
    $cache_path = CACHE_DIR . '/' . $cache;

    if (!file_exists(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0777, true);
    }

    if (!file_exists($cache_path) || time() - filemtime($cache_path) > $cache_lifetime) {
        if (!file_exists($cache_path)) {
            $fp = fopen($cache_path, 'w');
        } else {
            $fp = fopen($cache_path, 'r+');
        }

        if (flock($fp, LOCK_EX | LOCK_NB)) {
            $options['ssl']['verify_peer'] = false;
            $options['ssl']['verify_peer_name'] = false;
            $contents = file_get_contents($remote, false, stream_context_create($options));

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

function fetch_ticker_api($convert) {
    // 掲載通貨が多すぎるので、最初の1,000件に限定する
    $data = array();
    for ($i = 1; $i <= 4; $i++) {
        $json_data = get_from_cache_or_remote("tickers_{$convert}_{$i}.json", TICKER_API . "&vs_currency={$convert}&page={$i}", TICKER_CACHE_LIFETIME);
        $data = array_merge($data, json_decode($json_data, true));
    }

    $base_btc_price = 1;
    foreach ($data as $key => $val) {
        if ($val['id'] == 'bitcoin') {
            $base_btc_price = $val['current_price'];
            break;
        }
    }

    $json_data = convert_data($data, $convert, $base_btc_price);

    return $json_data;
}

function convert_data($data, $convert, $base_btc_price) {
    if (!is_array($data)) {
        $data = json_decode($data, true);
    }

    $converted = array();
    foreach ($data as $key => $val) {
        $converted[$key] = array(
            'id' => $val['id'],
            'symbol' => strtoupper($val['symbol']),
            'name' => $val['name'],
            $convert => $val['current_price'],
            'BTC' => $val['current_price'] / $base_btc_price,
            '24h' => $val['price_change_percentage_24h_in_currency'],
            '7d' => $val['price_change_percentage_7d_in_currency'],
        );
    }

    return json_encode($converted);
}

$convert = isset($_REQUEST['convert']) ? $_REQUEST['convert'] : 'JPY';
$data = fetch_ticker_api($convert);

if (isset($_REQUEST['format']) && ($_REQUEST['format'] == 'json')) {
    echo $data;
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" id="viewport" content="width=device-width">
    <title>赤字計算機 v3</title>
    <meta name="description" content="暗号通貨（仮想通貨）の赤字を日本円でわかりやすく管理">
    <style>
        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
        }
    </style>
</head>
<body>

    <app></app>

    <script src="js/fetch.js"></script>
    <script src="js/utils.js"></script>
    <script src="tag/app.html" type="riot/tag"></script>
    <script src="js/riot-compiler.min.js"></script>

    <script>
        var data = <?= $data ?>;
        var convert = <?= json_encode($convert) ?>;

        riot.mount('app', {
            initial_data: data,
            convert: convert
        });
    </script>

</body>
</html>
