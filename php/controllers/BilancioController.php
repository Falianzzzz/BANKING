<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BilancioController {

    public function index(Request $request, Response $response, array $args) {
        $mysqli = new mysqli("my_mariadb", "root", "ciccio", "banking");
        $idA = (int)$args['idA'];

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
        $mysqli = new mysqli("my_mariadb", "root", "ciccio", "banking");
        $idA = (int)$args['idA'];
        $to = strtoupper($request->getQueryParams()['to'] ?? '');

        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) as balance FROM transactions WHERE account_id = ?");
        $stmt->bind_param("i", $idA);
        $stmt->execute();
        $balance = (float)$stmt->get_result()->fetch_assoc()['balance'];
        $mysqli->close();

        $url = "https://api.frankfurter.dev/v1/latest?base=EUR&symbols=$to";
        $data = json_decode(@file_get_contents($url), true);

        if (!$data || !isset($data['rates'][$to])) {
            $response->getBody()->write(json_encode(['error' => 'Valuta non supportata']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $rate = $data['rates'][$to];
        $converted = round($balance * $rate, 2);

        $response->getBody()->write(json_encode([
            'original_balance' => $balance,
            'rate' => $rate,
            'converted_balance' => $converted
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function convertCrypto(Request $request, Response $response, array $args) {
        $mysqli = new mysqli("my_mariadb", "root", "ciccio", "banking");
        $idA = (int)$args['idA'];
        $to = strtoupper($request->getQueryParams()['to'] ?? '');

        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE -amount END), 0) as balance FROM transactions WHERE account_id = ?");
        $stmt->bind_param("i", $idA);
        $stmt->execute();
        $balance = (float)$stmt->get_result()->fetch_assoc()['balance'];
        $mysqli->close();

        $symbol = "{$to}EUR";
        $api = json_decode(@file_get_contents("https://api.binance.com/api/v3/ticker/price?symbol=$symbol"), true);

        if (!$api || !isset($api['price'])) {
            $response->getBody()->write(json_encode(['error' => 'Crypto non trovata']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $price = (float)$api['price'];
        $converted = round($balance / $price, 8);

        $response->getBody()->write(json_encode([
            'fiat_balance' => $balance,
            'crypto_price' => $price,
            'crypto_amount' => $converted
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}