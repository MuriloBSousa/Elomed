<?php
abstract class Model {
    protected PDO $db;
    protected $table; 
    protected $primaryKey = 'id';

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Escapes LIKE wildcards from user input so '%'/'_' typed by the user are
     * matched literally instead of widening the search.
     */
    public static function likeEscape(string $term): string {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }

    public function all() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'ativo' ORDER BY {$this->primaryKey} DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        if ($row['status'] === 'ativo') {
                return $row;
            }
            return false;
        }

    public function insert($sql, array $data = []) {
        $stmt = $this->db->prepare($sql); 
        $stmt->execute($data); 
        return $stmt;
    }

    public function update($sql, array $data = []) {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete(string $sql, array $data = []) {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Searches users with dynamic filters.
     * $filtros may contain: id (exact), nome (starts with), email (contains), perfil (exact).
     * Only non-empty filters are applied to the WHERE clause.
     */
    public function buscarUsuarios(array $filtros = []) {
        $condicoes = [];
        $params = [];

        if (!empty($filtros['id'])) {
            $condicoes[] = "u.id = :id";
            $params['id'] = (int) $filtros['id'];
        }

        if (!empty($filtros['nome'])) {
            $condicoes[] = "u.nome LIKE :nome";
            $params['nome'] = self::likeEscape($filtros['nome']) . '%';
        }

        if (!empty($filtros['email'])) {
            $condicoes[] = "u.email LIKE :email";
            $params['email'] = '%' . self::likeEscape($filtros['email']) . '%';
        }

        if (!empty($filtros['perfil'])) {
            $num = count($params);
            $condicoes[] = "EXISTS (
                SELECT 1 FROM tbl_user_role ur
                JOIN tbl_roles r ON r.id = ur.fk_role
                WHERE ur.fk_usuario = u.id AND r.slug = :perfil_{$num})";
            $params["perfil_{$num}"] = $filtros['perfil'];
        }

        $where = empty($condicoes) ? '' : 'WHERE ' . implode(' AND ', $condicoes);

        $perfilSub = "(SELECT r.slug FROM tbl_user_role ur JOIN tbl_roles r ON r.id = ur.fk_role
                        WHERE ur.fk_usuario = u.id
                        ORDER BY FIELD(r.slug, 'medico', 'enfermeiro', 'tecnico_enfermagem', 'admin', 'paciente') ASC
                        LIMIT 1)";

        $stmt = $this->db->prepare(
            "SELECT u.id, u.nome, u.email, u.status, u.primeiro_acesso,
                    " . $perfilSub . " AS perfil
               FROM tbl_usuario u
              {$where}
              ORDER BY " . $perfilSub . " ASC, u.nome ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorNomeProfissional($nome) {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.nome, u.email,
                    (SELECT r.slug FROM tbl_user_role ur JOIN tbl_roles r ON r.id = ur.fk_role
                      WHERE ur.fk_usuario = u.id
                      ORDER BY FIELD(r.slug, 'medico', 'enfermeiro', 'tecnico_enfermagem', 'admin', 'paciente') ASC
                      LIMIT 1) AS perfil
               FROM tbl_usuario u
              WHERE u.nome LIKE :nome
                AND EXISTS (SELECT 1
                              FROM tbl_user_role ur
                              JOIN tbl_roles r ON r.id = ur.fk_role
                             WHERE ur.fk_usuario = u.id
                               AND r.slug IN ('medico','enfermeiro','tecnico_enfermagem'))"
        );
        $stmt->execute(['nome' => '%' . self::likeEscape($nome) . '%']);
        return $stmt->fetchAll();
    }

    public function buscarPorNomePaciente($nome) {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.nome, u.email,
                    (SELECT r.slug FROM tbl_user_role ur JOIN tbl_roles r ON r.id = ur.fk_role
                      WHERE ur.fk_usuario = u.id
                      ORDER BY FIELD(r.slug, 'medico', 'enfermeiro', 'tecnico_enfermagem', 'admin', 'paciente') ASC
                      LIMIT 1) AS perfil
               FROM tbl_usuario u
              WHERE u.nome LIKE :nome
                AND EXISTS (SELECT 1
                              FROM tbl_user_role ur
                              JOIN tbl_roles r ON r.id = ur.fk_role
                             WHERE ur.fk_usuario = u.id AND r.slug = 'paciente')"
        );
        $stmt->execute(['nome' => '%' . self::likeEscape($nome) . '%']);
        return $stmt->fetchAll();
    }

    public function listarProfissionaisAtendendo($sql) {
    $stmt = $this->db->prepare($sql);
    $stmt->execute(); // Fixed: do not pass the $sql string here
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function buscarIdPaciente($sql, $data){
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}