<?php class Auth
{
    protected $pdo;
    protected $table;
    public function __construct($pdo, $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }
    public function login($email, $password)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
