<?php
class Conexion {
    private static $host = "localhost";
    private static $dbname = "desayunos_detalles";
    private static $username = "root";
    private static $password = "admin";
    private static $conexion = null;

    public static function conectar() {
        if (self::$conexion === null) {
            try {
                self::$conexion = new PDO(
                    "mysql:host=".self::$host.";dbname=".self::$dbname.";charset=utf8",
                    self::$username,
                    self::$password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (PDOException $e) {
                die("Error en la conexión: " . $e->getMessage());
            }
        }
        return self::$conexion;
    }
}
?>