<?php


class Profissional extends Model {
    protected $table = 'tbl_profissional'; 
    
    /**
     * Fetches professional ID by user ID
     */
    public function getIdByUsuario($usuarioId) {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE fk_usuario = :fk");
        $stmt->execute(['fk' => $usuarioId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['id'] ?? null;
    }
    
    /**
     * Fetches professional record by user ID
     */
    public function getByUsuario($usuarioId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE fk_usuario = :fk");
        $stmt->execute(['fk' => $usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Fetches professional data with user name
     */
    public function getDadosComNome($profissionalId) {
        $stmt = $this->db->prepare("SELECT u.nome AS nome_profissional, p.numero_registro 
                                     FROM tbl_usuario u
                                     JOIN {$this->table} p ON p.fk_usuario = u.id
                                     WHERE p.id = :id");
        $stmt->execute(['id' => $profissionalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Counts visits scheduled for today
     */
    public function contarVisitasHoje($profissionalId) {
        $stmt = $this->db->prepare("SELECT count(id) AS total_visitas
                                      FROM tbl_agendamento 
                                      WHERE fk_profissional = :fk 
                                        AND DATE(data_hora_inicio) = CURDATE()
                                        AND status = 'agendado'");
        $stmt->execute(['fk' => $profissionalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Counts visits completed today
     */
    public function contarVisitasConcluidasHoje($profissionalId) {
        $stmt = $this->db->prepare("SELECT count(id) AS visitas_concluidas
                                      FROM tbl_agendamento 
                                      WHERE fk_profissional = :fk 
                                        AND DATE(data_hora_inicio) = CURDATE()
                                        AND status = 'concluido'");
        $stmt->execute(['fk' => $profissionalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetches today's service queue
     */
    public function getFilaAtendimentosHoje($profissionalId) {
        $stmt = $this->db->prepare("SELECT a.id,
                                             DATE(a.data_hora_inicio) AS data_agendamento,
                                             TIME(a.data_hora_inicio) AS horario_inicio,
                                             TIME(a.data_hora_fim)    AS horario_fim,
                                             a.status,
                                             u.nome AS nome_paciente
                                      FROM tbl_agendamento a
                                      JOIN tbl_paciente p ON a.fk_paciente = p.id
                                      JOIN tbl_usuario u ON p.fk_usuario = u.id
                                      WHERE a.fk_profissional = :fk 
                                        AND DATE(a.data_hora_inicio) = CURDATE()
                                        AND a.status IN ('agendado', 'em_andamento')
                                      ORDER BY a.data_hora_inicio ASC");
        $stmt->execute(['fk' => $profissionalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Fetches pending triages forwarded to a professional (médico/enfermeiro)
     * The técnico de enfermagem analyzes and forwards each triage to the
     * appropriate professional via fk_profissional.
     */
    public function getTriagensPendentes($profissionalId) {
        $stmt = $this->db->prepare(
            "SELECT t.id, t.nome_solicitante, t.status, t.classificacao, t.criado_em,
                    t.justificativa_encaminhamento
               FROM tbl_triagem t
              WHERE t.status = 'pendente'
                AND t.fk_profissional = :prof
              ORDER BY t.criado_em DESC"
        );
        $stmt->execute(['prof' => $profissionalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Fetches the pending triages waiting for the técnico de enfermagem to
     * analyze and forward (i.e. triages that have no target professional yet).
     */
    public function getTriagensParaAnalise() {
        $stmt = $this->db->prepare(
            "SELECT t.id, t.nome_solicitante, t.queixa_principal,
                    t.descricao_sintomas, t.data_inicio_sintomas,
                    t.status, t.classificacao, t.criado_em,
                    t.fk_paciente,
                    t.ia_classificacao, t.ia_justificativa_classificacao,
                    t.ia_perfil_recomendado, t.ia_justificativa_perfil,
                    t.ia_profissional_sugerido, t.ia_modelo, t.ia_analisada_em,
                    u.nome AS nome_paciente
               FROM tbl_triagem t
               JOIN tbl_paciente p ON p.id = t.fk_paciente
               JOIN tbl_usuario u ON u.id = p.fk_usuario
              WHERE t.status = 'pendente'
                AND t.fk_profissional IS NULL
              ORDER BY t.criado_em DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Fetches the name of the professional suggested by the IA, when she picked one.
     * Used by the técnico de enfermagem to show the recommendation alongside the triage.
     */
    public function getSugeridoPelaIA($idProfissional) {
        if (!$idProfissional) {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT p.id AS id_profissional, u.nome AS nome_profissional, p.especialidade,
                    p.tempo_experiencia_anos
               FROM {$this->table} p
               JOIN tbl_usuario u ON u.id = p.fk_usuario
              WHERE p.id = :id"
        );
        $stmt->execute(['id' => (int) $idProfissional]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Lists active professionals eligible to receive a triage, ranked by how well
     * they match the case: specialty relevance first, then time of experience,
     * then current workload (whoever is closest to the daily limit is deprioritized).
     *
     * $termos receives the complaint text; it is matched against the specialty
     * after accent removal so that "dor no peito" hits "Cardiologia".
     */
    public function listarParaEncaminhamento($perfis, array $termos = []) {
        return $this->rankearParaTriagem($this->listarPorPerfis($perfis), $termos);
    }

    /**
     * Scores and sorts an already fetched list of professionals for a given case.
     * Split from listarParaEncaminhamento() so the queue of triages can reuse the
     * same base list and rank it once per complaint, without re-querying.
     */
    public function rankearParaTriagem(array $profissionais, array $termos = []) {
        $cargaHoje = $this->mapaCargaDiaria(array_column($profissionais, 'id_profissional'));

        $texto = mb_strtolower(implode(' ', array_filter($termos, 'strlen')));

        foreach ($profissionais as &$prof) {
            $prof['id_profissional'] = (int) $prof['id_profissional'];
            $prof['tempo_experiencia_anos'] = (int) ($prof['tempo_experiencia_anos'] ?? 0);
            $prof['carga_hoje'] = (int) ($cargaHoje[$prof['id_profissional']] ?? 0);
            $prof['limite_diario'] = (int) ($prof['limite_diario'] ?? 12);
            $prof['especialidade_correspondente'] = $this->especialidadeCorresponde($texto, $prof['especialidade'] ?? '');
            $prof['pontuacao'] = $this->calcularPontuacao($prof);
        }
        unset($prof);

        usort($profissionais, static function ($a, $b) {
            if ($a['pontuacao'] === $b['pontuacao']) {
                return strcasecmp($a['nome_profissional'], $b['nome_profissional']);
            }
            return $a['pontuacao'] <=> $b['pontuacao'];
        });

        return $profissionais;
    }

    /**
     * Counts today's visits per professional, used to spread the load.
     */
    private function mapaCargaDiaria(array $idsProfissionais) {
        if (empty($idsProfissionais)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($idsProfissionais) as $i => $id) {
            $placeholders[] = ":id_{$i}";
            $params["id_{$i}"] = (int) $id;
        }

        $stmt = $this->db->prepare(
            "SELECT fk_profissional, COUNT(*) AS total
               FROM tbl_agendamento
              WHERE fk_profissional IN (" . implode(', ', $placeholders) . ")
                AND DATE(data_hora_inicio) = CURDATE()
                AND status IN ('agendado', 'em_andamento', 'pendente')
              GROUP BY fk_profissional"
        );
        $stmt->execute($params);

        $mapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $mapa[(int) $linha['fk_profissional']] = (int) $linha['total'];
        }
        return $mapa;
    }

    /**
     * Tells whether the specialty is relevant to the complaint text.
     * Returns false when the text is too short to judge (the case then relies
     * on the time of experience alone).
     */
    private function especialidadeCorresponde(string $texto, string $especialidade) {
        $palavrasChave = $this->tokenizar($especialidade);
        if (empty($palavrasChave) || strlen($texto) < 12) {
            return false;
        }
        foreach ($palavrasChave as $palavra) {
            if (mb_strlen($palavra) >= 4 && strpos($texto, $palavra) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Splits a specialty into comparable words (accent-free, stopwords removed).
     */
    private function tokenizar(string $texto) {
        $normalizado = $this->semAcento(mb_strtolower($texto));
        $stopwords = ['de', 'da', 'do', 'e', 'em', 'a', 'o', 'as', 'os', 'geral'];
        $tokens = preg_split('/[^a-z0-9]+/', $normalizado, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_filter($tokens, static function ($t) use ($stopwords) {
            return !in_array($t, $stopwords, true);
        }));
    }

    private function semAcento(string $texto) {
        $mapa = [
            'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n'
        ];
        return strtr($texto, $mapa);
    }

    /**
     * Score used only to order/preselect professionals for the técnico.
     * Specialty match weighs the most, then experience (capped at 20 years),
     * and finally the remaining capacity for the day.
     */
    private function calcularPontuacao(array $prof) {
        $pontos = 0;

        if (!empty($prof['especialidade_correspondente'])) {
            $pontos += 50;
        }

        $pontos += min((int) $prof['tempo_experiencia_anos'], 20) * 2;

        $livre = (int) $prof['limite_diario'] - (int) $prof['carga_hoje'];
        if ($livre < 0) {
            $livre = 0;
        }
        $pontos += (int) round(($livre / max(1, (int) $prof['limite_diario'])) * 10);

        return (int) $pontos;
    }

    /**
     * Counts the pending triages waiting for analysis (técnico de enfermagem)
     */
    public function contarTriagensParaAnalise() {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_triagem
              WHERE status = 'pendente' AND fk_profissional IS NULL"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Checks whether the triage is still awaiting analysis by the técnico
     * (status 'pendente' and not yet forwarded to any professional).
     */
    public function triagemPodeSerAnalisada($idTriagem) {
        $stmt = $this->db->prepare(
            "SELECT id FROM tbl_triagem
              WHERE id = :id AND status = 'pendente' AND fk_profissional IS NULL"
        );
        $stmt->execute(['id' => $idTriagem]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Forwards (encaminha) a triage to the target professional after analysis.
     * Stores the classification given by the technician, who analyzed it, and
     * the optional justification for the encaminhamento.
     */
    public function encaminharTriagem($idTriagem, $fkProfissional, $fkAnalisadoPor, $classificacao, $justificativa = null) {
        $stmt = $this->db->prepare(
            "UPDATE tbl_triagem
                SET fk_profissional = :prof,
                    fk_analisado_por = :analista,
                    classificacao = :classificacao,
                    justificativa_encaminhamento = :justificativa
              WHERE id = :id AND status = 'pendente' AND fk_profissional IS NULL"
        );
        $stmt->execute([
            'prof' => (int) $fkProfissional,
            'analista' => $fkAnalisadoPor ? (int) $fkAnalisadoPor : null,
            'classificacao' => $classificacao,
            'justificativa' => $justificativa,
            'id' => (int) $idTriagem
        ]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Fetches pending schedulings for approval
     * Includes the IA triage/encaminhamento analysis and the patient's anamnesis data.
     */
    public function getAgendamentosPendentes($profissionalId) {
        $stmt = $this->db->prepare("SELECT a.id,
                                             DATE(a.data_hora_inicio) AS data_agendamento,
                                             TIME(a.data_hora_inicio) AS horario_inicio,
                                             TIME(a.data_hora_fim)    AS horario_fim,
                                             a.status,
                                             a.fk_profissional,
                                             a.classificacao_ia, a.justificativa_classificacao_ia,
                                             a.profissional_recomendado_ia, a.justificativa_recomendacao_ia,
                                             a.classificacao_final, a.modelo_ia, a.data_analise_ia,
                                             u.nome AS nome_paciente, p.id AS id_paciente,
                                             an.alergias, an.doencas_previas, an.medicamentos_uso, an.cirurgias,
                                             t.id AS id_triagem, t.nome_solicitante,
                                             t.classificacao AS triagem_classificacao, t.status AS triagem_status
                                      FROM tbl_agendamento a
                                      JOIN tbl_paciente p ON a.fk_paciente = p.id
                                      JOIN tbl_usuario u ON p.fk_usuario = u.id
                                      LEFT JOIN tbl_anamnese an ON an.fk_paciente = p.id
                                       LEFT JOIN tbl_triagem t ON t.id = COALESCE(a.fk_triagem, (
                                           SELECT t2.id FROM tbl_triagem t2
                                            WHERE t2.fk_paciente = a.fk_paciente AND t2.status = 'pendente'
                                            ORDER BY t2.criado_em DESC LIMIT 1
                                       ))
                                      WHERE a.fk_profissional = :fk 
                                        AND DATE(a.data_hora_inicio) >= CURDATE()
                                        AND a.status = 'pendente'
                                      ORDER BY a.data_hora_inicio ASC");
        $stmt->execute(['fk' => $profissionalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lists active professionals filtered by user profile (e.g. enfermeiro, tecnico_enfermagem)
     * Used to assign/reassign the responsibility of a scheduling.
     */
    public function listarPorPerfis($perfis) {
        $placeholders = [];
        $params = [];
        foreach (array_values($perfis) as $i => $perfil) {
            $placeholders[] = ":perfil_{$i}";
            $params["perfil_{$i}"] = $perfil;
        }
        $in = implode(', ', $placeholders);
        $stmt = $this->db->prepare(
            "SELECT p.id AS id_profissional, u.nome AS nome_profissional,
                    (SELECT r.slug FROM tbl_user_role ur JOIN tbl_roles r ON r.id = ur.fk_role
                      WHERE ur.fk_usuario = u.id
                      ORDER BY FIELD(r.slug, 'medico', 'enfermeiro', 'tecnico_enfermagem', 'admin', 'paciente') ASC
                      LIMIT 1) AS perfil,
                     p.especialidade,
                     p.tempo_experiencia_anos,
                     p.numero_registro,
                     p.foto,
                     p.limite_diario
               FROM tbl_usuario u
               JOIN {$this->table} p ON p.fk_usuario = u.id
              WHERE u.status = 'ativo'
                AND EXISTS (SELECT 1
                              FROM tbl_user_role ur
                              JOIN tbl_roles r ON r.id = ur.fk_role
                             WHERE ur.fk_usuario = u.id AND r.slug IN ({$in}))
              ORDER BY u.nome ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetches future schedulings
     */
    public function getAgendamentosFuturos($profissionalId) {
        $stmt = $this->db->prepare("SELECT a.id AS agendamento_id,
                                             DATE(a.data_hora_inicio) AS data_agendamento,
                                             TIME(a.data_hora_inicio) AS horario_inicio,
                                             TIME(a.data_hora_fim)    AS horario_fim,
                                             a.status,
                                             u.nome AS nome_paciente, p.especialidade
                                      FROM tbl_agendamento a
                                      JOIN {$this->table} p ON a.fk_profissional = p.id
                                      JOIN tbl_paciente pac ON a.fk_paciente = pac.id
                                      JOIN tbl_usuario u ON pac.fk_usuario = u.id
                                      WHERE a.fk_profissional = :fk 
                                        AND DATE(a.data_hora_inicio) >= CURDATE()
                                        AND a.status IN ('agendado', 'pendente', 'em_andamento')
                                      ORDER BY a.data_hora_inicio ASC 
                                      LIMIT 10");
        $stmt->execute(['fk' => $profissionalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches past schedulings
     */
    public function getAgendamentosPassados($profissionalId) {
        $stmt = $this->db->prepare("SELECT a.id AS agendamento_id,
                                             DATE(a.data_hora_inicio) AS data_agendamento,
                                             TIME(a.data_hora_inicio) AS horario_inicio,
                                             TIME(a.data_hora_fim)    AS horario_fim,
                                             a.status,
                                             u.nome AS nome_paciente, p.especialidade
                                      FROM tbl_agendamento a
                                      JOIN {$this->table} p ON a.fk_profissional = p.id
                                      JOIN tbl_paciente pac ON a.fk_paciente = pac.id
                                      JOIN tbl_usuario u ON pac.fk_usuario = u.id
                                      WHERE a.fk_profissional = :fk 
                                        AND (DATE(a.data_hora_inicio) < CURDATE()
                                             OR (DATE(a.data_hora_inicio) = CURDATE()
                                                 AND a.status IN ('concluido', 'cancelado', 'ausente')))
                                      ORDER BY a.data_hora_inicio DESC 
                                      LIMIT 10");
        $stmt->execute(['fk' => $profissionalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches completed visits for the professional with medical record
     */
    public function getVisitasConcluidas($profissionalId) {
        $stmt = $this->db->prepare("SELECT a.id,
                                             DATE(a.data_hora_inicio) AS data_agendamento,
                                             TIME(a.data_hora_inicio) AS horario_inicio,
                                             TIME(a.data_hora_fim)    AS horario_fim,
                                             a.status,
                                             u.nome AS nome_paciente, p.especialidade,
                                             pr.diagnostico_final, pr.conduta_medica,
                                             pr.queixa_principal, pr.historico_medico
                                      FROM tbl_agendamento a
                                      JOIN {$this->table} p ON a.fk_profissional = p.id
                                      JOIN tbl_paciente pac ON a.fk_paciente = pac.id
                                      JOIN tbl_usuario u ON pac.fk_usuario = u.id
                                      LEFT JOIN tbl_atendimento ta ON ta.fk_agendamento = a.id
                                      LEFT JOIN tbl_prontuario pr ON pr.fk_atendimento = ta.id
                                      WHERE a.fk_profissional = :fk 
                                        AND a.status = 'concluido'
                                      ORDER BY a.data_hora_inicio DESC");
        $stmt->execute(['fk' => $profissionalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lists active professionals for selection
     */
    public function listarAtivos() {
        $stmt = $this->db->prepare("SELECT p.id AS id_profissional, u.nome AS nome_profissional, p.especialidade,
                                           p.numero_registro, p.tempo_experiencia_anos, p.foto
                                     FROM tbl_usuario u
                                     JOIN {$this->table} p ON p.fk_usuario = u.id
                                     WHERE u.status = 'ativo' 
                                     ORDER BY p.especialidade ASC, u.nome ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Checks if a triage was forwarded to the professional (médico/enfermeiro)
     */
    public function triagemPertenceAoProfissional($idTriagem, $idProfissional) {
        $stmt = $this->db->prepare(
            "SELECT t.id
               FROM tbl_triagem t
              WHERE t.id = :id AND t.status = 'pendente'
                AND t.fk_profissional = :prof
              ORDER BY t.criado_em DESC LIMIT 1"
        );
        $stmt->execute(['id' => $idTriagem, 'prof' => $idProfissional]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Counts pending triages forwarded to the professional
     */
    public function contarTriagensPendentes($idProfissional) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_triagem
               WHERE fk_profissional = :prof AND status = 'pendente'"
        );
        $stmt->execute(['prof' => $idProfissional]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Counts pending triages forwarded to the nursing professional
     */
    public function contarTriagensPendentesEnfermagem($idProfissional) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM tbl_triagem
               WHERE fk_profissional = :prof AND status = 'pendente'"
        );
        $stmt->execute(['prof' => $idProfissional]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Lists the pending triages forwarded to the nursing professional
     */
    public function getTriagensPendentesEnfermagem($profissionalId) {
        $stmt = $this->db->prepare(
            "SELECT t.id, t.nome_solicitante AS paciente_nome,
                    t.classificacao, t.status, t.criado_em,
                    t.justificativa_encaminhamento,
                    a.id AS id_agendamento,
                    DATE(a.data_hora_inicio) AS data_agendamento,
                    TIME(a.data_hora_inicio) AS horario_inicio,
                    TIME(a.data_hora_fim)    AS horario_fim,
                    a.status AS agendamento_status
               FROM tbl_triagem t
               LEFT JOIN tbl_agendamento a ON a.fk_triagem = t.id
              WHERE t.status = 'pendente'
                AND t.fk_profissional = :prof
              ORDER BY t.criado_em DESC"
        );
        $stmt->execute(['prof' => $profissionalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Creates a new professional
     */
    public function criar($dados) {
        $experiencia = isset($dados['tempo_experiencia_anos'])
            ? max(0, min(70, (int) $dados['tempo_experiencia_anos']))
            : 0;

        $stmt = $this->db->prepare("INSERT INTO {$this->table} (fk_usuario, especialidade, numero_registro, tempo_experiencia_anos, foto)
                                    VALUES (:fk_usuario, :especialidade, :numero_registro, :experiencia, :foto)");
        return $stmt->execute([
            'fk_usuario' => $dados['fk_usuario'],
            'especialidade' => $dados['especialidade'],
            'numero_registro' => $dados['numero_registro'],
            'experiencia' => $experiencia,
            'foto' => ($dados['foto'] ?? '') !== '' ? $dados['foto'] : null
        ]);
    }

    /**
     * Updates professional data
     */
    public function atualizar($profissionalId, $dados) {
        $campos = [];
        $params = ['id' => $profissionalId];

        if (array_key_exists('especialidade', $dados)) {
            $campos[] = 'especialidade = :especialidade';
            $params['especialidade'] = $dados['especialidade'];
        }
        if (array_key_exists('numero_registro', $dados)) {
            $campos[] = 'numero_registro = :numero_registro';
            $params['numero_registro'] = $dados['numero_registro'];
        }
        if (array_key_exists('tempo_experiencia_anos', $dados)) {
            $campos[] = 'tempo_experiencia_anos = :experiencia';
            $params['experiencia'] = max(0, min(70, (int) $dados['tempo_experiencia_anos']));
        }
        if (array_key_exists('foto', $dados)) {
            $campos[] = 'foto = :foto';
            $params['foto'] = ($dados['foto'] ?? '') !== '' ? $dados['foto'] : null;
        }

        if (!$campos) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $campos) . " WHERE id = :id"
        );
        return $stmt->execute($params);
    }
}
