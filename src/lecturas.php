<?php
require __DIR__ . '/../vendor/autoload.php';

use Clue\React\Mq\Queue;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;

$loop   = React\EventLoop\Loop::get();
$client = new Browser($loop);

// load a huge array of URLs to fetch
$urls = [
    '10.11.23.4',
    '10.11.23.9',
    '10.11.23.69',
    '10.11.23.166',
    '10.11.22.70',
    '10.11.23.228',
    '10.11.23.212',
    '10.11.23.251',
    '10.11.24.67',
    '10.11.24.135',
    '10.11.22.189',
    '10.11.25.12',
    '10.11.23.202',
    '10.11.22.206',
    '10.11.24.24',
    '10.11.27.2',
    '10.11.25.29'];

$loop->addPeriodicTimer(10, function () use ($client, $urls) {

    // each job should use the browser to GET a certain URL
    // limit number of concurrent jobs here
    $q = new Queue(50, null, function ($url) use ($client) {
        $url = "http://scada:3L3ctrota5@$url/status.xml";
        return $client->get($url);
    });

    foreach ($urls as $url) {

        $q($url)->then(
            function (ResponseInterface $response) use ($url) {
                // Procesar respuesta
                $xml = new SimpleXMLElement($response->getBody());
                print $url . ' : ' . $xml->pot2 . " \n";
            },
            function (Exception $exception) use ($url) {
                print $url . ' : ' . $exception->getMessage() . " \n";
            }
        );
    }

});

$loop->run();
