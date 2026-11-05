<?php


class Paciente extends Model {
    protected $table = 'tbl_paciente';
    
    /**
     * Fetches patient ID by user ID
     */
    public function getIdByUsuario($usuarioId) {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE fk_usuario = :fk");
        $stmt->execute(['fk' => $usuarioId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['id'] ?? null;
    }
    
    /**
     * Fetches the latest triage for the patient
     */
    public function getUltimaTriagem($pacienteId) {
        $stmt = $this->db->prepare("SELECT nome_solicitante, status, classificacao, criado_em 
                                     FROM tbl_triagem 
                                     WHERE fk_paciente = :fk 
                                     ORDER BY criado_em DESC LIMIT 1");
        $stmt->execute(['fk' => $pacienteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches all triages for the patient
     */
    public function getTriagens($pacienteId) {
        $stmt = $this->db->prepare("SELECT t.id, t.nome_solicitante, t.classificacao, t.status,
                                             t.criado_em AS data_criacao,
                                             a.id AS id_agendamento,
                                             DATE(a.data_hora_inicio) AS data_visita,
                                             TIME(a.data_hora_inicio) AS horario_inicio,
                                             TIME(a.data_hora_fim)    AS horario_fim,
                                             a.status AS agendamento_status
                                      FROM tbl_triagem t
                                      LEFT JOIN tbl_agendamento a ON a.fk_triagem = t.id
                                     WHERE t.fk_paciente = :fk 
                                     ORDER BY t.criado_em DESC");
        $stmt->execute(['fk' => $pacienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches all schedulings for the patient
     */
    public function getAgendamentos($pacienteId) {
        $stmt = $this->db->prepare("SELECT a.id,
                                             DATE(a.data_hora_inicio) AS data_agendamento,
                                             TIME(a.data_hora_inicio) AS horario_inicio,
                                             TIME(a.data_hora_fim)    AS horario_fim,
                                             a.status,
                                             a.vezes_reagendado,
                                             a.fk_triagem,
                                             u.nome AS nome_profissional, p.especialidade
                                      FROM tbl_agendamento a
                                      JOIN tbl_profissional p ON a.fk_profissional = p.id
                                      JOIN tbl_usuario u ON p.fk_usuario = u.id
                                      WHERE a.fk_paciente = :fk 
                                      ORDER BY a.data_hora_inicio DESC");
        $stmt->execute(['fk' => $pacienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches completed visits for the patient
     */
    public function getVisitasConcluidas($pacienteId) {
        $stmt = $this->db->prepare("SELECT a.id,
                                             DATE(a.data_hora_inicio) AS data_agendamento,
                                             TIME(a.data_hora_inicio) AS horario_inicio,
                                             TIME(a.data_hora_fim)    AS horario_fim,
                                             ta.id AS id_atendimento,
                                             u.nome AS nome_profissional, p.especialidade,
                                             pr.diagnostico_final, pr.conduta_medica
                                      FROM tbl_agendamento a
                                      JOIN tbl_profissional p ON a.fk_profissional = p.id
                                      JOIN tbl_usuario u ON p.fk_usuario = u.id
                                      LEFT JOIN tbl_atendimento ta ON ta.fk_agendamento = a.id
                                      LEFT JOIN tbl_prontuario pr ON pr.fk_atendimento = ta.id
                                      WHERE a.fk_paciente = :fk 
                                        AND a.status = 'concluido'
                                      ORDER BY a.data_hora_inicio DESC");
        $stmt->execute(['fk' => $pacienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Creates a new triage for the patient
     */
    public function criarTriagem($pacienteId, $nomeSolicitante, $classificacao = null, $queixaPrincipal = null, $descricaoSintomas = null, $dataInicioSintomas = null) {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_triagem (fk_paciente, nome_solicitante, queixa_principal, descricao_sintomas, data_inicio_sintomas, classificacao, status, criado_em)
             VALUES (:fk_paciente, :nome_solicitante, :queixa_principal, :descricao_sintomas, :data_inicio_sintomas, :classificacao, 'pendente', NOW())"
        );
        $stmt->execute([
            'fk_paciente' => $pacienteId,
            'nome_solicitante' => $nomeSolicitante,
            'queixa_principal' => $queixaPrincipal,
            'descricao_sintomas' => $descricaoSintomas,
            'data_inicio_sintomas' => $dataInicioSintomas,
            'classificacao' => $classificacao
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Stores the preliminary assessment produced by the AI at the moment the
     * triage is requested. The técnico de enfermagem reviews it and may (or may
     * not) follow the recommendation — it never decides on its own.
     */
    public function registrarAvaliacaoIA($idTriagem, array $avaliacao) {
        $stmt = $this->db->prepare(
            "UPDATE tbl_triagem
                SET ia_classificacao = :classificacao,
                    ia_justificativa_classificacao = :just_classificacao,
                    ia_perfil_recomendado = :perfil,
                    ia_justificativa_perfil = :just_perfil,
                    ia_profissional_sugerido = :profissional,
                    ia_modelo = :modelo,
                    ia_analisada_em = NOW()
              WHERE id = :id"
        );
        return $stmt->execute([
            'classificacao'      => $avaliacao['classificacao'] ?? null,
            'just_classificacao' => $avaliacao['justificativa_classificacao'] ?? null,
            'perfil'             => $avaliacao['perfil_recomendado'] ?? null,
            'just_perfil'        => $avaliacao['justificativa_perfil'] ?? null,
            'profissional'       => $avaliacao['profissional_sugerido'] ?? null,
            'modelo'             => $avaliacao['modelo'] ?? null,
            'id'                 => (int) $idTriagem
        ]);
    }

    /**
     * Approves a pending triage (optionally applying the final classification)
     */
    public function aprovarTriagem($idTriagem, $classificacao = null) {
        $stmt = $this->db->prepare(
            "UPDATE tbl_triagem
                SET status = 'aprovada', data_aprovacao = NOW(),
                    classificacao = COALESCE(:classificacao, classificacao)
              WHERE id = :id AND status = 'pendente'"
        );
        return $stmt->execute(['classificacao' => $classificacao, 'id' => $idTriagem]);
    }

    /**
     * Refuses a pending triage with an optional justification
     */
    public function recusarTriagem($idTriagem, $justificativa = null) {
        $stmt = $this->db->prepare(
            "UPDATE tbl_triagem
                SET status = 'recusada', data_aprovacao = NOW(),
                    justificativa_reprovacao = :justificativa
              WHERE id = :id AND status = 'pendente'"
        );
        return $stmt->execute(['justificativa' => $justificativa, 'id' => $idTriagem]);
    }

    /**
     * Patient timeline
     */
    public function getLinhaDoTempo($pacienteId) {
        $sql = "SELECT 'agendamento' AS tipo,
                       DATE_FORMAT(a.data_hora_inicio, '%d/%m/%Y') AS data_evento,
                       TIME(a.data_hora_inicio) AS horario, a.status AS status,
                       a.justificativa_cancelamento AS detalhe
                  FROM tbl_agendamento a WHERE a.fk_paciente = :id
                 UNION ALL
                SELECT 'triagem',
                       DATE_FORMAT(t.criado_em, '%d/%m/%Y'),
                       NULL, t.status,
                       CONCAT('Solicitante: ', t.nome_solicitante,
                              IF(t.classificacao IS NULL, '', CONCAT(' | Risco: ', t.classificacao)))
                  FROM tbl_triagem t WHERE t.fk_paciente = :id
                 UNION ALL
                SELECT 'visita',
                       DATE_FORMAT(at.horario_inicio, '%d/%m/%Y'),
                       TIME(at.horario_inicio), at.status, at.observacoes
                  FROM tbl_atendimento at
                  JOIN tbl_agendamento a ON a.id = at.fk_agendamento
                 WHERE a.fk_paciente = :id
                 ORDER BY STR_TO_DATE(data_evento, '%d/%m/%Y') DESC, horario DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $pacienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches patient reminders
     */
    public function getLembretes($pacienteId) {
        $stmt = $this->db->prepare(
            "SELECT l.enviado_em AS data_aviso, l.mensagem, l.status,
                    DATE(a.data_hora_inicio) AS data_agendamento,
                    TIME(a.data_hora_inicio) AS horario_inicio
               FROM tbl_lembrete l
               JOIN tbl_agendamento a ON a.id = l.fk_agendamento
              WHERE a.fk_paciente = :id
              ORDER BY l.enviado_em DESC"
        );
        $stmt->execute(['id' => $pacienteId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Fetches next upcoming scheduling for the patient
     */
    public function getProximoAgendamento($pacienteId) {
        $stmt = $this->db->prepare("SELECT DATE(a.data_hora_inicio) AS data_agendamento,
                                             TIME(a.data_hora_inicio) AS horario_inicio,
                                             TIME(a.data_hora_fim)    AS horario_fim,
                                             a.status,
                                             u.nome AS nome_profissional, p.especialidade
                                      FROM tbl_agendamento a
                                      JOIN tbl_profissional p ON a.fk_profissional = p.id
                                      JOIN tbl_usuario u ON p.fk_usuario = u.id
                                      WHERE a.fk_paciente = :fk 
                                        AND a.data_hora_inicio >= NOW()
                                        AND a.status IN ('agendado', 'pendente')
                                      ORDER BY a.data_hora_inicio ASC 
                                      LIMIT 1");
        $stmt->execute(['fk' => $pacienteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetches future schedulings for the patient
     */
    public function getAgendamentosFuturos($pacienteId) {
        $stmt = $this->db->prepare("SELECT DATE(a.data_hora_inicio) AS data_agendamento,
                                             TIME(a.data_hora_inicio) AS horario_inicio,
                                             TIME(a.data_hora_fim)    AS horario_fim,
                                             a.status,
                                             u.nome AS nome_profissional, p.especialidade
                                      FROM tbl_agendamento a
                                      JOIN tbl_profissional p ON a.fk_profissional = p.id
                                      JOIN tbl_usuario u ON p.fk_usuario = u.id
                                      WHERE a.fk_paciente = :fk 
                                        AND a.data_hora_inicio >= NOW()
                                        AND a.status IN ('agendado', 'pendente')
                                      ORDER BY a.data_hora_inicio ASC 
                                      LIMIT 10");
        $stmt->execute(['fk' => $pacienteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetches the full patient record by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Fetches the full patient record by user ID
     */
    public function getByUsuario($usuarioId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE fk_usuario = :fk LIMIT 1");
        $stmt->execute(['fk' => $usuarioId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: false;
    }

    /**
     * Lists all active patient accounts (user id + name) for the admin
     * to schedule visits on their behalf.
     */
    public function listarTodos() {
        return $this->db->query(
            "SELECT u.id AS fk_usuario, p.id AS id_paciente, u.nome
               FROM tbl_usuario u
               JOIN tbl_paciente p ON p.fk_usuario = u.id
              WHERE u.status = 'ativo'
              ORDER BY u.nome ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Upserts the complementary registration data into the normalized tables
     * (telefone, endereco, contato_emergencia, convenio, anamnese).
     * Accepts the legacy flat keys produced by the controllers/forms.
     */
    private function sincronizarDadosComplementares($pacienteId, $fkUsuario, array $d) {
        if (($d['telefone_principal'] ?? '') !== '') {
            $tel = ['fk_usuario' => $fkUsuario, 'telefone' => $d['telefone_principal']];
            $existe = $this->db->prepare("SELECT id FROM tbl_telefone WHERE fk_usuario = :fk AND tipo = 'principal'");
            $existe->execute(['fk' => $fkUsuario]);
            if ($existe->fetchColumn()) {
                // A chave do array e 'fk_usuario', entao o SQL tem que
                // usar :fk_usuario. Antes o UPDATE referenciava :fk e o
                // PDO devolvia "HY093 Invalid parameter number" - o
                // telefone so era salvo na primeira vez (INSERT).
                $this->db->prepare(
                    "UPDATE tbl_telefone SET telefone = :telefone WHERE fk_usuario = :fk_usuario AND tipo = 'principal'"
                )->execute($tel);
            } else {
                $this->db->prepare(
                    "INSERT INTO tbl_telefone (fk_usuario, telefone, tipo) VALUES (:fk_usuario, :telefone, 'principal')"
                )->execute($tel);
            }
        }

        if (($d['endereco_rua'] ?? '') !== '') {
            $end = [
                'fk_usuario'  => $fkUsuario,
                'logradouro'  => $d['endereco_rua'],
                'numero'      => $d['endereco_numero'] ?? '',
                'complemento' => $d['endereco_complemento'] ?? null,
                'bairro'      => $d['endereco_bairro'] ?? '',
                'cep'         => $d['endereco_cep'] ?? '',
                'cidade'      => $d['endereco_cidade'] ?? '',
                'uf'          => $d['endereco_uf'] ?? ''
            ];
            $existe = $this->db->prepare("SELECT id FROM tbl_endereco WHERE fk_usuario = :fk AND principal = 1");
            $existe->execute(['fk' => $fkUsuario]);
            $endSql = "fk_usuario = :fk_usuario, logradouro = :logradouro, numero = :numero,
                       complemento = :complemento, bairro = :bairro, cep = :cep,
                       cidade = :cidade, uf = :uf";
            if ($existe->fetchColumn()) {
                $this->db->prepare(
                    "UPDATE tbl_endereco SET {$endSql} WHERE fk_usuario = :fk_usuario AND principal = 1"
                )->execute($end);
            } else {
                $this->db->prepare(
                    "INSERT INTO tbl_endereco (fk_usuario, logradouro, numero, complemento, bairro, cep, cidade, uf, principal)
                     VALUES (:fk_usuario, :logradouro, :numero, :complemento, :bairro, :cep, :cidade, :uf, 1)"
                )->execute($end);
            }
        }

        $contatoNome = $d['contato_nome'] ?? '';
        $contatoTel = $d['contato_telefone'] ?? ($d['tel_emergencia'] ?? ($d['contato_emergencia'] ?? ''));
        if ($contatoTel !== '') {
            $ct = [
                'fk_paciente' => $pacienteId,
                'nome'        => $contatoNome !== '' ? $contatoNome : 'Contato',
                'parentesco'  => $d['contato_parentesco'] ?? null,
                'telefone'    => $contatoTel,
                'observacao'  => null
            ];
            $existe = $this->db->prepare("SELECT id FROM tbl_contato_emergencia WHERE fk_paciente = :fk AND principal = 1");
            $existe->execute(['fk' => $pacienteId]);
            if ($existe->fetchColumn()) {
                $this->db->prepare(
                    "UPDATE tbl_contato_emergencia SET nome = :nome, parentesco = :parentesco,
                            telefone = :telefone, observacao = :observacao
                      WHERE fk_paciente = :fk_paciente AND principal = 1"
                )->execute($ct);
            } else {
                $this->db->prepare(
                    "INSERT INTO tbl_contato_emergencia (fk_paciente, nome, parentesco, telefone, observacao, principal)
                     VALUES (:fk_paciente, :nome, :parentesco, :telefone, :observacao, 1)"
                )->execute($ct);
            }
        }

        $operadora = $d['convenio_operadora'] ?? '';
        if ($operadora !== '' && ($d['convenio_carteirinha'] ?? '') !== '') {
            $convenio = $this->db->prepare("SELECT id FROM tbl_convenio WHERE nome_operadora = :nome LIMIT 1");
            $convenio->execute(['nome' => $operadora]);
            $fkConvenio = $convenio->fetchColumn();
            if (!$fkConvenio) {
                $this->db->prepare(
                    "INSERT INTO tbl_convenio (nome_operadora, ativo) VALUES (:nome, 1)"
                )->execute(['nome' => $operadora]);
                $fkConvenio = $this->db->lastInsertId();
            }
            $this->db->prepare(
                "INSERT INTO tbl_paciente_convenio
                    (fk_paciente, fk_convenio, numero_carteirinha, validade, titular_nome, ativo)
                 VALUES (:fk_paciente, :fk_convenio, :numero, :validade, :titular, 1)
                 ON DUPLICATE KEY UPDATE
                    numero_carteirinha = VALUES(numero_carteirinha),
                    validade = VALUES(validade),
                    titular_nome = VALUES(titular_nome),
                    ativo = 1"
            )->execute([
                'fk_paciente' => $pacienteId,
                'fk_convenio' => $fkConvenio,
                'numero'      => $d['convenio_carteirinha'],
                'validade'    => !empty($d['convenio_validade']) ? $d['convenio_validade'] : null,
                'titular'     => null
            ]);
        }

        $anamnese = [
            'fk_paciente'     => $pacienteId,
            'alergias'        => $d['alergias'] ?? null,
            'doencas_previas' => $d['doencas_previas'] ?? null,
            'medicamentos_uso'=> $d['medicamentos_uso'] ?? null,
            'cirurgias'       => $d['cirurgias'] ?? null,
            'observacoes'     => $d['observacoes'] ?? null
        ];
        $this->db->prepare(
            "INSERT INTO tbl_anamnese (fk_paciente, alergias, doencas_previas, medicamentos_uso, cirurgias, observacoes)
             VALUES (:fk_paciente, :alergias, :doencas_previas, :medicamentos_uso, :cirurgias, :observacoes)
             ON DUPLICATE KEY UPDATE
                alergias = VALUES(alergias),
                doencas_previas = VALUES(doencas_previas),
                medicamentos_uso = VALUES(medicamentos_uso),
                cirurgias = VALUES(cirurgias),
                observacoes = VALUES(observacoes)"
        )->execute($anamnese);
    }

    /**
     * Updates a patient record and its normalized complementary data
     */
    public function atualizar($id, $dados) {
        $atual = $this->getById($id);
        if (!$atual) {
            return false;
        }
        $dados = array_merge($atual, $dados);
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET
                cpf = :cpf, rg = :rg, sexo = :sexo, estado_civil = :estado_civil,
                foto = :foto, data_nascimento = :data_nascimento
              WHERE id = :id"
        );
        $ok = $stmt->execute([
            'cpf'             => $dados['cpf'] ?? null,
            'rg'              => ($dados['rg'] ?? '') !== '' ? $dados['rg'] : null,
            'sexo'            => $dados['sexo'] ?? 'nao_informado',
            'estado_civil'    => $dados['estado_civil'] ?? 'nao_informado',
            'foto'            => ($dados['foto'] ?? '') !== '' ? $dados['foto'] : null,
            'data_nascimento' => $dados['data_nascimento'],
            'id'              => $id
        ]);
        if ($ok && !empty($atual['fk_usuario'])) {
            $this->sincronizarDadosComplementares($id, $atual['fk_usuario'], $dados);
        }
        return $ok;
    }

    /**
     * Fetches the patient photo
     */
    public function getFoto($pacienteId) {
        $stmt = $this->db->prepare("SELECT foto FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $pacienteId]);
        return $stmt->fetchColumn() ?: '';
    }

    /**
     * Creates a new patient with full registration,
     * persisting the complementary data into the normalized tables.
     */
    public function criar($dados) {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (fk_usuario, data_nascimento, cpf, rg, sexo, estado_civil, foto)
             VALUES (:fk_usuario, :data_nascimento, :cpf, :rg, :sexo, :estado_civil, :foto)"
        );
        $ok = $stmt->execute([
            'fk_usuario'      => $dados['fk_usuario'],
            'data_nascimento' => $dados['data_nascimento'],
            'cpf'             => ($dados['cpf'] ?? '') !== '' ? $dados['cpf'] : null,
            'rg'              => ($dados['rg'] ?? '') !== '' ? $dados['rg'] : null,
            'sexo'            => $dados['sexo'] ?? 'nao_informado',
            'estado_civil'    => $dados['estado_civil'] ?? 'nao_informado',
            'foto'            => ($dados['foto'] ?? '') !== '' ? $dados['foto'] : null
        ]);
        if ($ok) {
            $pacienteId = $this->db->lastInsertId();
            $this->sincronizarDadosComplementares($pacienteId, $dados['fk_usuario'], $dados);
        }
        return $ok;
    }

    /**
     * Fetches the patient's anamnesis (allergies, previous diseases, etc.)
     */
    public function getAnamnese($pacienteId) {
        $stmt = $this->db->prepare(
            "SELECT alergias, doencas_previas, medicamentos_uso, cirurgias, observacoes
               FROM tbl_anamnese WHERE fk_paciente = :fk LIMIT 1"
        );
        $stmt->execute(['fk' => $pacienteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
