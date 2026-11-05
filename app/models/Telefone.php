<?php
class Telefone extends Model {
    protected $table = 'tbl_telefone';

    /**
     * Lists all phone numbers of a user.
     */
    public function doUsuario($usuarioId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE fk_usuario = :usuario ORDER BY id ASC"
        );
        $stmt->execute(['usuario' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Replaces the whole phone collection of a user.
     * Accepts either a single assoc array or a list of assoc arrays.
     * Each item: telefone (or a ddd+numero pair), [tipo]. The schema stores
     * a single 'telefone' column (no ddd / nome_contato / parentesco).
     */
    public function substituir($usuarioId, $entrada) {
        $telefones = $entrada;
        if (isset($telefones['ddd']) || isset($telefones['telefone']) || isset($telefones['numero'])) {
            $telefones = [$telefones];
        }

        $this->db->prepare("DELETE FROM {$this->table} WHERE fk_usuario = :usuario")
            ->execute(['usuario' => $usuarioId]);

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
             (fk_usuario, telefone, tipo)
             VALUES (:usuario, :telefone, :tipo)"
        );

        $ok = true;
        foreach ($telefones as $tel) {
            $numero = $tel['telefone'] ?? '';
            if ($numero === '') {
                $numero = trim(($tel['ddd'] ?? '') . ' ' . ($tel['numero'] ?? ''));
            }
            $numero = preg_replace('/\D/', '', $numero);
            if ($numero === '') {
                continue;
            }
            $tipo = $tel['tipo'] ?? 'principal';
            if (!in_array($tipo, ['principal', 'secundario', 'comercial'], true)) {
                $tipo = 'principal';
            }
            $ok = $stmt->execute([
                'usuario'  => $usuarioId,
                'telefone' => $numero,
                'tipo'     => $tipo
            ]) && $ok;
        }
        return $ok;
    }
}