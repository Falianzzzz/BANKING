<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TransazioniController
{
 public function index(Request $request, Response $response, $args){
    $mysqli_connection = new MySQLi('my_mariadb', 'root', 'ciccio', 'banking');
    $id = (int) $args['idA'];
    $tmp = $mysqli_connection->prepare("SELECT * FROM transactions WHERE account_id = (?)");
    $tmp->bind_param("i", $id);
    $tmp->execute();
    $result = $tmp->get_result();
    $results = $result->fetch_assoc();

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function show (Request $request, Response $response, $args){
    $mysqli_connection = new MySQLi('my_mariadb', 'root', 'ciccio', 'banking');
    $idA = (int) $args['idA'];
    $idT = (int) $args['idT'];
    $tmp = $mysqli_connection->prepare("SELECT * FROM transactions t WHERE (SELECT t.id FROM transactions t JOIN accounts a ON a.id = t.account_id WHERE a.id = (?)) = (?) ");
    $tmp->bind_param("ii", $idA, $idT);
    $tmp->execute();
    $result = $tmp->get_result();
    $results = $result->fetch_assoc();

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function register (Request $request, Response $response, $args){
    $mysqli_connection = new MySQLi('my_mariadb', 'root', 'ciccio', 'banking');
    $idA = (int) $args['idA'];
    $richiesta = $request->getParsedBody();
    $type = (string) $richiesta['type'];
    $amount = (int) $richiesta['amount'];
    $description = (string) $richiesta['description'];
    $date =  $richiesta['date'];
    $tmp = $mysqli_connection->prepare("INSERT INTO transactions('account_id,'type','amount','description','created_at') VALUES (?,?,?,?)");
    $tmp->bind_param("isisd", $idA, $type,$amount,$description,$date);
    $tmp->execute();
    $result = $tmp->get_result();
    $results = $result->fetch_assoc();

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function withdrawls (Request $request, Response $response, $args){
    $mysqli_connection = new MySQLi('my_mariadb', 'root', 'ciccio', 'banking');
    $idA = (int) $args['idA'];
    $richiesta = $request->getParsedBody();
    
    $amount = (int) $richiesta['amount'];

    $tmp = $mysqli_connection->prepare("SELECT balance FROM accounts WHERE id = ?");
    $tmp->bind_param("i", $idA);
    $tmp->execute();
    $result = $tmp->get_result();
    $results = $result->fetch_assoc();

    if($amount <= $results['balance']){

      $description = (string) $richiesta['description'];
      $date =  $richiesta['date'];
      $type = (string) $richiesta['type'];
      $tmp = $mysqli_connection->prepare("INSERT INTO transactions('account_id,'type','amount','description','created_at') VALUES (?,?,?,?)");
      $tmp->bind_param("isisd", $idA, $type,$amount,$description,$date);
    $tmp->execute();
    $result = $tmp->get_result();
    $results = $result->fetch_assoc();
    }
     else {
      $results = [
        "messaggio" =>  "saldo insufficente" 
      ];
     }
    
    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }

  public function changeDescription (Request $request, Response $response, $args){
    $mysqli_connection = new MySQLi('my_mariadb', 'root', 'ciccio', 'banking');
    $idA = (int) $args['idA'];
    $idT = (int) $args['idT'];
    $richiesta = $request->getParsedBody();
    $description = (string) $richiesta['description'];
    $tmp = $mysqli_connection->prepare("SELECT * FROM transactions t WHERE (SELECT t.id FROM transactions t JOIN accounts a ON a.id = t.account_id WHERE a.id = (?)) = (?) ");
    $tmp->bind_param("ii", $idA, $idT);
    $tmp->execute();
    $result = $tmp->get_result();

    if($result->num_rows > 0){

      $tmp = $mysqli_connection->prepare("UPDATE transactions t SET t.description = ? WHERE t.id = ?");
      $tmp->bind_param("i", $idT);
    $tmp->execute();
    $result = $tmp->get_result();

    $results = $result->fetch_assoc();

    }
    

    $response->getBody()->write(json_encode($results));
    return $response->withHeader("Content-type", "application/json")->withStatus(200);
  }
  
}

