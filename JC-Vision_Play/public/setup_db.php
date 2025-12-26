<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'api/db_connection.php';

echo "<h1>Configuração do Banco de Dados</h1>";

// Tentar conectar sem banco
$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS');
if ($pass === false) $pass = '8ca06h8rC3QV'; // Fallback só se realmente não estiver definido, mas o fix anterior resolve

// Correção para XAMPP root vazio
if ($user === 'root' && $pass === '8ca06h8rC3QV') $pass = ''; 

// Melhor pegar do env direto como fiz no db_connection
$db_conn = new DbConnection();
// Hack para pegar propriedades privadas ou recriar lógica
// Vamos recriar lógica simples aqui para o setup
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(sprintf('%s=%s', trim($name), trim(trim($value), '"\'')));
        }
    }
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS');
if ($pass === false) $pass = ''; // Default XAMPP empty
$dbname = getenv('DB_NAME') ?: 'JC-Vision-Play';

try {
    // 1. Conectar ao MySQL sem banco
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>Conectado ao MySQL com sucesso.</p>";

    // 2. Criar Banco
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>Banco de dados '$dbname' verificado/criado.</p>";

    // 3. Selecionar Banco
    $pdo->exec("USE `$dbname`");

    // 4. Ler SQL
    $sqlPath = __DIR__ . '/../database.sql';
    if (!file_exists($sqlPath)) {
        die("<p style='color:red'>Arquivo database.sql não encontrado em $sqlPath</p>");
    }
    
    $sql = file_get_contents($sqlPath);

    // 5. Executar queries (separando por ;)
    // PDO não executa multiplas queries num unico exec confiavelmente em todos drivers, mas mysql costuma aceitar se configurado.
    // Melhor fazer split simples.
    
    // Remover comentários simples
    $sql = preg_replace('/^--.*$/m', '', $sql);
    
    $queries = explode(';', $sql);
    $count = 0;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                $pdo->exec($query);
                $count++;
            } catch (PDOException $e) {
                // Ignorar erros de tabela já existente se for o caso, ou mostrar
                echo "<p style='color:orange'>Aviso na query: " . substr($query, 0, 50) . "... - " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<p style='color:green'><strong>Sucesso! $count comandos SQL executados.</strong></p>";
    echo "<p><a href='/login.php'>Ir para Login</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red'>Erro Crítico: " . $e->getMessage() . "</p>";
}
?>