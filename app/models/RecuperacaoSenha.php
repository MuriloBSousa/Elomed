<?php

/**
 * Password recovery tokens (tbl_recuperacao_senha).
 * A single-use, expiring token per user.
 */
class RecuperacaoSenha extends Model {
    protected $table = 'tbl_recuperacao_senha';

    /**
     * Creates a new recovery token for the user.
     * Revokes any previous unused token for the same user.
     */
    public function criar(int $usuarioId): ?string {
        $limpar = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE fk_usuario = :id"
        );
        $limpar->execute(['id' => $usuarioId]);

        $token = bin2hex(random_bytes(32));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (fk_usuario, token, expira_em)
             VALUES (:id, :token, :expira)"
        );
        $ok = $stmt->execute([
            'id'     => $usuarioId,
            'token'  => hash('sha256', $token),
            'expira' => date('Y-m-d H:i:s', time() + Constants::RECUPERACAO_EXPIRACAO_MINUTOS * 60)
        ]);

        return $ok ? $token : null;
    }

    /**
     * Fetches a valid (unused, not expired) token with user data.
     * The incoming token is hashed before comparison.
     */
    public function buscarValido(string $token) {
        $tokenHash = hash('sha256', $token);
        $stmt = $this->db->prepare(
            "SELECT r.id AS rec_id, r.fk_usuario, r.token, r.expira_em,
                    u.email, u.nome
               FROM {$this->table} r
               JOIN tbl_usuario u ON u.id = r.fk_usuario
              WHERE r.token = :token
                AND r.usado = 0
                AND r.expira_em > NOW()
                AND u.status = 'ativo'
              LIMIT 1"
        );
        $stmt->execute(['token' => $tokenHash]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Marks a token as used (single use).
     * The incoming token is hashed before matching.
     */
    public function marcarUsado(string $token): bool {
        $tokenHash = hash('sha256', $token);
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET usado = 1 WHERE token = :token"
        );
        return $stmt->execute(['token' => $tokenHash]);
    }
}