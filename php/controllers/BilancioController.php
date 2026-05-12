<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BilancioController {

    public function index(Request $request, Response $response, array $args) {
        $password = getenv('MARIADB_ROOT_PASSWORD');
        $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
        if ($mysqli->connect_error) {
            $response->getBody()->write(json_encode(['error' => 'DB connection failed']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        $idA = (int)($args['idA'] ?? 0);

        // check account exists
        $chk = $mysqli->prepare("SELECT 1 FROM accounts WHERE id = ? LIMIT 1");
        $chk->bind_param("i", $idA);
        $chk->execute();
        if ($chk->get_result()->num_rows === 0) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Account non trovato']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $stmt = $mysqli->prepare("SELECT 
            COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) as balance 
            FROM transactions WHERE account_id = ?");
        $stmt->bind_param("i", $idA);
        $stmt->execute();
        $balance = (float)$stmt->get_result()->fetch_assoc()['balance'];

        $mysqli->close();
        $response->getBody()->write(json_encode(['balance' => $balance]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function convertFiat(Request $request, Response $response, array $args) {
        $password = getenv('MARIADB_ROOT_PASSWORD');
        $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
        if ($mysqli->connect_error) {
            $response->getBody()->write(json_encode(['error' => 'DB connection failed']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        $idA = (int)($args['idA'] ?? 0);
        $to = strtoupper(trim($request->getQueryParams()['to'] ?? ''));

        if ($to === '') {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Parametro to mancante']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        if (!preg_match('/^[A-Z]{3}$/', $to)) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Valuta non valida']));
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

        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) as balance FROM transactions WHERE account_id = ?");
        $stmt->bind_param("i", $idA);
        $stmt->execute();
        $balance = (float)$stmt->get_result()->fetch_assoc()['balance'];
        $mysqli->close();

        $toEnc = rawurlencode($to);
        $url = "https://api.frankfurter.dev/v1/latest?base=EUR&symbols=$toEnc";
        $data = @file_get_contents($url);
        $data = $data ? json_decode($data, true) : null;

        if (!$data || !isset($data['rates'][$to])) {
            $response->getBody()->write(json_encode(['error' => 'Valuta non supportata o servizio non disponibile']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $rate = (float)$data['rates'][$to];
        $converted = round($balance * $rate, 2);

        $response->getBody()->write(json_encode([
            'original_balance' => $balance,
            'rate' => $rate,
            'converted_balance' => $converted
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function convertCrypto(Request $request, Response $response, array $args) {
        $password = getenv('MARIADB_ROOT_PASSWORD');
        $mysqli = new mysqli("home-banking-db", "root", $password, "banking");
        if ($mysqli->connect_error) {
            $response->getBody()->write(json_encode(['error' => 'DB connection failed']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        $idA = (int)($args['idA'] ?? 0);
        $to = strtoupper(trim($request->getQueryParams()['to'] ?? ''));

        if ($to === '') {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Parametro to mancante']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        if (!preg_match('/^[A-Z0-9]{2,10}$/', $to)) {
            $mysqli->close();
            $response->getBody()->write(json_encode(['error' => 'Crypto non valida']));
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

        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) as balance FROM transactions WHERE account_id = ?");
        $stmt->bind_param("i", $idA);
        $stmt->execute();
        $balance = (float)$stmt->get_result()->fetch_assoc()['balance'];
        $mysqli->close();

        $symbol = rawurlencode("{$to}EUR");
        $apiRaw = @file_get_contents("https://api.binance.com/api/v3/ticker/price?symbol=$symbol");
        $api = $apiRaw ? json_decode($apiRaw, true) : null;

        if (!$api || !isset($api['price'])) {
            $response->getBody()->write(json_encode(['error' => 'Crypto non trovata o servizio non disponibile']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $price = (float)$api['price'];
        if ($price <= 0) {
            $response->getBody()->write(json_encode(['error' => 'Prezzo non valido']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $converted = round($balance / $price, 8);

        $response->getBody()->write(json_encode([
            'fiat_balance' => $balance,
            'crypto_price' => $price,
            'crypto_amount' => $converted
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}