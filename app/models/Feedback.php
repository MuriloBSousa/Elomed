<?php


/**
 * Feedback do paciente sobre o atendimento recebido.
 *
 * Regras de negócio:
 *  - só existe para atendimentos já concluídos;
 *  - um paciente avalia uma única vez cada atendimento (uq_feedback_atendimento);
 *  - a nota vai de 0 a 5 estrelas e o comentário é opcional;
 *  - a leitura é restrita ao administrador (permissão feedback_visualizar),
 *    nunca é exposta ao profissional avaliado nem a outros perfis.
 */
class Feedback extends Model {
    protected $table = 'tbl_feedback';

    /**
     * Atendimentos concluídos do paciente que ainda não foram avaliados.
     */
    public function listarAvaliaveisPorPaciente($pacienteId) {
        $stmt = $this->db->prepare(
            "SELECT at.id AS id_atendimento,
                    DATE(ag.data_hora_inicio) AS data_agendamento,
                    TIME(ag.data_hora_inicio) AS horario_inicio,
                    pr.id AS id_profissional,
                    u.nome AS nome_profissional,
                    pr.especialidade
               FROM tbl_atendimento at
               JOIN tbl_agendamento ag ON ag.id = at.fk_agendamento
               JOIN tbl_profissional pr ON pr.id = at.fk_profissional
               JOIN tbl_usuario u ON u.id = pr.fk_usuario
               LEFT JOIN tbl_feedback f
                      ON f.fk_atendimento = at.id
                     AND f.fk_paciente = :paciente_fb
              WHERE ag.fk_paciente = :paciente
                AND at.status = 'concluido'
                AND f.id IS NULL
              ORDER BY ag.data_hora_inicio DESC"
        );
        $stmt->execute([
            'paciente' => (int) $pacienteId,
            'paciente_fb' => (int) $pacienteId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Atendimentos já avaliados por este paciente (para exibir "avaliado").
     */
    public function listarAvaliadosPorPaciente($pacienteId) {
        $stmt = $this->db->prepare(
            "SELECT f.id, f.fk_atendimento AS id_atendimento, f.nota, f.comentario, f.criado_em,
                    ag.data_hora_inicio, u.nome AS nome_profissional
               FROM tbl_feedback f
               JOIN tbl_atendimento at ON at.id = f.fk_atendimento
               JOIN tbl_agendamento ag ON ag.id = at.fk_agendamento
               JOIN tbl_profissional pr ON pr.id = f.fk_profissional
               JOIN tbl_usuario u ON u.id = pr.fk_usuario
              WHERE f.fk_paciente = :paciente
              ORDER BY f.criado_em DESC"
        );
        $stmt->execute(['paciente' => (int) $pacienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Confere se o atendimento pertence ao paciente, já foi concluído e ainda
     * não foi avaliado. Devolve os dados normalizados ou null.
     */
    public function validarAvaliacao($idAtendimento, $pacienteId) {
        $stmt = $this->db->prepare(
            "SELECT a.id AS id_atendimento, a.fk_profissional, a.status,
                    ag.fk_paciente
               FROM tbl_atendimento a
               JOIN tbl_agendamento ag ON ag.id = a.fk_agendamento
               LEFT JOIN tbl_feedback f
                      ON f.fk_atendimento = a.id
              WHERE a.id = :id
                AND ag.fk_paciente = :paciente"
        );
        $stmt->execute([
            'id' => (int) $idAtendimento,
            'paciente' => (int) $pacienteId
        ]);

        $linha = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$linha) {
            return null;
        }
        if ($linha['status'] !== 'concluido') {
            return null;
        }
        return $linha;
    }

    /**
     * Grava a avaliação. Retorna false quando já existe uma para o mesmo atendimento.
     */
    public function registrar($idAtendimento, $profissionalId, $pacienteId, $usuarioId, $nota, $comentario = null) {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO {$this->table}
                (fk_atendimento, fk_profissional, fk_paciente, fk_usuario, nota, comentario)
             VALUES (:atendimento, :profissional, :paciente, :usuario, :nota, :comentario)"
        );
        return $stmt->execute([
            'atendimento' => (int) $idAtendimento,
            'profissional' => (int) $profissionalId,
            'paciente' => (int) $pacienteId,
            'usuario' => (int) $usuarioId,
            'nota' => (int) $nota,
            'comentario' => $comentario
        ]) && $stmt->rowCount() > 0;
    }

    /**
     * Lista completa para o painel administrativo.
     */
    public function listarTodos($limite = 200) {
        $stmt = $this->db->prepare(
            "SELECT f.id, f.nota, f.comentario, f.criado_em,
                    f.fk_atendimento AS id_atendimento,
                    ag.data_hora_inicio,
                    up.nome AS nome_profissional,
                    pr.especialidade,
                    pu.nome AS nome_paciente
               FROM {$this->table} f
               JOIN tbl_atendimento at ON at.id = f.fk_atendimento
               JOIN tbl_agendamento ag ON ag.id = at.fk_agendamento
               JOIN tbl_profissional pr ON pr.id = f.fk_profissional
               JOIN tbl_usuario up ON up.id = pr.fk_usuario
               JOIN tbl_paciente pac ON pac.id = f.fk_paciente
               JOIN tbl_usuario pu ON pu.id = pac.fk_usuario
              ORDER BY f.criado_em DESC
              LIMIT :limite"
        );
        $stmt->bindValue('limite', (int) $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Média e total de avaliações por profissional.
     */
    public function resumoPorProfissional() {
        $stmt = $this->db->prepare(
            "SELECT f.fk_profissional AS id_profissional,
                    u.nome AS nome_profissional,
                    p.especialidade,
                    COUNT(*) AS total,
                    ROUND(AVG(f.nota), 2) AS media,
                    SUM(f.nota = 5) AS cinco_estrelas,
                    SUM(f.nota <= 2) AS criticas
               FROM {$this->table} f
               JOIN tbl_profissional p ON p.id = f.fk_profissional
               JOIN tbl_usuario u ON u.id = p.fk_usuario
              GROUP BY f.fk_profissional, u.nome, p.especialidade
              ORDER BY media DESC, total DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Indicadores gerais da tela administrativa.
     */
    public function indicadores() {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    ROUND(AVG(nota), 2) AS media,
                    SUM(nota = 5) AS cinco_estrelas,
                    SUM(nota = 4) AS quatro_estrelas,
                    SUM(nota = 3) AS tres_estrelas,
                    SUM(nota = 2) AS duas_estrelas,
                    SUM(nota = 1) AS uma_estrela,
                    SUM(nota = 0) AS zero_estrelas
               FROM {$this->table}"
        );
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
