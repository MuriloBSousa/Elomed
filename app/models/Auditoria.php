<?php
require_once __DIR__ . '/Model.php';

class Auditoria extends Model {
    protected $table = 'tbl_auditoria';

    public function registrar(string $acao, string $descricao, array $propriedades = []) {
        $data = $propriedades;
        if (!isset($data['descricao'])) {
            $data['descricao'] = $descricao;
        }
        if (!isset($data['usuario_perfil'])) {
            $data['usuario_perfil'] = Session::get('usuario_perfil');
        }
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_auditoria
                (fk_usuario, usuario_nome, usuario_perfil, acao, descricao, propriedades, ip)
             VALUES
                (:fk_usuario, :usuario_nome, :usuario_perfil, :acao, :descricao, :propriedades, :ip)"
        );
        return $stmt->execute([
            'fk_usuario'    => Session::get('usuario_id') ?: null,
            'usuario_nome'  => Session::get('usuario_nome'),
            'usuario_perfil'=> $data['usuario_perfil'],
            'acao'          => $acao,
            'descricao'     => $descricao,
            'propriedades'  => json_encode($data, JSON_UNESCAPED_UNICODE),
            'ip'            => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    public function listar(string $where, array $params, int $limite, int $offset) {
        $sql = "SELECT id, acao, usuario_nome, ip,
                       data_hora,
                       propriedades,
                       descricao,
                       usuario_perfil
                  FROM tbl_auditoria{$where}
                 ORDER BY data_hora DESC
                 LIMIT :limite OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contar(string $where, array $params) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_auditoria{$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function listarAcoes() {
        return $this->db->query(
            "SELECT DISTINCT acao FROM tbl_auditoria ORDER BY acao"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Access events (login/logout) with optional filters.
     */
    public function registrarAcesso($de = '', $ate = '', $por = '', int $limite = 200) {
        $conditions = ["acao IN ('login', 'logout')"];
        $params = [];
        if ($de !== '') { $conditions[] = 'DATE(data_hora) >= :de';   $params['de']  = $de; }
        if ($ate !== '') { $conditions[] = 'DATE(data_hora) <= :ate';  $params['ate'] = $ate; }
        if ($por !== '') { $conditions[] = "(usuario_nome LIKE :por OR usuario_perfil LIKE :por)"; $params['por'] = '%' . Model::likeEscape($por) . '%'; }
        $where = ' WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT id, acao, usuario_nome, ip, data_hora, usuario_perfil
                  FROM tbl_auditoria{$where}
                 ORDER BY data_hora DESC
                 LIMIT :limite";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Session summary by user and day: first entry and last exit.
     */
    public function resumoSessoes($de = '', $ate = '', $por = '') {
        $conditions = ["acao IN ('login', 'logout')"];
        $params = [];
        if ($de !== '') { $conditions[] = 'DATE(data_hora) >= :de';   $params['de']  = $de; }
        if ($ate !== '') { $conditions[] = 'DATE(data_hora) <= :ate';  $params['ate'] = $ate; }
        if ($por !== '') { $conditions[] = "(usuario_nome LIKE :por OR usuario_perfil LIKE :por)"; $params['por'] = '%' . Model::likeEscape($por) . '%'; }
        $where = ' WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT usuario_nome,
                       usuario_perfil,
                       DATE(data_hora) AS dia,
                       MIN(CASE WHEN acao = 'login'  THEN data_hora END) AS primeira_entrada,
                       MAX(CASE WHEN acao = 'logout' THEN data_hora END) AS ultima_saida
                  FROM tbl_auditoria{$where}
                 GROUP BY usuario_nome,
                          usuario_perfil,
                          DATE(data_hora)
                 ORDER BY dia DESC, primeira_entrada DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Completed visits (who treated whom, with diagnosis when available).
     */
    public function visitasRealizadas(int $limite = 50) {
        $sql = "SELECT a.id, DATE(a.data_hora_inicio) AS data_agendamento,
                       TIME(a.data_hora_inicio) AS horario_inicio,
                       TIME(a.data_hora_fim)    AS horario_fim,
                       a.status,
                       u.nome  AS nome_profissional, p.especialidade,
                       up.nome AS nome_paciente,
                       pr.diagnostico_final
                  FROM tbl_agendamento a
                  JOIN tbl_profissional p  ON a.fk_profissional = p.id
                  JOIN tbl_usuario u       ON p.fk_usuario = u.id
                  JOIN tbl_paciente pac    ON a.fk_paciente = pac.id
                  JOIN tbl_usuario up      ON pac.fk_usuario = up.id
             LEFT JOIN tbl_atendimento ta  ON ta.fk_agendamento = a.id
             LEFT JOIN tbl_prontuario pr   ON pr.fk_atendimento = ta.id
                 WHERE a.status = 'concluido'
                 ORDER BY a.data_hora_inicio DESC
                 LIMIT :limite";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarAcessosHoje() {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM tbl_auditoria
              WHERE acao = 'login' AND DATE(data_hora) = CURDATE()"
        );
        return (int) $stmt->fetchColumn();
    }

    public function contarAcoes() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM tbl_auditoria");
        return (int) $stmt->fetchColumn();
    }

    public function contarUsuariosAtivos() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM tbl_usuario WHERE status = 'ativo'");
        return (int) $stmt->fetchColumn();
    }

    public function contarVisitasConcluidas() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM tbl_agendamento WHERE status = 'concluido'");
        return (int) $stmt->fetchColumn();
    }
}
