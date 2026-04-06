<?php
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/vendor/autoload.php';

require __DIR__ . '/controllers/TransazioniController.php';
require __DIR__ . '/controllers/BilancioController.php';

$app = AppFactory::create();

echo "Funziona!";
$app->get('/accounts/{idA}/transactions', "TransazioniController:index");
$app->get('/accounts/{idA}/transactions/{idT}', "TransazioniController:show");
$app->post('/accounts/{idA}/deposits', "TransazioniController:register");
$app->post('/accounts/{idA}/withdrawals', "TransazioniController:withdrawls");
$app->put('/accounts/{idA}/transactions/{idT}', "TransazioniController:changeDescription");
$app->delete('/accounts/{idA}/transactions/{idT}', "TransazioniController:delete");

$app->get('/accounts/{idA}/balance', "BilancioController:index");

$app->get('/accounts/{idA}/balance/convert/fiat', "BilancioController:convertFiat");
$app->get('/accounts/{idA}/balance/convert/crypto', "BilancioController:convertCrypto");

$app->run();
