<?php
class Diagnostico extends Model {
    protected $table = 'tbl_diagnostico';

    /**
     * Creates a diagnosis record.
     * $dados: fk_paciente, fk_medico, queixa_principal, diagnostico_final,
     * conduta_medica required; sintomas, historico_medico, sinais_vitais optional.
     */
    public function criar(array $dados) {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
             (fk_paciente, fk_medico, queixa_principal, sintomas, historico_medico,
              sinais_vitais, diagnostico_final, conduta_medica)
             VALUES (:paciente, :medico, :queixa, :sintomas, :historico,
                     :vitais, :diagnostico, :conduta)"
        );
        $ok = $stmt->execute([
            'paciente'     => $dados['fk_paciente'],
            'medico'       => $dados['fk_medico'],
            'queixa'       => $dados['queixa_principal'],
            'sintomas'     => $dados['sintomas'] ?? null,
            'historico'    => $dados['historico_medico'] ?? null,
            'vitais'       => $dados['sinais_vitais'] ?? null,
            'diagnostico'  => $dados['diagnostico_final'],
            'conduta'      => $dados['conduta_medica']
        ]);
        return $ok ? (int) $this->db->lastInsertId() : 0;
    }

    public function listarPorPaciente($pacienteId) {
        $stmt = $this->db->prepare(
            "SELECT d.*, m.nome AS medico_nome
               FROM {$this->table} d
               JOIN tbl_usuario m ON m.id = d.fk_medico
              WHERE d.fk_paciente = :paciente
              ORDER BY d.criado_em DESC"
        );
        $stmt->execute(['paciente' => $pacienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorMedico($medicoId) {
        $stmt = $this->db->prepare(
            "SELECT d.*, u.nome AS paciente_nome
               FROM {$this->table} d
               JOIN tbl_paciente p ON p.id = d.fk_paciente
               JOIN tbl_usuario u  ON u.id  = p.fk_usuario
              WHERE d.fk_medico = :medico
              ORDER BY d.criado_em DESC"
        );
        $stmt->execute(['medico' => $medicoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ultimoPorPaciente($pacienteId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE fk_paciente = :paciente
              ORDER BY criado_em DESC LIMIT 1"
        );
        $stmt->execute(['paciente' => $pacienteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    public function contarHoje() {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM {$this->table} WHERE DATE(criado_em) = CURDATE()"
        )->fetchColumn();
    }

    public function contarMes() {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM {$this->table}
              WHERE MONTH(criado_em) = MONTH(CURDATE()) AND YEAR(criado_em) = YEAR(CURDATE())"
        )->fetchColumn();
    }
}