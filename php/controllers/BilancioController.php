<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TransazioniController
{
  public function index(Request $request, Response $response, $args){
    $mysqli_connection = new MySQLi('my_mariadb', 'root', 'ciccio', 'banking');
    $id = (int) $args['idA'];
    $tmp = $mysqli_connection->prepare("SELECT owner_name, currency, balance FROM accounts WHERE id = (?)");
    $tmp->bind_param("i", $id);
    $result = $tmp->execute();
    $results = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }
  
}