    <?php $host = 'localhost';
$dbname = 'laboratorio';
$user = 'root';
$pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION,);
    $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
    // Puedes registrar el error en un archivo y mostrar un mensaje genérico al usuario 
    error_log('Error de conexión: ' . $e->getMessage());
    die('No se pudo conectar a la base de datos. Intenta más tarde.');
}