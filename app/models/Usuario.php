<?php
class Usuario extends Model {
    protected $table = 'tbl_usuario';

    /**
     * Expression that derives the user's primary role slug as "perfil".
     * The old tbl_usuario.perfil column no longer exists (RBAC migrated to
     * tbl_roles/tbl_user_role); this keeps legacy consumers working.
     */
    private const COLUNA_PERFIL =
        "(SELECT r.slug FROM tbl_user_role ur JOIN tbl_roles r ON r.id = ur.fk_role
           WHERE ur.fk_usuario = u.id
           ORDER BY FIELD(r.slug, 'medico', 'enfermeiro', 'tecnico_enfermagem', 'admin', 'paciente') ASC
           LIMIT 1)";

    /**
     * Fetches user by email (with the primary role derived as "perfil").
     */
    public function getByEmail($email) {
        $stmt = $this->db->prepare(
            "SELECT u.*, " . self::COLUNA_PERFIL . " AS perfil
               FROM {$this->table} u
              WHERE u.email = :email"
        );
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches a single active user with the primary role derived as "perfil".
     */
    public function find($id) {
        $stmt = $this->db->prepare(
            "SELECT u.*, " . self::COLUNA_PERFIL . " AS perfil
               FROM {$this->table} u
              WHERE u.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        if (($row['status'] ?? 'ativo') === 'ativo') {
            return $row;
        }
        return false;
    }

    /**
     * Verifies if the user currently holds a given role (slug).
     */
    public function possuiPerfil($usuarioId, $perfil) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
               FROM tbl_user_role ur
               JOIN tbl_roles r ON r.id = ur.fk_role
              WHERE ur.fk_usuario = :id AND r.slug = :slug"
        );
        $stmt->execute(['id' => (int) $usuarioId, 'slug' => $perfil]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Verifies user login by e-mail + password.
     * Optional $perfil keeps the check strict for legacy callers;
     * when omitted, any active account with valid credentials matches.
     */
    public function verificarLogin($email, $senha, $perfil = null) {
        $user = $this->getByEmail($email);

        // Business rule: only active users can access the system.
        if (!$user || ($user['status'] ?? 'ativo') !== 'ativo') {
            return false;
        }

        if (!password_verify($senha, $user['senha'])) {
            return false;
        }

        if ($perfil !== null && !$this->possuiPerfil($user['id'], $perfil)) {
            return false;
        }

        return $user;
    }
    
    /**
     * Creates a new user.
     * NOTE: the role is assigned by the caller through RBAC::atribuirRole
     * (the old tbl_usuario.perfil column no longer exists).
     */
    public function criar($dados) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (nome, email, senha) VALUES (:nome, :email, :senha)");
        try {
            return $stmt->execute([
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT)
            ]);
        } catch (PDOException $e) {
            // Business rule: duplicate emails are handled gracefully
            if (in_array(1062, (array) $e->errorInfo, true)) {
                throw new DatabaseException(
                    'Este e-mail já está em uso. Tente outro ou faça login.',
                    null,
                    ['email' => $dados['email']]
                );
            }
            error_log("Erro ao criar usuário: " . $e->getMessage());
            throw new DatabaseException('Erro ao cadastrar usuário. Tente novamente.', null, ['email' => $dados['email']]);
        }
    }
    
    /**
     * Updates user
     * NOTE: the role is managed separately via RBAC (tbl_user_role).
     */
    public function atualizar($id, $dados) {
        if (!empty($dados['senha'])) {
            $sql = "UPDATE {$this->table} SET nome = :nome, email = :email, senha = :senha WHERE id = :id";
            $params = [
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT),
                'id' => $id
            ];
        } else {
            $sql = "UPDATE {$this->table} SET nome = :nome, email = :email WHERE id = :id";
            $params = [
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'id' => $id
            ];
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Deactivates user
     */
    public function desativar($id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'inativo' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Changes the user password (recovery or profile page).
     * Clears the first-access flag and stamps the change time.
     */
    public function trocarSenha($id, $novaSenha) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
                SET senha = :senha,
                    primeiro_acesso = 0,
                    ultima_troca_senha = NOW()
              WHERE id = :id"
        );
        return $stmt->execute([
            'senha' => password_hash($novaSenha, PASSWORD_DEFAULT),
            'id'    => $id
        ]);
    }

    /**
     * Creates a user from the Google sign-in (no known password).
     * First access flag starts FALSE so they are not locked out.
     * NOTE: the RBAC role is assigned by the caller via RBAC::atribuirRole.
     */
    public function criarGoogle($nome, $email, $perfil = null) {
        $senhaAleatoria = bin2hex(random_bytes(16));
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (nome, email, senha, primeiro_acesso)
             VALUES (:nome, :email, :senha, 0)"
        );
        $ok = $stmt->execute([
            'nome'    => $nome,
            'email'   => $email,
            'senha'   => password_hash($senhaAleatoria, PASSWORD_DEFAULT)
        ]);
        return $ok ? (int) $this->db->lastInsertId() : 0;
    }

    /**
     * Clears the first-access flag (Google sign-in proves identity).
     */
    public function limparPrimeiroAcesso($id) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET primeiro_acesso = 0 WHERE id = :id"
        );
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Lists every user (including deactivated) for the admin management screen.
     */
    public function listarTodos() {
        return $this->db->query(
            "SELECT u.*, " . self::COLUNA_PERFIL . " AS perfil
               FROM {$this->table} u
              ORDER BY FIELD(" . self::COLUNA_PERFIL . ", 'medico', 'enfermeiro', 'tecnico_enfermagem', 'admin', 'paciente') ASC,
                       u.nome ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reactivates a previously deactivated account.
     */
    public function reativar($id) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET status = 'ativo' WHERE id = :id"
        );
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Counts active administrator accounts.
     */
    public function contarAdminsAtivos() {
        $stmt = $this->db->query(
            "SELECT COUNT(*)
               FROM {$this->table} u
               JOIN tbl_user_role ur ON ur.fk_usuario = u.id
               JOIN tbl_roles r ON r.id = ur.fk_role
              WHERE r.slug = 'admin' AND u.status = 'ativo'"
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Searches patient by name OR by CPF
     */
    public function buscarPorNomeOuCpfPaciente($termo) {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.nome, u.email, p.id AS id_paciente, p.cpf
               FROM tbl_usuario u
          LEFT JOIN tbl_paciente p ON p.fk_usuario = u.id
              WHERE EXISTS (SELECT 1
                              FROM tbl_user_role ur
                              JOIN tbl_roles r ON r.id = ur.fk_role
                             WHERE ur.fk_usuario = u.id AND r.slug = 'paciente')
                AND (u.nome LIKE :nome OR p.cpf LIKE :cpf)
              LIMIT 20"
        );
        $nome = '%' . Model::likeEscape($termo) . '%';
        // O CPF fica no banco SO com digito (CHAR(11)). Se a pessoa colar
        // "123.456.789-01", o LIKE do nome nao acha nada e o CPF exato
        // tambem nao - por isso procuramos os digitos sem pontuacao.
        // O piso de 3 digitos existe para nao gerar falso positivo: em
        // "Maria 2" o $digitos seria "2" e "%2%" cairia em qualquer CPF.
        $digitos = preg_replace('/\D/', '', $termo);
        $cpf = strlen($digitos) >= 3 ? '%' . $digitos . '%' : '%@@@%';
        $stmt->execute(['nome' => $nome, 'cpf' => $cpf]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
