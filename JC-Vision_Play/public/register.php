<?php
// register.php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard.php");
    exit();
}

require_once 'api/db_connection.php';
require_once 'api/utils.php'; // Para generate_uuid

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Por favor, preencha todos os campos.";
    } elseif ($password !== $confirm_password) {
        $error = "As senhas não coincidem.";
    } else {
        $database = new DbConnection();
        $db = $database->getConnection();

        if ($db) {
            try {
                // Verificar se email já existe
                $query = "SELECT id FROM users WHERE email = :email LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $error = "Este email já está cadastrado.";
                } else {
                    // Criar novo usuário
                    $uuid = generate_uuid();
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $insertQuery = "INSERT INTO users (id, full_name, email, password) VALUES (:id, :full_name, :email, :password)";
                    $insertStmt = $db->prepare($insertQuery);
                    $insertStmt->bindParam(':id', $uuid);
                    $insertStmt->bindParam(':full_name', $full_name);
                    $insertStmt->bindParam(':email', $email);
                    $insertStmt->bindParam(':password', $hashed_password);

                    if ($insertStmt->execute()) {
                        $success = "Conta criada com sucesso! Você pode fazer login agora.";
                    } else {
                        $error = "Erro ao criar conta.";
                    }
                }
            } catch (Exception $e) {
                $error = "Erro no sistema: " . $e->getMessage();
            }
        } else {
            $error = "Erro de conexão com o banco de dados.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - JC Vision Play</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Crie sua conta</h1>
            <p class="text-gray-600">Preencha os dados abaixo para começar</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                <p class="mt-2"><a href="/login.php" class="font-bold underline">Clique aqui para fazer login</a></p>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="full_name">
                    Nome Completo
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input class="shadow appearance-none border rounded w-full py-2 pl-10 pr-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500" id="full_name" type="text" name="full_name" placeholder="Seu Nome" required value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    Email
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-gray-400"></i>
                    </div>
                    <input class="shadow appearance-none border rounded w-full py-2 pl-10 pr-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500" id="email" type="email" name="email" placeholder="seu@email.com" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Senha
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input class="shadow appearance-none border rounded w-full py-2 pl-10 pr-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500" id="password" type="password" name="password" placeholder="******************" required>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                    Confirmar Senha
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input class="shadow appearance-none border rounded w-full py-2 pl-10 pr-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500" id="confirm_password" type="password" name="confirm_password" placeholder="******************" required>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full transition duration-300" type="submit">
                    Criar Conta
                </button>
            </div>
            <div class="mt-4 text-center">
                <p class="text-sm text-gray-600">Já tem uma conta? <a href="/login.php" class="font-bold text-blue-600 hover:text-blue-800">Faça Login</a></p>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
