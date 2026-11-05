<?php
class ProntuarioRegistro extends Model {
    protected $table = 'tbl_prontuario_registro';

    /**
     * Secoes do prontuario, na ordem em que aparecem as abas.
     * A chave e o valor do ENUM 'secao' no banco.
     */
    public const SECOES = [
        Constants::PRONTUARIO_SECAO_ANAMNESE     => 'Anamnese',
        Constants::PRONTUARIO_SECAO_EVOLUCAO    => 'Evolucao e Tratamento',
        Constants::PRONTUARIO_SECAO_COMPLEMENTAR => 'Informacoes Complementares',
    ];

    /**
     * Um lancamento de uma secao, do mais recente para o mais antigo.
     * Traz o nome do autor e a especialidade dele, que e o que a
     * pagina precisa mostrar em cada linha do historico.
     *
     * ATENCAO AO NOME DA COLUNA: em tbl_agendamento a coluna do fim
     * do atendimento se chama data_hora_fim. Nao existe "horario_fim"
     * em lugar nenhum do schema - se voce digitar horario_fim aqui, a
     * pagina inteira da secao quebra com
     * "Unknown column 'a.horario_fim' in 'field list'".
     */
    public function listarPorSecao($pacienteId, string $secao) {
        $stmt = $this->db->prepare(
            "SELECT r.*,
                    u.nome AS autor_nome,
                    pr.numero_registro AS autor_registro,
                    pr.especialidade  AS autor_especialidade,
                    a.data_hora_inicio AS atendimento_inicio,
                    a.data_hora_fim    AS atendimento_fim
               FROM {$this->table} r
               JOIN tbl_usuario u ON u.id = r.fk_usuario
          LEFT JOIN tbl_profissional pr ON pr.id = r.fk_profissional
          LEFT JOIN tbl_agendamento a   ON a.id = r.fk_agendamento
              WHERE r.fk_paciente = :paciente
                AND r.secao = :secao
           ORDER BY r.data_hora DESC, r.id DESC"
        );
        $stmt->execute(['paciente' => $pacienteId, 'secao' => $secao]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Quantos registros existem em cada secao (para os badges das abas).
     * Uma consulta so, em vez de tres.
     */
    public function contarPorSecao($pacienteId): array {
        $stmt = $this->db->prepare(
            "SELECT secao, COUNT(*) AS total
               FROM {$this->table}
              WHERE fk_paciente = :paciente
           GROUP BY secao"
        );
        $stmt->execute(['paciente' => $pacienteId]);

        $contadores = array_fill_keys(array_keys(self::SECOES), 0);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            if (isset($contadores[$linha['secao']])) {
                $contadores[$linha['secao']] = (int)$linha['total'];
            }
        }
        return $contadores;
    }

    public function getById($id) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function ultimoId(): int {
        return (int)$this->db->lastInsertId();
    }

    public function criar(array $dados): bool {
        // data_hora e opcional: quando o controller nao manda, o
        // DEFAULT CURRENT_TIMESTAMP do banco entra no lugar. Nunca
        // mande string vazia aqui, senao o MySQL reclama.
        $colunas = '(fk_paciente, fk_usuario, fk_profissional, fk_agendamento,
                 secao, conteudo, status)';
        $valores = '(:fk_paciente, :fk_usuario, :fk_profissional, :fk_agendamento,
                 :secao, :conteudo, :status)';
        $params = [
            'fk_paciente'     => $dados['fk_paciente'],
            'fk_usuario'      => $dados['fk_usuario'],
            'fk_profissional' => $dados['fk_profissional'] ?: null,
            'fk_agendamento'  => $dados['fk_agendamento'] ?: null,
            'secao'           => $dados['secao'],
            'conteudo'        => $dados['conteudo'],
            'status'          => $dados['status'],
        ];
        if (!empty($dados['data_hora'])) {
            $colunas = str_replace('status)', 'status, data_hora)', $colunas);
            $valores = str_replace(':status)', ':status, :data_hora)', $valores);
            $params['data_hora'] = $dados['data_hora'];
        }
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} {$colunas} VALUES {$valores}"
        );
        return $stmt->execute($params);
    }

    /**
     * Edita APENAS rascunho. O WHERE com status = 'rascunho' e a
     * protecao: mesmo que o controller erre a checagem, o banco
     * recusa a alteracao de um registro finalizado.
     */
    public function atualizarRascunho($id, string $conteudo): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
                SET conteudo = :conteudo
              WHERE id = :id
                AND status = 'rascunho'"
        );
        return $stmt->execute(['conteudo' => $conteudo, 'id' => $id]);
    }

    public function finalizar($id): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
                SET status = 'finalizado'
              WHERE id = :id
                AND status = 'rascunho'"
        );
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Apaga APENAS rascunho. Registro finalizado e intocavel - para
     * corrigir, o medico cria outro.
     */
    public function excluir($id): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table}
              WHERE id = :id
                AND status = 'rascunho'"
        );
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Historico completo do paciente, todas as secoes (usado no PDF e
     * para mostrar a linha do tempo).
     */
    public function timeline($pacienteId, $limite = 200) {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.nome AS autor_nome, pr.especialidade AS autor_especialidade
               FROM {$this->table} r
               JOIN tbl_usuario u ON u.id = r.fk_usuario
          LEFT JOIN tbl_profissional pr ON pr.id = r.fk_profissional
              WHERE r.fk_paciente = :paciente
           ORDER BY r.data_hora DESC, r.id DESC
              LIMIT :limite"
        );
        $stmt->bindValue(':paciente', $pacienteId, PDO::PARAM_INT);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
