<?php

/**
 * 
 * Laravel usa config/database.php para su ORM, pero esta clase queda disponible
 * si se requiere una conexion PDO directa con el mismo usuario no-root.
 */
class MyConnection
{
    private PDO $conexion;
    private bool $debug = false;

    public function __construct()
    {
        $sql_host = 'localhost';
        $sql_name = 'company_info';
        $sql_user = 'ComodinUser7';
        $sql_pass = 'mySecreto27';

        $dsn = "mysql:host=$sql_host;dbname=$sql_name;charset=utf8mb4";

        try {
            $this->conexion = new PDO($dsn, $sql_user, $sql_pass);
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Error de conexion: ' . $e->getMessage();
            exit;
        }
    }

    /**
     * Devuelve la instancia PDO para consultas preparadas personalizadas.
     */
    public function getConexion(): PDO
    {
        return $this->conexion;
    }

    /**
     * Ejecuta updates dinamicos usando parametros enlazados.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $condiciones
     */
    public function updateSeguro(string $tabla, array $data, array $condiciones): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = :$key";
        }

        $where = [];
        foreach ($condiciones as $key => $value) {
            $where[] = "$key = :cond_$key";
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $tabla,
            implode(', ', $set),
            implode(' AND ', $where)
        );

        try {
            $stmt = $this->conexion->prepare($sql);

            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            foreach ($condiciones as $key => $value) {
                $stmt->bindValue(":cond_$key", $value);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            if ($this->debug) {
                echo 'Error en UPDATE: ' . $e->getMessage();
            }

            return false;
        }
    }
}
