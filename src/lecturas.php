<?php

set_time_limit(0);
ini_set('max_execution_time', 0);

require 'funciones.inc.php';
require __DIR__ . '/../../vendor/autoload.php';

use Clue\React\Mq\Queue;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Promise\Timer;

$loop   = React\EventLoop\Loop::get();
$client = new Browser($loop);

// Habilitar lecturas
habilitarLecturas();

// Obtener placas a leer
$placas = getPlacas();

// Informa la cantidad de placas a leer
informarPlacas(count($placas));

$loop->addPeriodicTimer(5, function (\React\EventLoop\TimerInterface $timer) use ($client, $placas, &$loop) {

    limpiarReintentos();

    if(cortarLoop()) {
        $loop->cancelTimer($timer);
    }

    $q = new Queue(50, null, function ($url) use ($client) {
        $url = "http://scada:3L3ctrota5@$url/status.xml";
        return $client->get($url);
    });


    foreach ($placas as $placa) {

        $id  = $placa['f_idplaca'];
        $url = $placa['f_ip'];

        Timer\timeout($q($url)->then(
            function (ResponseInterface $response) use ($id, $url) {
                // Procesar respuesta
                $xml = new SimpleXMLElement($response->getBody());
                guardarXmlAsync($id, $xml);
            },
            function (Exception $exception) use ($id, $url, $loop, $timer) {
                if($exception->getCode() == 0) {
                    print "Rechazo en placa $id, con la IP $url " . $exception->getMessage() . " \n";
                    informarDesconexion();
                }
            }
        ), 5.0);
    }

});

$loop->run();
