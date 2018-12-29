<?php
// Relative path to the cache directory
define('CACHE_DIR', './cache');
// Seconds to cache the ticker API results
define('TICKER_CACHE_LIFETIME', 30);
// Seconds to cache the forex API results
define('FOREX_CACHE_LIFETIME', 600);
// Ticker API endpoint
define('TICKER_API', 'https://api.coinpaprika.com/v1/tickers');
// Forex API endpoint (used to get the exchange rate for USD/JPY)
define('FOREX_API', 'https://forex.1forge.com/1.0.3/quotes');
// API key for the forex API
// Free plan available at https://1forge.com/forex-data-api
define('FOREX_API_KEY', getenv('FOREX_API_KEY'));


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
    $data = get_from_cache_or_remote("tickers_{$convert}.json", TICKER_API . "?quotes=BTC,USD", TICKER_CACHE_LIFETIME);

    if ($convert != 'USD') {
        $convert_rate = fetch_forex_api($convert);
        $data = convert_data($data, $convert_rate);
    }

    return $data;
}

function fetch_forex_api($convert) {
    if ($convert == 'USD') {
        return 1;
    }

    $json = get_from_cache_or_remote("forex_{$convert}.json", FOREX_API . "?pairs=USD{$convert}&api_key=" . FOREX_API_KEY, FOREX_CACHE_LIFETIME);
    $data = json_decode($json, true);
    return $data[0]['price'];
}

function convert_data($data, $convert_rate) {
    if (!is_array($data)) {
        $data = json_decode($data, true);
    }

    foreach ($data as $key => $val) {
        $data[$key]['quotes']['JPY'] = $data[$key]['quotes']['USD'];
        $data[$key]['quotes']['JPY']['price'] = $data[$key]['quotes']['USD']['price'] * $convert_rate;
    }

    return json_encode($data);
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
    <title>赤字計算機 v2</title>
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
