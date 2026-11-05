<?php


class Agendamento extends Model {
    protected $table = 'tbl_agendamento'; 

    /**
     * Id of the last row inserted through criar() on this instance.
     * PDO::lastInsertId() is reset by any other statement on the same
     * connection, so the value is captured at insert time.
     */
    private $ultimoIdInserido = 0;

    /**
     * Combines a date (Y-m-d) with a time (H:i[:s]) into a DATETIME value.
     */
    private function montarDatetime($data, $hora) {
        $hora = trim((string) $hora);
        if ($hora === '') {
            return trim((string) $data);
        }
        if (strlen($hora) === 5) {
            $hora .= ':00';
        }
        return trim((string) $data) . ' ' . $hora;
    }
    
    /**
     * Updates scheduling status
     */
    public function atualizarStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status WHERE id = :id");
        $ok = $stmt->execute(['status' => $status, 'id' => $id]);
        if ($ok) {
            $this->sincronizarVisita($id);
        }
        return $ok;
    }
    
    /**
     * Updates scheduling status with condition
     */
    public function atualizarStatusComCondicao($id, $status, $statusAtual) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status WHERE id = :id AND status = :status_atual");
        $ok = $stmt->execute(['status' => $status, 'id' => $id, 'status_atual' => $statusAtual]);
        if ($ok) {
            $this->sincronizarVisita($id);
        }
        return $ok;
    }

    /**
     * Bridge scheduling -> atendimento: keeps tbl_atendimento mirroring the
     * state of the scheduling (em_andamento/concluido/cancelado) so the admin
     * panel and patient timeline reflect reality.
     */
    public function sincronizarVisita($id) {
        $ag = $this->getById($id);
        if (!$ag) {
            return false;
        }
        $status = $ag['status'];
        if (!in_array($status, ['em_andamento', 'concluido', 'cancelado', 'ausente'], true)) {
            return true;
        }

        $inicio = $ag['data_hora_inicio']
            ?? trim(($ag['data_agendamento'] ?? '') . ' ' . ($ag['horario_inicio'] ?? ''));
        $stmt = $this->db->prepare("SELECT id FROM tbl_atendimento WHERE fk_agendamento = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $atendimentoId = $stmt->fetchColumn();

        if ($atendimentoId) {
            if ($status === 'cancelado') {
                $up = $this->db->prepare("UPDATE tbl_atendimento SET status = 'cancelado' WHERE id = :id");
                return $up->execute(['id' => $atendimentoId]);
            }
            $up = $this->db->prepare(
                "UPDATE tbl_atendimento
                    SET horario_inicio = COALESCE(horario_inicio, :inicio),
                        horario_fim    = IF(:status = 'concluido', NOW(), NULL),
                        status         = :status
                  WHERE id = :id"
            );
            return $up->execute(['inicio' => $inicio ?: date('Y-m-d H:i:s'), 'status' => $status, 'id' => $atendimentoId]);
        }

        if ($status === 'cancelado') {
            return true;
        }
        $ins = $this->db->prepare(
            "INSERT INTO tbl_atendimento
                (fk_agendamento, fk_profissional, horario_inicio, horario_fim, status)
             VALUES
                (:ag, :prof, :inicio, IF(:status = 'concluido', NOW(), NULL), :status)"
        );
        return $ins->execute([
            'ag'     => $id,
            'prof'   => $ag['fk_profissional'],
            'inicio' => $inicio ?: date('Y-m-d H:i:s'),
            'status' => $status
        ]);
    }
    
    /**
     * Fetches patient linked to the scheduling
     */
    public function getPacienteId($agendamentoId) {
        $stmt = $this->db->prepare("SELECT fk_paciente FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $agendamentoId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['fk_paciente'] ?? null;
    }
    
    /**
     * Length of the visit in minutes, derived from the stored window.
     */
    private function duracaoMinutos($inicio, $fim) {
        $minutos = (int) round((strtotime($fim) - strtotime($inicio)) / 60);
        return $minutos > 0 ? $minutos : 30;
    }

    /**
     * Creates new scheduling.
     * Accepts the legacy pair date (data_agendamento/data) + time
     * (horario_inicio/horario_fim) and stores it as the DATETIME columns
     * data_hora_inicio / data_hora_fim.
     *
     * Optional keys: fk_triagem (visit originated from a triage), status
     * and duracao_minutos (derived from the window when not informed).
     */
    public function criar($dados) {
        $data    = $dados['data_agendamento'] ?? ($dados['data'] ?? '');
        $inicio  = $this->montarDatetime($data, $dados['horario_inicio'] ?? '');
        $fim     = $this->montarDatetime($data, $dados['horario_fim'] ?? '');

        $duracao = isset($dados['duracao_minutos'])
            ? (int) $dados['duracao_minutos']
            : $this->duracaoMinutos($inicio, $fim);

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (fk_paciente, fk_profissional, data_hora_inicio, data_hora_fim, status, duracao_minutos, fk_triagem)
              VALUES (:fk_paciente, :fk_profissional, :inicio, :fim, :status, :duracao, :fk_triagem)"
        );
        $ok = $stmt->execute([
            'fk_paciente' => $dados['fk_paciente'],
            'fk_profissional' => $dados['fk_profissional'],
            'inicio' => $inicio,
            'fim' => $fim,
            'status' => $dados['status'] ?? Constants::AGENDAMENTO_PENDENTE,
            'duracao' => $duracao,
            'fk_triagem' => !empty($dados['fk_triagem']) ? (int) $dados['fk_triagem'] : null
        ]);
        if ($ok) {
            $this->ultimoIdInserido = (int) $this->db->lastInsertId();
        }
        return $ok;
    }

    /**
     * Checks time conflict for the professional
     */
    public function verificarConflito($fkProfissional, $data, $inicio, $fim, $idExcluir = null) {
        $inicioDt = $this->montarDatetime($data, $inicio);
        $fimDt    = $this->montarDatetime($data, $fim);
        $sql = "SELECT id, TIME(data_hora_inicio) AS horario_inicio, TIME(data_hora_fim) AS horario_fim
                  FROM tbl_agendamento
                 WHERE fk_profissional = :prof
                   AND status IN ('pendente', 'agendado', 'em_andamento')
                   AND (:id_excluir IS NULL OR id <> :id_excluir)
                   AND data_hora_inicio < :fim_dt AND data_hora_fim > :inicio_dt";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'prof' => $fkProfissional,
            'id_excluir' => $idExcluir ? (int) $idExcluir : null,
            'inicio_dt' => $inicioDt,
            'fim_dt' => $fimDt
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Checks time conflict for the patient
     */
    public function verificarConflitoPaciente($fkPaciente, $data, $inicio, $fim, $idExcluir = null) {
        $inicioDt = $this->montarDatetime($data, $inicio);
        $fimDt    = $this->montarDatetime($data, $fim);
        $sql = "SELECT id, TIME(data_hora_inicio) AS horario_inicio, TIME(data_hora_fim) AS horario_fim
                  FROM tbl_agendamento
                 WHERE fk_paciente = :pac
                   AND status IN ('pendente', 'agendado', 'em_andamento')
                   AND (:id_excluir IS NULL OR id <> :id_excluir)
                   AND data_hora_inicio < :fim_dt AND data_hora_fim > :inicio_dt";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'pac' => $fkPaciente,
            'id_excluir' => $idExcluir ? (int) $idExcluir : null,
            'inicio_dt' => $inicioDt,
            'fim_dt' => $fimDt
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Cancels scheduling with justification
     */
    public function cancelarComJustificativa($id, $justificativa) {
        $stmt = $this->db->prepare(
            "UPDATE tbl_agendamento
                SET status = 'cancelado', justificativa_cancelamento = :just
              WHERE id = :id AND status IN ('pendente', 'agendado')"
        );
        $ok = $stmt->execute(['just' => $justificativa, 'id' => $id]);
        if ($ok) {
            $this->sincronizarVisita($id);
        }
        return $ok;
    }

    /**
     * Generates simulated reminder for confirmed scheduling
     */
    public function gerarLembrete($idAgendamento) {
        $tem = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_lembrete WHERE fk_agendamento = :id"
        );
        $tem->execute(['id' => $idAgendamento]);
        if ((int) $tem->fetchColumn() > 0) {
            return false;
        }
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_lembrete (fk_agendamento, mensagem, enviado_em, status)
             VALUES (:id, :mensagem, NOW(), 'enviado')"
        );
        return $stmt->execute([
            'id' => $idAgendamento,
            'mensagem' => 'Lembrete de consulta agendada no EloMed.'
        ]);
    }

    /**
     * Returns the ID of the last inserted scheduling
     */
    public function getUltimoIdInserido() {
        return $this->ultimoIdInserido ?: (int) $this->db->lastInsertId();
    }

    /**
     * Stores the AI triage/encaminhamento analysis on the scheduling
     */
    public function salvarAnaliseIA($id, $analise) {
        $stmt = $this->db->prepare(
            "UPDATE tbl_agendamento SET
                classificacao_ia = :classificacao,
                justificativa_classificacao_ia = :justificativa,
                profissional_recomendado_ia = :profissional,
                justificativa_recomendacao_ia = :justif_profissional,
                modelo_ia = :modelo,
                data_analise_ia = NOW()
              WHERE id = :id"
        );
        return $stmt->execute([
            'classificacao' => $analise['classificacao'],
            'justificativa' => $analise['justificativa_classificacao'],
            'profissional' => $analise['profissional_indicado'],
            'justif_profissional' => $analise['justificativa_profissional'],
            'modelo' => $analise['modelo'],
            'id' => $id
        ]);
    }

    /**
     * Saves the doctor's final override for the risk classification (pulseira)
     */
    public function salvarClassificacaoFinal($id, $classificacao) {
        $stmt = $this->db->prepare(
            "UPDATE tbl_agendamento SET classificacao_final = :classificacao WHERE id = :id"
        );
        return $stmt->execute(['classificacao' => $classificacao, 'id' => $id]);
    }

    /**
     * Assigns (or reassigns) the professional responsible for the scheduling,
     * optionally storing the doctor's justification for the encaminhamento.
     *
     * @param int|null $fkOrigem Professional (doctor) who made the reassignment.
     *                  Stored in fk_profissional_origem so the same doctor is the
     *                  one that MUST authenticate the nursing visit form before
     *                  the reminder/notification to the patient is generated.
     */
    public function atribuirProfissional($id, $fkProfissional, $justificativa = null, $fkOrigem = null) {
        $stmt = $this->db->prepare(
            "UPDATE tbl_agendamento
                SET fk_profissional = :prof,
                    fk_profissional_origem = COALESCE(:origem, fk_profissional_origem),
                    justificativa_encaminhamento = COALESCE(:just, justificativa_encaminhamento)
              WHERE id = :id"
        );
        return $stmt->execute([
            'prof' => $fkProfissional,
            'origem' => $fkOrigem ? (int) $fkOrigem : null,
            'just' => ($justificativa !== null && trim($justificativa) !== '') ? $justificativa : null,
            'id' => $id
        ]);
    }

    /**
     * Returns the latest pending triage (id) of the patient linked to the scheduling
     * When the visit came from a triage, that triage is the one used.
     */
    public function getTriagemPendente($idAgendamento) {
        $stmt = $this->db->prepare(
            "SELECT t.id, t.fk_paciente, t.classificacao
               FROM tbl_agendamento a
               JOIN tbl_triagem t ON t.fk_paciente = a.fk_paciente
              WHERE a.id = :id
                AND t.status = 'pendente'
                AND (a.fk_triagem IS NULL OR a.fk_triagem = t.id)
              ORDER BY (a.fk_triagem = t.id) DESC, t.criado_em DESC LIMIT 1"
        );
        $stmt->execute(['id' => $idAgendamento]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Returns the scheduling created from a triage, when it exists.
     */
    public function getPorTriagem($idTriagem) {
        $stmt = $this->db->prepare(
            "SELECT *
               FROM {$this->table}
              WHERE fk_triagem = :triagem
              LIMIT 1"
        );
        $stmt->execute(['triagem' => (int) $idTriagem]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Confirms the visit that the técnico de enfermagem scheduled from a
     * triage once the professional who received the triage approves it.
     * Returns the appointment id when it moved from pendente to agendado.
     */
    public function confirmarVisitaDaTriagem($idTriagem) {
        $ag = $this->getPorTriagem($idTriagem);
        if (!$ag || $ag['status'] !== Constants::AGENDAMENTO_PENDENTE) {
            return null;
        }
        $confirmou = $this->atualizarStatusComCondicao(
            (int) $ag['id'],
            Constants::AGENDAMENTO_AGENDADO,
            Constants::AGENDAMENTO_PENDENTE
        );
        if (!$confirmou) {
            return null;
        }
        $this->gerarLembrete((int) $ag['id']);
        return (int) $ag['id'];
    }

    /**
     * Cancels the visit that the técnico de enfermagem scheduled from a
     * triage when the professional who received the triage refuses it.
     * Returns the appointment id when it moved from pendente to cancelado.
     */
    public function cancelarVisitaDaTriagem($idTriagem, $justificativa = null) {
        $ag = $this->getPorTriagem($idTriagem);
        if (!$ag || $ag['status'] !== Constants::AGENDAMENTO_PENDENTE) {
            return null;
        }
        $motivo = ($justificativa !== null && trim($justificativa) !== '')
            ? $justificativa
            : 'Triagem recusada pelo profissional.';
        return $this->cancelarComJustificativa((int) $ag['id'], $motivo)
            ? (int) $ag['id']
            : null;
    }

    /**
     * Fetches scheduling by ID.
     * Also exposes the legacy aliases (data_agendamento, horario_inicio,
     * horario_fim) derived from the DATETIME columns so existing views/
     * controllers keep working.
     */
    public function getById($id) {
        $stmt = $this->db->prepare(
            "SELECT *,
                    DATE(data_hora_inicio) AS data_agendamento,
                    TIME(data_hora_inicio) AS horario_inicio,
                    TIME(data_hora_fim)    AS horario_fim
               FROM {$this->table}
              WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Fetches the daily-visit limit and pause window of a professional.
     */
    public function getLimitesProfissional($fkProfissional) {
        $stmt = $this->db->prepare(
            "SELECT limite_diario, pausa_inicio, pausa_fim
               FROM tbl_profissional WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $fkProfissional]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Counts the active schedulings a professional already has on a date.
     * Concluded visits are also counted so the daily limit matches the real
     * workload of the day (pendente/agendado/em_andamento/concluido).
     */
    public function contarAgendamentosDia($fkProfissional, $data) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_agendamento
              WHERE fk_profissional = :prof AND DATE(data_hora_inicio) = :data
                AND status IN ('pendente', 'agendado', 'em_andamento', 'concluido')"
        );
        $stmt->execute(['prof' => $fkProfissional, 'data' => $data]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Checks whether a time window overlaps the professional's pause window.
     * The pause times come as TIME (HH:MM:SS) while form times may arrive as
     * H:i without seconds, so both sides are compared in minutes.
     */
    public function conflitaComPausa($limites, $inicio, $fim) {
        $pausaInicio = $limites['pausa_inicio'] ?? null;
        $pausaFim = $limites['pausa_fim'] ?? null;
        if (empty($pausaInicio) || empty($pausaFim)) {
            return false;
        }
        $inicioMin = $this->paraMinutos($inicio);
        $fimMin    = $this->paraMinutos($fim);
        $pausaInicioMin = $this->paraMinutos($pausaInicio);
        $pausaFimMin    = $this->paraMinutos($pausaFim);
        return $inicioMin < $pausaFimMin && $pausaInicioMin < $fimMin;
    }

    /**
     * Reschedules an appointment (patient side) when it is still pending/agendado.
     * Revalidates all scheduling rules (daily limit, pause window, time conflicts
     * excluding the appointment itself) before updating. Increments the reschedule
     * counter; the new date falls back to 'pendente' so the professional revalidates it.
     */
    public function reagendar($id, $data, $inicio, $fim) {
        $ag = $this->getById($id);
        if (!$ag || !in_array($ag['status'], [Constants::AGENDAMENTO_PENDENTE, Constants::AGENDAMENTO_AGENDADO], true)) {
            return false;
        }
        $id = (int) $id;
        $limites = $this->getLimitesProfissional($ag['fk_profissional']);
        if ($limites && !empty($limites['limite_diario'])
            && $this->contarAgendamentosDia($ag['fk_profissional'], $data) >= (int) $limites['limite_diario']) {
            return false;
        }
        if ($limites && $this->conflitaComPausa($limites, $inicio, $fim)) {
            return false;
        }
        if ($this->verificarConflito($ag['fk_profissional'], $data, $inicio, $fim, $id)) {
            return false;
        }
        if ($this->verificarConflitoPaciente($ag['fk_paciente'], $data, $inicio, $fim, $id)) {
            return false;
        }

        $inicioDt = $this->montarDatetime($data, $inicio);
        $fimDt    = $this->montarDatetime($data, $fim);
        $stmt = $this->db->prepare(
            "UPDATE tbl_agendamento
                SET data_hora_inicio = :inicio_dt,
                    data_hora_fim = :fim_dt,
                    status = :status,
                    vezes_reagendado = vezes_reagendado + 1
              WHERE id = :id AND status IN ('pendente', 'agendado')"
        );
        return $stmt->execute([
            'inicio_dt' => $inicioDt,
            'fim_dt'    => $fimDt,
            'status' => Constants::AGENDAMENTO_PENDENTE,
            'id'     => $id
        ]);
    }

    /**
     * Converts a time string (H, H:i or H:i:s) to minutes since midnight.
     */
    private function paraMinutos($hora): int {
        $partes = array_map('intval', explode(':', trim((string) $hora)));
        return ($partes[0] ?? 0) * 60 + ($partes[1] ?? 0);
    }

    /**
     * Checks if scheduling belongs to the professional (business rule)
     */
    public function belongsToProfissional($id, $fkProfissional) {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE id = :id AND fk_profissional = :prof");
        $stmt->execute(['id' => $id, 'prof' => $fkProfissional]);
        return (bool) $stmt->fetchColumn();
    }
}
