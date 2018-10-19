--TEST--
swoole_http2_server: nghttp2 big data with ssl
--SKIPIF--
<?php
require __DIR__ . '/../include/skipif.inc';
if (strpos(@`nghttpd --version`, 'nghttp2') === false) {
    skip('no nghttpd');
}
?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';
$pm = new ProcessManager;
$pm->parentFunc = function ($pid) use ($pm) {
    go(function () use ($pm) {
        co::sleep(0.1);
        $cli = new Swoole\Coroutine\Http2\Client('127.0.0.1', $pm->getFreePort());
        $cli->connect();

        $filename = pathinfo(__FILE__, PATHINFO_BASENAME);
        $req = new swoole_http2_request;
        $req->path = "/{$filename}";
        $req->cookies = [
            'foo' => 'bar',
            'bar' => 'char'
        ];
        assert($cli->send($req));
        $response = $cli->recv();
        assert($response->data === co::readFile(__FILE__));
        echo "DONE\n";
    });
    $pm->kill();
};
$pm->childFunc = function () use ($pm) {
    $pm->wakeup();
    $root = __DIR__;
    `nghttpd -v -d {$root}/ -a 0.0.0.0 {$pm->getFreePort()} --no-tls`;
};
$pm->childFirst();
$pm->run();
?>
--EXPECT--
DONE