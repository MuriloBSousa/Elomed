<?php


class Triagem extends Model {
    protected $table = 'tbl_triagem';

    /**
     * Model::all() filters by status = 'ativo', which does not exist in the
     * triage enum (pendente | aprovada | recusada). Lists every triage.
     */
    public function all() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY criado_em DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Model::find() applies the same non-existent status filter. Fetches a
     * triage by its primary key.
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    /**
     * Returns the scheduling created from this triage, when it exists.
     */
    public function getAgendamento($idTriagem) {
        $stmt = $this->db->prepare(
            "SELECT id, status, fk_profissional, data_hora_inicio, data_hora_fim
               FROM tbl_agendamento
              WHERE fk_triagem = :triagem
              LIMIT 1"
        );
        $stmt->execute(['triagem' => (int) $idTriagem]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
