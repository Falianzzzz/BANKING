<?php
use Slim\Factory\AppFactory;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/controllers/TransazioniController.php';

$app = AppFactory::create();

$app->get('/accounts/{idA}/transactions', "TransazioniController:index");
$app->get('/accounts/{idA}/transactions/{idT}', "TransazioniController:show");
$app->post('/accounts/{idA}/deposits', "AlunniController:index");
$app->post('/accounts/{idA}/withdrawals', "AlunniController:index");
$app->put('/accounts/{idA}/transactions/{idT}', "AlunniController:index");
$app->delete('/accounts/{idA}/transactions/{idT}', "AlunniController:index");

$app->get('/accounts/{idA}/balance', "AlunniController:index");

$app->get('/accounts/{idA}/balance/convert/fiat?to=USD', "AlunniController:index");
$app->get('/accounts/{idA}/balance/convert/crypto?to=BTC', "AlunniController:index");

$app->run();
?>
