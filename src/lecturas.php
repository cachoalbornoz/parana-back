<?php

require_once 'funciones.inc.php';

require __DIR__ . '/../vendor/autoload.php';

use Clue\React\Mq\Queue;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Promise\Timer;

use React\MySQL\QueryResult;

$loop   = React\EventLoop\Loop::get();
$client = new Browser($loop);

// $factory = new React\MySQL\Factory();
// //$connection = $factory->createLazyConnection('user:password@server/database');
// $connection = $factory->createLazyConnection('root@localhost/parana-medio');

// $connection->query('SELECT f_ip FROM tbl_placas')->then(
//     function (QueryResult $command) {

//     },
//     function (Exception $error) {
//         print 'Error: ' . $error->getMessage() . PHP_EOL;
//     }
// );

// load a huge array of URLs to fetch


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
                print 'id: ' . $id . ' url: ' . $url . ' : ' . $xml->pot2 . " \n";
            },
            function (Exception $exception) use ($url) {
                print $url . ' : ' . $exception->getMessage() . " \n";
            }
        ), 5.0);

    }
});

$loop->run();
