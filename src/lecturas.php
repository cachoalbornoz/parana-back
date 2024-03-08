<?php

require_once 'funciones.inc.php';

require __DIR__ . '/../vendor/autoload.php';

use Clue\React\Mq\Queue;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Promise\Timer;

$loop   = React\EventLoop\Loop::get();
$client = new Browser($loop);

$placas = getPlacas();

$loop->addPeriodicTimer(5, function (\React\EventLoop\TimerInterface $timer) use ($client, $placas, &$loop) {

    if(cortarLoop() == 1) {
        $loop->cancelTimer($timer);
    }

    $q = new Queue(50, null, function ($url) use ($client) {
        $url = "http://scada:3L3ctrota5@$url/status.xml";
        return $client->get($url);
    });

    foreach ($placas as $placa) {

        $id  = $placa['f_idplaca'];
        $url = $placa['f_ip'];

        Timer\timeout(
        $q($url)->then(
            function (ResponseInterface $response) use ($id, $url) {
                // Procesar respuesta
                $xml = new SimpleXMLElement($response->getBody());
                guardarXmlAsync($id, $xml);
            },
            function (Exception $exception) use ($url) {
                print $url . ' : ' . $exception->getMessage() . " \n";
            }
        ), 5.0);

    }
});

$loop->run();
