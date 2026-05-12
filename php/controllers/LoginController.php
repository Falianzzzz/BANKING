<?php

class LoginController {
    private $db;
    private $email;
    private $password;

    public function __construct($database) {
        $this->db = $database;
    }

    public function login($email, $password) {
        $this->email = htmlspecialchars(trim($email));
        $this->password = trim($password);

        if (!$this->validateInputs()) {
            return ['success' => false, 'message' => 'Email or password invalid'];
        }

        $query = "SELECT id, email, password FROM users WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $this->email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'User not found'];
        }

        $user = $result->fetch_assoc();

        if (!password_verify($this->password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];

        return ['success' => true, 'message' => 'Login successful'];
    }

    private function validateInputs() {
        return !empty($this->email) && !empty($this->password) && filter_var($this->email, FILTER_VALIDATE_EMAIL);
    }

    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'Logged out'];
    }
}
?>