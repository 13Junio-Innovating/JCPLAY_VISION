<?php
// login.php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: /dashboard.php");
    exit();
}

require_once 'api/db_connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Por favor, preencha todos os campos.";
    } else {
        $database = new DbConnection();
        $db = $database->getConnection();

        if ($db) {
            try {
                $query = "SELECT id, email, password, full_name FROM users WHERE email = :email LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    // Verifica senha (suporta tanto hash novo quanto MD5 legado se necessário, mas idealmente migrar)
                    // Aqui vamos assumir password_verify para segurança
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['full_name'];
                        $_SESSION['user_email'] = $user['email'];
                        // Role pode não existir na tabela atual baseada no SQL lido, mas se existir é bom ter
                        $_SESSION['user_role'] = 'user'; // Default role since column doesn't exist yet 
                        
                        header("Location: /dashboard.php");
                        exit();
                    } else {
                        $error = "Senha incorreta.";
                    }
                } else {
                    $error = "Usuário não encontrado.";
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
    <title>Login - JC Vision Play</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <div class="text-center mb-8">
            <!-- Se a imagem não existir, não quebra o layout -->
            <img src="/logo-costao.png" onerror="this.style.display='none'" alt="Logo" class="h-12 mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-800">Bem-vindo de volta</h1>
            <p class="text-gray-600">Entre na sua conta para continuar</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
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
            <div class="mb-6">
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
            <div class="flex items-center justify-between">
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full transition duration-300" type="submit">
                    Entrar
                </button>
            </div>
            <div class="mt-4 text-center">
                <p class="text-sm text-gray-600">Não tem uma conta? <a href="/register.php" class="font-bold text-blue-600 hover:text-blue-800">Registre-se</a></p>
            </div>
        </form>
    </div>
</body>
</html>
