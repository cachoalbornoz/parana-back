<?php

require 'config.inc.php';
require __DIR__ . '/../vendor/autoload.php';

use React\MySQL\QueryResult;

function conectar()
{
    $cnx = mysqli_connect(SERVER_IP, USERNAME, PASSWORD, MYSQL_DATABASE);
    if (!$cnx) {
        die('ERROR AL CONECTAR CON MYSQL');
    }
    mysqli_query($cnx, "SET NAMES 'utf8'");
    mysqli_query($cnx, "SET lc_time_names = 'es_AR'");
    mysqli_query($cnx, "SET time_zone = '-3:00'");
    return $cnx;
}

function desconectar($cnx)
{
    @mysqli_close($cnx);
}

function conectarAsync()
{
    //
    // Modelo de conexion
    // $connection = $factory->createLazyConnection('user:password@server/database');
    //
    $factory         = new React\MySQL\Factory();
    $MySQLConnection = USERNAME . '@' . SERVER_IP . '/' . MYSQL_DATABASE;
    return $factory->createLazyConnection($MySQLConnection);
}

function habilitarLecturas(){
    mysqli_query(conectar(), 'UPDATE tbl_setting SET f_detenerlecturas=0, f_placasaleer = 0, f_reintentos = 0, f_iteracciones = 0 WHERE f_idsetting = 1');
}

function armarXML($f_idplaca, $xml)
{
    $conn = conectar();

    // Convertir valores de btnx
    $btn0_value = ($xml->btn0 == 'up') ? 0 : 1;
    $btn1_value = ($xml->btn1 == 'up') ? 0 : 1;
    $btn2_value = ($xml->btn2 == 'up') ? 0 : 1;
    $btn3_value = ($xml->btn3 == 'up') ? 0 : 1;
    $btn4_value = ($xml->btn4 == 'up') ? 0 : 1;
    $btn5_value = ($xml->btn5 == 'up') ? 0 : 1;
    $btn6_value = ($xml->btn6 == 'up') ? 0 : 1;
    $btn7_value = ($xml->btn7 == 'up') ? 0 : 1;

    // Chequear si esta el registro
    $query_check = mysqli_query($conn, "SELECT f_idplaca FROM tbl_datalogger_electrotas WHERE f_idplaca = $f_idplaca");

    if(mysqli_num_rows($query_check) == 0) {
        $query = "INSERT INTO tbl_datalogger_electrotas(
			f_idplaca,
			f_fecha,
			f_ED1,
			f_ED2,
			f_ED3,
			f_ED4,
			f_ED5,
			f_ED6,
			f_ED7,
			f_ED8,
			f_EA1,
			f_EA2,
			f_EA3,
			f_SR1,
			f_SR2,
			f_SR3,
			f_SR4,
			f_SR5,
			f_SR6,
			f_SR7,
			f_SR8)
		VALUES (
			$f_idplaca,
			NOW(),
			$btn0_value,
			$btn1_value,
			$btn2_value,
			$btn3_value,
			$btn4_value,
			$btn5_value,
			$btn6_value,
			$btn7_value,
			$xml->pot0,
			$xml->pot1,
			$xml->pot2,
			$xml->led0,
			$xml->led1,
			$xml->led2,
			$xml->led3,
			$xml->led4,
			$xml->led5,
			$xml->led6,
			$xml->led7
		)";

    } else {
        $query = "UPDATE tbl_datalogger_electrotas SET
			f_fecha = NOW(),
			f_ED1 = $btn0_value,
			f_ED2 = $btn1_value,
			f_ED3 = $btn2_value,
			f_ED4 = $btn3_value,
			f_ED5 = $btn4_value,
			f_ED6 = $btn5_value,
			f_ED7 = $btn6_value,
			f_ED8 = $btn7_value,
			f_EA1 = $xml->pot0,
			f_EA2 = $xml->pot1,
			f_EA3 = $xml->pot2,
			f_SR1 = $xml->led0,
			f_SR2 = $xml->led1,
			f_SR3 = $xml->led2,
			f_SR4 = $xml->led3,
			f_SR5 = $xml->led4,
			f_SR6 = $xml->led5,
			f_SR7 = $xml->led6,
			f_SR8 = $xml->led7
			WHERE f_idplaca = $f_idplaca";
    }

    return $query;
}

function guardaXML($f_idplaca, $xml)
{
    $conn = conectar();

    $query = armarXML($f_idplaca, $xml);
    mysqli_query($conn, $query) or die($query);
    print "Xml PlacaId: $f_idplaca  \n";

    desconectar($conn);
}

function guardarXmlAsync($f_idplaca, $xml)
{
    $connection = conectarAsync();
    $query      = armarXML($f_idplaca, $xml);
    $connection->query($query)->then(
        function (QueryResult $command) use ($f_idplaca) {
            print "AsyncXml PlacaId: $f_idplaca  \n";
        },
        function (Exception $error) {
            print 'Error: ' . $error->getMessage() . PHP_EOL;
        }
    );
}

function getPlacas()
{
    $conn = conectar();

    // SQL QUERY
    $query = 'SELECT f_idplaca, f_ip FROM `tbl_placas` WHERE f_habilitada = 1;';

    // FETCHING DATA FROM DATABASE
    $result = [];
    $data   = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_array($data)) {
        $result[] = $row;
    }
    return  (count($result) > 0) ? $result : null;
}

function limpiarDesconexion()
{
    mysqli_query(conectar(), 'UPDATE tbl_setting SET f_placasaleer = 0, f_reintentos = 0, f_iteracciones = 0 WHERE f_idsetting = 1');
}

function limpiarReintentos()
{
    mysqli_query(conectar(), 'UPDATE tbl_setting SET f_reintentos = 0, f_iteracciones = 0 WHERE f_idsetting = 1');
}

function chequearIteraciones()
{
    $conn = conectar();
    $query = mysqli_query($conn, 'SELECT f_iteracciones FROM tbl_setting WHERE f_idsetting = 1');
    $row   = mysqli_fetch_array($query);
    return ($row['f_iteracciones'] > 1) ? true : false;
}

function informarPlacas($cantidad)
{
    mysqli_query(conectar(), "UPDATE tbl_setting SET f_placasaleer = $cantidad WHERE f_idsetting = 1");
}

function informarDesconexion()
{
    $conn = conectar();
    mysqli_query($conn, 'UPDATE tbl_setting SET f_reintentos = f_reintentos + 1 WHERE f_idsetting = 1');

    $query = mysqli_query($conn, 'SELECT * FROM tbl_setting WHERE f_idsetting = 1');
    $row   = mysqli_fetch_array($query);
    if ($row['f_reintentos'] == $row['f_placasaleer']) {
        mysqli_query($conn, 'UPDATE tbl_setting SET f_reintentos = 0, f_iteracciones = 1 WHERE f_idsetting = 1');
    }

}

function cortarLoop()
{
    $conn  = conectar();
    $query = 'SELECT f_detenerlecturas FROM `tbl_setting`;';
    $data  = mysqli_query($conn, $query);
    $row   = mysqli_fetch_array($data);
    return  ($row['f_detenerlecturas'] == 1) ? true : false;
}
