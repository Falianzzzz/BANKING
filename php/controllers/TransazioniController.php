<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TransazioniController {

    public function index(Request $request, Response $response, array $args) {
        $password = getenv('MARIADB_ROOT_PASSWORD');
        $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
        if ($mysqli->connect_error) {
            $response->getBody()->write(json_encode(['error' => 'DB connection failed']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $idA = (int)($args['idA'] ?? 0);
        // verify account exists
        $chk = $mysqli->prepare("SELECT 1 FROM accounts WHERE id = ? LIMIT 1");
        $chk->bind_param("i", $idA);
        $chk->execute();
        $resChk = $chk->get_result();
        if ($resChk->num_rows === 0) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Account non trovato']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $mysqli->prepare("SELECT * FROM transactions WHERE account_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $idA);
        $stmt->execute();
        $result = $stmt->get_result();
        $transactions = $result->fetch_all(MYSQLI_ASSOC);

        $mysqli->close();
        $response->getBody()->write(json_encode($transactions));
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function show(Request $request, Response $response, array $args) {
      $password = getenv('MARIADB_ROOT_PASSWORD');
      $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
      if ($mysqli->connect_error) {
          $response->getBody()->write(json_encode(['error' => 'DB connection failed']));
          return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
      }

      $idA = (int)($args['idA'] ?? 0);
      $idT = (int)($args['idT'] ?? 0);

      // prepared statement already enforces ownership
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
        if ($mysqli->connect_error) {
            $response->getBody()->write(json_encode(['error' => 'DB connection failed']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $idA = (int)($args['idA'] ?? 0);
        $data = json_decode((string)$request->getBody(), true) ?? [];
        $amount = isset($data['amount']) ? (float)$data['amount'] : 0.0;
        $desc = isset($data['description']) ? trim($data['description']) : '';

        // basic validations
        if ($amount <= 0) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Importo deve essere > 0']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        if (strlen($desc) > 1000) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Descrizione troppo lunga']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // check account exists
        $chk = $mysqli->prepare("SELECT 1 FROM accounts WHERE id = ? LIMIT 1");
        $chk->bind_param("i", $idA);
        $chk->execute();
        if ($chk->get_result()->num_rows === 0) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Account non trovato']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $mysqli->prepare("INSERT INTO transactions (account_id, type, amount, description) VALUES (?, 'deposit', ?, ?)");
        $stmt->bind_param("ids", $idA, $amount, $desc);
        if (!$stmt->execute()) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Errore salvataggio']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $mysqli->close();
        $response->getBody()->write(json_encode(['message' => 'Deposito registrato']));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function withdrawls(Request $request, Response $response, array $args) {
        $password = getenv('MARIADB_ROOT_PASSWORD');
        $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
        if ($mysqli->connect_error) {
            $response->getBody()->write(json_encode(['error' => 'DB connection failed']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $idA = (int)($args['idA'] ?? 0);
        $data = json_decode((string)$request->getBody(), true) ?? [];
        $amount = isset($data['amount']) ? (float)$data['amount'] : 0.0;
        $desc = isset($data['description']) ? trim($data['description']) : '';

        if ($amount <= 0) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Importo deve essere > 0']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        if (strlen($desc) > 1000) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Descrizione troppo lunga']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // check account exists
        $chk = $mysqli->prepare("SELECT 1 FROM accounts WHERE id = ? LIMIT 1");
        $chk->bind_param("i", $idA);
        $chk->execute();
        if ($chk->get_result()->num_rows === 0) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Account non trovato']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // start transaction to reduce race conditions
        $mysqli->begin_transaction();

        $balStmt = $mysqli->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) as balance FROM transactions WHERE account_id = ?");
        $balStmt->bind_param("i", $idA);
        $balStmt->execute();
        $balRes = $balStmt->get_result()->fetch_assoc();
        $balance = (float)($balRes['balance'] ?? 0.0);

        if ($amount > $balance) {
            $mysqli->rollback();
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Saldo insufficiente']));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $mysqli->prepare("INSERT INTO transactions (account_id, type, amount, description) VALUES (?, 'withdrawal', ?, ?)");
        $stmt->bind_param("ids", $idA, $amount, $desc);
        if (!$stmt->execute()) {
            $mysqli->rollback();
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Errore salvataggio']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $mysqli->commit();
        $mysqli->close();
        $response->getBody()->write(json_encode(['message' => 'Prelievo registrato']));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function changeDescription(Request $request, Response $response, array $args) {
        $password = getenv('MARIADB_ROOT_PASSWORD');
        $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
        if ($mysqli->connect_error) {
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $idT = (int)($args['idT'] ?? 0);
        $idA = isset($args['idA']) ? (int)$args['idA'] : null;
        $data = json_decode((string)$request->getBody(), true) ?? [];
        $newDesc = isset($data['description']) ? trim($data['description']) : '';

        if (strlen($newDesc) > 1000) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Descrizione troppo lunga']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // confirm transaction exists and (optionally) belongs to account
        $chk = $mysqli->prepare("SELECT account_id FROM transactions WHERE id = ? LIMIT 1");
        $chk->bind_param("i", $idT);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        if (!$row) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Transazione non trovata']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        if ($idA !== null && $row['account_id'] != $idA) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Operazione non autorizzata']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $mysqli->prepare("UPDATE transactions SET description = ? WHERE id = ?");
        $stmt->bind_param("si", $newDesc, $idT);
        $stmt->execute();

        $mysqli->close();
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    public function delete(Request $request, Response $response, array $args) {
        $password = getenv('MARIADB_ROOT_PASSWORD');
        $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
        if ($mysqli->connect_error) {
            $response->getBody()->write(json_encode(['error' => 'DB connection failed']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $idT = (int)($args['idT'] ?? 0);
        $idA = isset($args['idA']) ? (int)$args['idA'] : null;

        // confirm transaction exists and (optionally) belongs to account
        $chk = $mysqli->prepare("SELECT account_id FROM transactions WHERE id = ? LIMIT 1");
        $chk->bind_param("i", $idT);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        if (!$row) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Transazione non trovata']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        if ($idA !== null && $row['account_id'] != $idA) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Operazione non autorizzata']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $mysqli->prepare("DELETE FROM transactions WHERE id = ?");
        $stmt->bind_param("i", $idT);
        $stmt->execute();

        $mysqli->close();
        $response->getBody()->write(json_encode(['message' => 'Eliminato']));
        return $response->withHeader('Content-Type', 'application/json');
    }
}