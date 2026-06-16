<?php

class User {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Registreer een nieuwe gebruiker.
     * Geeft true terug bij succes, of een foutmelding als string.
     */
    public function register(string $username, string $email, string $password): true|string {
        // Controleer of username of email al bestaat
        $stmt = $this->conn->prepare("SELECT user_id FROM user WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            return "Gebruikersnaam of e-mailadres is al in gebruik.";
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $role_id = 1; // standaard rol

        $stmt = $this->conn->prepare(
            "INSERT INTO user (role_id, username, email, password) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$role_id, $username, $email, $passwordHash]);

        return true;
    }

    /**
     * Log een gebruiker in op basis van gebruikersnaam en wachtwoord.
     * Geeft de user-array terug bij succes, of een foutmelding als string.
     */
    public function login(string $username, string $password): array|string {
        $stmt = $this->conn->prepare("SELECT * FROM user WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return "Ongeldige gebruikersnaam of wachtwoord.";
        }

        return $user;
    }
}