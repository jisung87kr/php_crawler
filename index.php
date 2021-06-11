<?php
function d($dump)
{
    echo '<pre>';
    var_dump($dump);
    echo '</pre>';
}

include $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DomCrawler\Crawler;

$result = [];
$client = new Client();

// 페이지 조회 익명함수(제너레이터) 생성
$requests = function ($total) use ($client) {
    $uri = 'http://ujsstudio.com/page';
    for ($i = 0; $i < $total; $i++) {
        yield function() use ($client, $uri, &$i) {
            $uri = $uri.'/'.$i+1;
            return $client->getAsync($uri);
        };
    }
};

$pool = new Pool($client, $requests(10), [
        'concurrency' => 5,
        'fulfilled' => function (Response $response, $index) use (&$result) {
            $res = $response->getBody();
            $html = (string)$res;

            $crawler = new Crawler($html);
            $nodeValues = $crawler->filter("#primary a")->each(function(Crawler $node, $i){
                return $node->attr('href');
            });

            $result[] = $nodeValues;
        },
        'rejected' => function (RequestException $reason, $index) {
            // this is delivered each failed request
        },
]);

$promise = $pool->promise();
$promise->wait();

d($result);