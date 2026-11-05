<?php
class Endereco extends Model {
    protected $table = 'tbl_endereco';

    /**
     * Fetches the (single) address of a user or false when absent.
     */
    public function doUsuario($usuarioId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE fk_usuario = :usuario LIMIT 1"
        );
        $stmt->execute(['usuario' => $usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    /**
     * Saves the user's address: inserts when there is none yet,
     * otherwise updates the existing record.
     * Accepts 'rua' (legacy) or 'logradouro'; stores in the logradouro column.
     */
    public function salvar($usuarioId, array $dados) {
        $existe = $this->doUsuario($usuarioId);

        $mapa = ['rua' => 'logradouro'];
        $cols = ['logradouro', 'numero', 'bairro', 'cidade', 'uf', 'cep'];
        if (array_key_exists('complemento', $dados)) {
            $cols[] = 'complemento';
        }

        $paramsBase = [];
        foreach ($mapa as $antigo => $novo) {
            if (isset($dados[$antigo]) && !isset($dados[$novo])) {
                $dados[$novo] = $dados[$antigo];
            }
        }

        if ($existe) {
            $sets = [];
            $params = ['id' => $existe['id']];
            foreach ($cols as $col) {
                $sets[] = "{$col} = :{$col}";
                $params[$col] = $dados[$col] ?? null;
            }
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id"
            );
            return (bool) $stmt->execute($params);
        }

        $params = ['usuario' => $usuarioId, 'principal' => 1];
        foreach ($cols as $col) {
            $params[$col] = $dados[$col] ?? null;
        }
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (fk_usuario, " . implode(', ', $cols) . ", principal)
             VALUES (:usuario, :" . implode(', :', $cols) . ", :principal)"
        );
        return (bool) $stmt->execute($params);
    }
}