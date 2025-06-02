<?php class Crud
{
    protected $pdo;
    protected $table;
    public function __construct($pdo, $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }
    // Validar campos únicos 
    public function existeUnico($unicos)
    {
        $where = [];
        foreach ($unicos as $campo => $valor) {
            $where[] = "$campo = :$campo";
        }
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' OR ', $where);
        $stmt = $this->pdo->prepare($sql);
        foreach ($unicos as $campo => $valor) {
            $stmt->bindValue(":$campo", $valor, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
    // Insertar registro 
    public function insertar($datos)
    {
        $campos = array_keys($datos);
        $sql = "INSERT INTO {$this->table} (" . implode(',', $campos) . ") VALUES (:" . implode(',:', $campos) . ")";
        $stmt = $this->pdo->prepare($sql);
        foreach ($datos as $campo => $valor) {
            $stmt->bindValue(":$campo", $valor, PDO::PARAM_STR);
        }
        return $stmt->execute();
    }
    // Obtener un registro por ID 
    public function obtenerPorId($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Actualizar registro por ID 
    public function actualizar($id, $datos)
    {
        $campos = [];
        foreach ($datos as $campo => $valor) {
            $campos[] = "$campo = :$campo";
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        foreach ($datos as $campo => $valor) {
            $stmt->bindValue(":$campo", $valor, PDO::PARAM_STR);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    // Eliminar registro por ID 
    public function eliminar($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    // Listar todos los registros 
    public function listar()
    {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function contar()
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchColumn();
    }
}
