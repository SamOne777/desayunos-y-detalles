<?php
abstract class UsuarioAbstracto {
    protected $id;
    protected $nombre;
    protected $email;
    protected $direccion;
    protected $telefono;
    protected $password;
    protected $rol;

    abstract public function registrar();
    abstract public function actualizar();
    abstract public function eliminar();
    abstract public function autenticar();
}

class Usuario extends UsuarioAbstracto {
    private $conexion;

    public function __construct($conexion, $datos = []) {
        $this->conexion = $conexion;
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }

    public function registrar() {
        $stmt = $this->conexion->prepare(
            "INSERT INTO usuarios (nombre, email, direccion, telefono, password, rol) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $passwordHash = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt->execute([
            $this->nombre,
            $this->email,
            $this->direccion,
            $this->telefono,
            $passwordHash,
            $this->rol ?? 'cliente'
        ]);
        return $this->conexion->lastInsertId();
    }

    public function actualizar() {
        $stmt = $this->conexion->prepare(
            "UPDATE usuarios SET 
             nombre = ?, email = ?, direccion = ?, telefono = ?, rol = ?
             WHERE id = ?"
        );
        return $stmt->execute([
            $this->nombre,
            $this->email,
            $this->direccion,
            $this->telefono,
            $this->rol,
            $this->id
        ]);
    }

    public function eliminar() {
        $stmt = $this->conexion->prepare(
            "DELETE FROM usuarios WHERE id = ?"
        );
        return $stmt->execute([$this->id]);
    }

    public function autenticar() {
        $stmt = $this->conexion->prepare(
            "SELECT * FROM usuarios WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$this->email]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($this->password, $usuario['password'])) {
            return $usuario;
        }
        return false;
    }
}
?>