<?php

define('CACHE_DIR', './cache');
define('CACHE_LIFETIME', 60);
define('COINMARKETCAP_TICKER_API', 'https://api.coinmarketcap.com/v1/ticker/?limit=0&convert=JPY');

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

function fetch_coinmarketcap_ticker_api() {
    return get_from_cache_or_remote('coinmarketcap.json', COINMARKETCAP_TICKER_API);
}

$data = fetch_coinmarketcap_ticker_api();

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')) {
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
    <title>幻想計算機</title>
    <meta name="description" content="暗号通貨（仮想通貨）の幻想総資産を日本円でわかりやすく管理">
    <link rel="stylesheet" href="https://unpkg.com/blaze@3.6.3/dist/blaze.min.css">
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
    <script src="https://cdn.jsdelivr.net/npm/riot@3.8/riot+compiler.min.js"></script>
    <script src="js/fetch.js"></script>
    <script src="js/utils.js"></script>
</head>
<body>

    <app></app>

    <script src="tag/modal.html" type="riot/tag"></script>
    <script src="tag/app.html" type="riot/tag"></script>

    <script>
        (function () {
            var dispatcher = riot.observable();
            var data = <?= $data ?>;

            riot.mount('app', {
                initial_data: data,
                dispatcher: dispatcher
            });
        })();
    </script>

</body>
</html>
