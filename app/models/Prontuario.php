<?php


class Prontuario extends Model {
    protected $table = 'tbl_prontuario'; 
    
    /**
     * Fetches the medical record linked to a scheduling (through its atendimento)
     */
    public function getByAgendamento($agendamentoId) {
        $stmt = $this->db->prepare(
            "SELECT pr.id
               FROM {$this->table} pr
               JOIN tbl_atendimento ta ON ta.id = pr.fk_atendimento
              WHERE ta.fk_agendamento = :id_agendamento
              LIMIT 1"
        );
        $stmt->execute(['id_agendamento' => $agendamentoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Resolves (or mirrors) the atendimento linked to a scheduling.
     */
    private function atendimentoPorAgendamento($agendamentoId) {
        $stmt = $this->db->prepare("SELECT id FROM tbl_atendimento WHERE fk_agendamento = :ag LIMIT 1");
        $stmt->execute(['ag' => $agendamentoId]);
        $atendimentoId = $stmt->fetchColumn();
        if ($atendimentoId) {
            return $atendimentoId;
        }
        $ag = $this->db->prepare(
            "SELECT fk_profissional, data_hora_inicio, status FROM tbl_agendamento WHERE id = :id"
        );
        $ag->execute(['id' => $agendamentoId]);
        $a = $ag->fetch(PDO::FETCH_ASSOC);
        if (!$a) {
            return null;
        }
        // tbl_atendimento.status is an ENUM without 'pendente', so the
        // scheduling status must be mapped to a valid atendimento status.
        $statusMap = [
            'pendente'   => 'agendado',
            'agendado'   => 'agendado',
            'em_andamento' => 'em_andamento',
            'concluido'  => 'concluido',
            'cancelado'  => 'cancelado',
            'ausente'    => 'ausente'
        ];
        $status = $statusMap[$a['status']] ?? 'agendado';
        $this->db->prepare(
            "INSERT INTO tbl_atendimento (fk_agendamento, fk_profissional, horario_inicio, status)
             VALUES (:ag, :prof, :inicio, :status)"
        )->execute([
            'ag'     => $agendamentoId,
            'prof'   => $a['fk_profissional'],
            'inicio' => $a['data_hora_inicio'],
            'status' => $status
        ]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Creates a new medical record linked to an atendimento.
     * Accepts either fk_atendimento or (legacy) fk_agendamento as input.
     */
    public function criar($dados) {
        $fkAtendimento = $dados['fk_atendimento'] ?? $this->atendimentoPorAgendamento($dados['fk_agendamento'] ?? null);
        if (!$fkAtendimento) {
            return false;
        }
        $stmt = $this->db->prepare("INSERT INTO {$this->table} 
                                      (fk_atendimento, queixa_principal, historico_medico, analise_ia) 
                                      VALUES (:fk_atendimento, :queixa, :historico, :analise_ia)");
        return $stmt->execute([
            'fk_atendimento' => $fkAtendimento,
            'queixa' => $dados['queixa'],
            'historico' => $dados['historico'],
            'analise_ia' => $dados['analise_ia']
        ]);
    }
    
    /**
     * Updates existing medical record
     */
    public function atualizar($id, $dados) {
        $stmt = $this->db->prepare("UPDATE {$this->table} 
                                      SET queixa_principal = :queixa, 
                                          historico_medico = :historico, 
                                          analise_ia = :analise_ia
                                      WHERE id = :id");
        return $stmt->execute([
            'queixa' => $dados['queixa'],
            'historico' => $dados['historico'],
            'analise_ia' => $dados['analise_ia'],
            'id' => $id
        ]);
    }
    
    /**
     * Updates final diagnosis and treatment plan of the atendimento
     * linked to the given scheduling
     */
    public function atualizarDiagnosticoFinal($agendamentoId, $diagnostico, $conduta) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
                SET diagnostico_final = :diagnostico,
                    conduta_medica = :conduta
              WHERE fk_atendimento = (
                        SELECT id FROM tbl_atendimento
                         WHERE fk_agendamento = :fk_agendamento
                         LIMIT 1
                    )"
        );
        return $stmt->execute([
            'diagnostico' => $diagnostico,
            'conduta' => $conduta,
            'fk_agendamento' => $agendamentoId
        ]);
    }
}
