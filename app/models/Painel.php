<?php


class Painel extends Model {
    public function getEstatisticaPainel(): array {
        $totalPatientsServed = $this->db->query("SELECT COUNT(*) FROM tbl_atendimento WHERE status = 'concluido'")->fetchColumn();
        $teamInField = $this->db->query("SELECT COUNT(DISTINCT fk_profissional) FROM tbl_atendimento WHERE status = 'em_andamento'")->fetchColumn();
        $visitsToday = $this->db->query("SELECT COUNT(*) FROM tbl_atendimento WHERE DATE(horario_inicio) = CURDATE() AND status <> 'cancelado'")->fetchColumn();
        $pendingTriages = $this->db->query("SELECT COUNT(*) FROM tbl_triagem WHERE status = 'pendente'")->fetchColumn();

        $averageTime = $this->db->query(
            "SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, horario_inicio, horario_fim)), 0)
               FROM tbl_atendimento WHERE status = 'concluido'"
        )->fetchColumn();

        $occupancy = $this->db->query(
            "SELECT ROUND(
                     SUM(status IN ('agendado', 'em_andamento')) * 100.0 / NULLIF(COUNT(*), 0)
                   ) AS ocupacao
                FROM tbl_agendamento WHERE DATE(data_hora_inicio) = CURDATE()"
        )->fetchColumn();

        return [
            'pacientes_atendidos' => (int)$totalPatientsServed,
            'equipe_campo'        => (int)$teamInField,
            'visitas_hoje'        => (int)$visitsToday,
            'triagens_pendentes'  => (int)$pendingTriages,
            'tempo_medio'         => (int) round($averageTime),
            'ocupacao_agenda'     => (int) $occupancy
        ];
    }

    public function getEscalaDoDia(): array {
        $sql = "SELECT 
            a.id AS agendamento_id,
            DATE(a.data_hora_inicio) AS data_agendamento,
            TIME(a.data_hora_inicio) AS horario_inicio,
            TIME(a.data_hora_fim)    AS horario_fim,
            a.status,
            u_prof.nome AS profissional_nome,
            prof.especialidade,
            u_pac.nome AS paciente_nome
            FROM tbl_agendamento AS a
            JOIN tbl_profissional AS prof ON a.fk_profissional = prof.id
            JOIN tbl_usuario AS u_prof ON prof.fk_usuario = u_prof.id
            JOIN tbl_paciente AS pac ON a.fk_paciente = pac.id
            JOIN tbl_usuario AS u_pac ON pac.fk_usuario = u_pac.id
            WHERE DATE(a.data_hora_inicio) = CURDATE()
            ORDER BY a.data_hora_inicio ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTriagens(string $data = ''): array {
        $sql = "SELECT t.id, t.nome_solicitante, t.classificacao, t.status, t.criado_em,
                       u.nome AS paciente_nome
                  FROM tbl_triagem t
                  JOIN tbl_paciente p ON t.fk_paciente = p.id
                  JOIN tbl_usuario u ON p.fk_usuario = u.id";
        if ($data !== '') {
            $sql .= " WHERE DATE(t.criado_em) = :data";
        }
        $sql .= " ORDER BY t.criado_em DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data !== '' ? ['data' => $data] : []);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProntuarios(string $data = ''): array {
        $sql = "SELECT DATE(a.data_hora_inicio) AS data_agendamento,
                       TIME(a.data_hora_inicio) AS horario_inicio,
                       a.status,
                       u_pac.nome AS paciente_nome,
                       u_prof.nome AS profissional_nome,
                       pr.diagnostico_final, pr.conduta_medica
                  FROM tbl_agendamento a
                  JOIN tbl_paciente pac ON a.fk_paciente = pac.id
                  JOIN tbl_usuario u_pac ON pac.fk_usuario = u_pac.id
                  JOIN tbl_profissional prof ON a.fk_profissional = prof.id
                  JOIN tbl_usuario u_prof ON prof.fk_usuario = u_prof.id
                  LEFT JOIN tbl_atendimento ta ON ta.fk_agendamento = a.id
                  LEFT JOIN tbl_prontuario pr ON pr.fk_atendimento = ta.id
                 WHERE a.status IN ('concluido', 'em_andamento')";
        if ($data !== '') {
            $sql .= " AND DATE(a.data_hora_inicio) = :data";
        }
        $sql .= " ORDER BY a.data_hora_inicio DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data !== '' ? ['data' => $data] : []);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
