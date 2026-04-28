<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TransazioniController {

    public function index(Request $request, Response $response, array $args) {
        $password = getenv('MARIADB_ROOT_PASSWORD');
        $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
        $idA = (int)$args['idA'];
        
        $result = $mysqli->query("SELECT * FROM transactions WHERE account_id = $idA ORDER BY created_at DESC");
        $transactions = $result->fetch_all(MYSQLI_ASSOC);
        
        $mysqli->close();
          $response->getBody()->write(json_encode($transactions));
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function show(Request $request, Response $response, array $args) {
      $password = getenv('MARIADB_ROOT_PASSWORD');
      $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
      $idA = (int)$args['idA'];
      $idT = (int)$args['idT'];
      
      $stmt = $mysqli->prepare("SELECT * FROM transactions WHERE id = ? AND account_id = ?");
      $stmt->bind_param("ii", $idT, $idA);
      $stmt->execute();
      $transaction = $stmt->get_result()->fetch_assoc();
      
      $mysqli->close();

      if (!$transaction) {
          $response->getBody()->write(json_encode([
              'error' => 'Transazione non trovata'
          ]));
          return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
      }

      $response->getBody()->write(json_encode($transaction));
      return $response->withHeader('Content-Type', 'application/json');
    }

    public function register(Request $request, Response $response, array $args) {
        $password = getenv('MARIADB_ROOT_PASSWORD');
        $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
        $idA = (int)$args['idA'];
        $data = json_decode($request->getBody(), true);
        $amount = (float)$data['amount'];
        $desc = $data['description'];

        if ($amount <= 0) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Importo deve essere > 0']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $mysqli->prepare("INSERT INTO transactions (account_id, type, amount, description) VALUES (?, 'deposit', ?, ?)");
        $stmt->bind_param("ids", $idA, $amount, $desc);
        $stmt->execute();
        
        $mysqli->close();
        $response->getBody()->write(json_encode(['message' => 'Deposito registrato']));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function withdrawls(Request $request, Response $response, array $args) {
        $password = getenv('MARIADB_ROOT_PASSWORD');
        $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
        $idA = (int)$args['idA'];
        $data = json_decode($request->getBody(), true);
        $amount = (float)$data['amount'];

        $res = $mysqli->query("SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) as balance FROM transactions WHERE account_id = $idA");
        $balance = (float)$res->fetch_assoc()['balance'];

        if ($amount <= 0 || $amount > $balance) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Saldo insufficiente o importo errato']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $mysqli->prepare("INSERT INTO transactions (account_id, type, amount, description) VALUES (?, 'withdrawal', ?, ?)");
        $stmt->bind_param("ids", $idA, $amount, $data['description']);
        $stmt->execute();

        $mysqli->close();
        $response->getBody()->write(json_encode(['message' => 'Prelievo registrato']));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function changeDescription(Request $request, Response $response, array $args) {
        $password = getenv('MARIADB_ROOT_PASSWORD');
        $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
        $idT = (int)$args['idT'];
        $data = json_decode($request->getBody(), true);
        $newDesc = $data['description'] ?? '';

        $stmt = $mysqli->prepare("UPDATE transactions SET description = ? WHERE id = ?");
        $stmt->bind_param("si", $newDesc, $idT);
        $stmt->execute();

        $mysqli->close();
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args) {
        $password = getenv('MARIADB_ROOT_PASSWORD');
        $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
        $idT = (int)$args['idT'];

        $stmt = $mysqli->prepare("DELETE FROM transactions WHERE id = ?");
        $stmt->bind_param("i", $idT);
        $stmt->execute();

        $mysqli->close();
        $response->getBody()->write(json_encode(['message' => 'Eliminato']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}