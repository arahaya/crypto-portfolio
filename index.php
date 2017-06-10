<?php

define('CACHE_DIR', './cache');
define('CACHE_LIFETIME', 60);
define('COINMARKETCAP_TICKER_API', 'https://api.coinmarketcap.com/v1/ticker/?convert=JPY');

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
    <title>赤字計算機</title>
    <meta name="description" content="暗号通貨（仮想通貨）の赤字を日本円でわかりやすく管理">
</head>
<body>

    <app></app>

    <script src="js/fetch.js"></script>
    <script src="js/utils.js"></script>
    <script src="tag/app.html" type="riot/tag"></script>
    <script src="js/riot-compiler.min.js"></script>

    <script>
        var data = <?= $data ?>;

        riot.mount('app', {
            initial_data: data
        });
    </script>

</body>
</html>
