<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'api/db_connection.php';

echo "<h1>Diagnóstico de Conexão</h1>";

$db = new DbConnection();
$conn = $db->getConnection();

if ($conn) {
    echo "<p style='color: green;'><strong>Conexão bem sucedida!</strong></p>";
    echo "<p>Banco de dados selecionado: " . getenv('DB_NAME') . "</p>";
} else {
    echo "<p style='color: red;'><strong>Falha na conexão.</strong></p>";
    echo "<p>Verifique o arquivo .env e se o MySQL está rodando.</p>";
    echo "<p>Host: " . getenv('DB_HOST') . "</p>";
    echo "<p>User: " . getenv('DB_USER') . "</p>";
    echo "<p>Pass: " . (getenv('DB_PASS') ? '******' : '(vazio)') . "</p>";
    
    // Tentar conectar sem banco para ver se é só o banco que falta
    try {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS');
        if ($pass === false) $pass = ''; // Fallback se não definido, mas logicamente deve estar
        
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        echo "<p style='color: orange;'>Conexão com servidor MySQL (sem selecionar banco) funcionou. O problema provável é que o banco de dados 'JC-Vision-Play' não existe.</p>";
        echo "<button onclick=\"location.href='/setup_db.php'\">Clique aqui para criar o Banco de Dados</button>";
    } catch (PDOException $e) {
        echo "<p>Erro ao conectar no servidor MySQL raiz: " . $e->getMessage() . "</p>";
    }
}
?>