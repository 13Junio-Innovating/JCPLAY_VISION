<?php

// Suppress HTML errors to ensure JSON response
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Define a constante DIRETORIO_BACKEND apenas se ainda não estiver definida
if (!defined('DIRETORIO_BACKEND')) {
    define('DIRETORIO_BACKEND', '../../backend/app/');
}

// Evita redefinir a classe DbConnection se já foi carregada anteriormente
if (!class_exists('DbConnection')) {
    class DbConnection
    {
        private $host;
        private $dbname;
        private $user;
        private $pass;

        public function __construct() {
            $this->loadEnv();
            
            $envHost = getenv('DB_HOST');
            $this->host = ($envHost !== false) ? $envHost : "127.0.0.1";

            $envName = getenv('DB_NAME');
            $this->dbname = ($envName !== false) ? $envName : "JC-Vision-Play";

            $envUser = getenv('DB_USER');
            $this->user = ($envUser !== false) ? $envUser : "projetos";

            $envPass = getenv('DB_PASS');
            $this->pass = ($envPass !== false) ? $envPass : "8ca06h8rC3QV";
        }

        private function loadEnv() {
            // Check if DB_HOST is already set (e.g. by Docker or server config)
            if (getenv('DB_HOST')) return;

            // Try to find .env file in project root (up 2 levels from public/api)
            $envPath = __DIR__ . '/../../.env';
            
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    // Skip comments
                    if (strpos(trim($line), '#') === 0) continue;
                    
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value);
                        
                        // Remove quotes if present
                        $value = trim($value, '"\'');

                        if (!getenv($name)) {
                            putenv(sprintf('%s=%s', $name, $value));
                            $_ENV[$name] = $value;
                            $_SERVER[$name] = $value;
                        }
                    }
                }
            }
        }

        public function getConnection()
        {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
                $connect = new PDO($dsn, $this->user, $this->pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                ]);
                return $connect;
            } catch (PDOException $e) {
                // Log the error for debugging (check php error log)
                error_log("Connection Error: " . $e->getMessage());
                return null;
            } catch (Exception $e) {
                error_log("General Error: " . $e->getMessage());
                return null;
            }
        }
    }
}
