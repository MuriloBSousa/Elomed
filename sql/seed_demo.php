<?php
/**
 * EloMed - seed de demonstracao.
 *
 * Popula o banco com pacientes, anamneses, triagens, agendamentos (hoje,
 * futuro e passado), atendimentos, prontuarios (por atendimento e por secao),
 * lembretes, feedback, uso de IA e diagnosticos - para que o painel do medico
 * e o prontuario do paciente apareçam "cheios" ao navegar.
 *
 * Idempotente: limpa os dados transacionais antes de recriar.
 * Rodar:  php sql/seed_demo.php
 */

require __DIR__ . '/../app/config/Env.php';
require __DIR__ . '/../config/Database.php';

$db = Database::getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$hoje = date('Y-m-d');
function dia($offset) {
    global $hoje;
    return date('Y-m-d', strtotime($hoje . ($offset >= 0 ? " +$offset day" : " $offset day")));
}
function dt($data, $hora) { return $data . ' ' . $hora . ':00'; }

/** Gera um CPF valido (com digitos verificadores) a partir de 9 digitos. */
function cpfDe(string $base9): string {
    $cpf = $base9;
    for ($t = 9; $t < 11; $t++) {
        $s = 0;
        for ($i = 0; $i < $t; $i++) {
            $s += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $cpf .= (string) ((($s * 10) % 11) % 10);
    }
    return $cpf;
}

echo "== Limpando dados transacionais ==\n";
foreach ([
    'tbl_prontuario_visualizacao', 'tbl_resposta_protocolo', 'tbl_feedback',
    'tbl_prontuario', 'tbl_prontuario_registro', 'tbl_lembrete',
    'tbl_atendimento', 'tbl_diagnostico', 'tbl_agendamento', 'tbl_triagem',
    'tbl_uso_ia',
] as $t) {
    $db->exec("DELETE FROM $t");
    echo "  - $t\n";
}

// ------------------------------------------------------------------
// 1) Profissional para o usuario 32 (Richard) - medico sem perfil
// ------------------------------------------------------------------
$prof32 = $db->query("SELECT id FROM tbl_profissional WHERE fk_usuario = 32")->fetchColumn();
if (!$prof32) {
    $db->prepare(
        "INSERT INTO tbl_profissional
            (fk_usuario, especialidade, numero_registro, tempo_experiencia_anos,
             limite_diario, pausa_inicio, pausa_fim)
         VALUES (32, 'Clinica Medica', 'CRM/DF 165432', 12, 12, '12:00:00', '13:00:00')"
    )->execute();
    $prof32 = (int) $db->lastInsertId();
    echo "== Profissional criado para user 32 -> id $prof32\n";
}
$prof32 = (int) $prof32;

// Experiencia e pausa dos profissionais existentes (ranking da triagem)
$db->exec("UPDATE tbl_profissional SET tempo_experiencia_anos=18, pausa_inicio='12:00:00', pausa_fim='13:00:00' WHERE id=1");
$db->exec("UPDATE tbl_profissional SET tempo_experiencia_anos=15, pausa_inicio='12:30:00', pausa_fim='13:30:00' WHERE id=2");
$db->exec("UPDATE tbl_profissional SET tempo_experiencia_anos=10 WHERE id=3");
$db->exec("UPDATE tbl_profissional SET tempo_experiencia_anos=8  WHERE id=4");
$db->exec("UPDATE tbl_profissional SET tempo_experiencia_anos=9  WHERE id BETWEEN 5 AND 15");

// ------------------------------------------------------------------
// 2) Dados mestres dos pacientes
// ------------------------------------------------------------------
$pacientes = [
    // paciente_id => [users_id, cpf_base9, rg, sexo, estado_civil, nascimento]
    1 => [16, '529982247', '1234567', 'masculino', 'casado',           '1985-04-12'],
    2 => [17, '111444777', '2345678', 'feminino',  'solteiro',         '1990-08-23'],
    3 => [18, '390533447', '3456789', 'masculino', 'divorciado',       '1978-12-02'],
    4 => [19, '453178287', '4567890', 'feminino',  'uniao_estavel',    '1995-01-15'],
    5 => [20, '356498877', '5678901', 'masculino', 'viuvo',            '1963-06-30'],
    6 => [33, '786876876', '6789012', 'masculino', 'nao_informado',    '1990-05-10'],
];

$ends = [
    1 => ['SQN 202 Bloco A', '302', 'Asa Norte',  '70842010', 'Brasilia', 'DF'],
    2 => ['Rua das Palmeiras', '145', 'Jardim America', '74255070', 'Goiania', 'GO'],
    3 => ['Av. Paulista', '1500', 'Bela Vista', '01310200', 'Sao Paulo', 'SP'],
    4 => ['Rua do Sol', '88', 'Centro', '65010000', 'Sao Luis', 'MA'],
    5 => ['Rua das Acacias', '410', 'Boa Viagem', '51020030', 'Recife', 'PE'],
    6 => ['SQS 308 Bloco C', 'apto 501', 'Asa Sul', '70355030', 'Brasilia', 'DF'],
];
$tels = [1 => '61999990001', 2 => '62988887777', 3 => '11977776666', 4 => '98966665555', 5 => '81955554444', 6 => '61999990001'];
$contatos = [
    1 => ['Maria Alencar', 'Esposa', '61999887766'],
    2 => ['Joao Regina', 'Pai', '62998887711'],
    3 => ['Livia Ramos', 'Irma', '11988887722'],
    4 => ['Marcos Lopes', 'Esposo', '98998887733'],
    5 => ['Ana Pires', 'Filha', '81998887744'],
    6 => ['Contato Emergencia', 'Amigo', '61997776655'],
];

foreach ($pacientes as $pid => [$uid, $cpfBase, $rg, $sexo, $estado, $nasc]) {
    $cpf = cpfDe($cpfBase);
    $db->prepare(
        "UPDATE tbl_paciente
            SET cpf = :cpf, rg = :rg, sexo = :sexo, estado_civil = :estado,
                data_nascimento = :nasc
          WHERE id = :id"
    )->execute(['cpf' => $cpf, 'rg' => $rg, 'sexo' => $sexo, 'estado' => $estado, 'nasc' => $nasc, 'id' => $pid]);

    if (!$db->query("SELECT id FROM tbl_endereco WHERE fk_usuario = $uid LIMIT 1")->fetchColumn()) {
        [$rua, $num, $bairro, $cep, $cidade, $uf] = $ends[$pid];
        $db->prepare(
            "INSERT INTO tbl_endereco (fk_usuario, logradouro, numero, bairro, cep, cidade, uf, principal)
             VALUES (:u, :rua, :num, :bairro, :cep, :cidade, :uf, 1)"
        )->execute(['u' => $uid, 'rua' => $rua, 'num' => $num, 'bairro' => $bairro, 'cep' => $cep, 'cidade' => $cidade, 'uf' => $uf]);
    }
    if (!$db->query("SELECT id FROM tbl_telefone WHERE fk_usuario = $uid LIMIT 1")->fetchColumn()) {
        $db->prepare("INSERT INTO tbl_telefone (fk_usuario, telefone, tipo) VALUES (:u, :t, 'principal')")
           ->execute(['u' => $uid, 't' => $tels[$pid]]);
    }
    if (!$db->query("SELECT id FROM tbl_contato_emergencia WHERE fk_paciente = $pid LIMIT 1")->fetchColumn()) {
        [$nome, $par, $tel] = $contatos[$pid];
        $db->prepare(
            "INSERT INTO tbl_contato_emergencia (fk_paciente, nome, parentesco, telefone, principal)
             VALUES (:p, :n, :par, :t, 1)"
        )->execute(['p' => $pid, 'n' => $nome, 'par' => $par, 't' => $tel]);
    }
}

// Anamnese (upsert por paciente)
$anamneses = [
    1 => ['Alergia a penicilina', 'Hipertensao arterial', 'Losartana 50mg', 'Apendicectomia (2010)'],
    2 => ['Nega alergias', 'Nega doencas cronicas', 'Nega uso continuo', 'Nega cirurgias'],
    3 => ['Alergia a dipirona', 'Diabetes tipo 2', 'Metformina 850mg', 'Colecistectomia (2015)'],
    4 => ['Nega alergias', 'Asma leve', 'Salbutamol SOS', 'Nega cirurgias'],
    5 => ['Alergia a AAS', 'Hipertensao e dislipidemia', 'Sinvastatina 20mg', 'Revascularizacao (2018)'],
    6 => ['Nega alergias', 'Nega doencas previas', 'Nega medicamentos', 'Nega cirurgias'],
];
foreach ($anamneses as $pid => [$al, $dp, $med, $cir]) {
    $tem = $db->query("SELECT id FROM tbl_anamnese WHERE fk_paciente = $pid LIMIT 1")->fetchColumn();
    if ($tem) {
        $db->prepare("UPDATE tbl_anamnese SET alergias=:a, doencas_previas=:d, medicamentos_uso=:m, cirurgias=:c WHERE id=:id")
           ->execute(['a' => $al, 'd' => $dp, 'm' => $med, 'c' => $cir, 'id' => $tem]);
    } else {
        $db->prepare("INSERT INTO tbl_anamnese (fk_paciente, alergias, doencas_previas, medicamentos_uso, cirurgias) VALUES (:p,:a,:d,:m,:c)")
           ->execute(['p' => $pid, 'a' => $al, 'd' => $dp, 'm' => $med, 'c' => $cir]);
    }
}

// Convenios
$conv = [1 => [1, '001122334455', '2027-12-31'], 3 => [2, '556677889900', '2026-12-31'], 5 => [3, '998877665544', '2027-06-30']];
foreach ($conv as $pid => [$cid, $cart, $val]) {
    if (!$db->query("SELECT id FROM tbl_paciente_convenio WHERE fk_paciente = $pid LIMIT 1")->fetchColumn()) {
        $db->prepare("INSERT INTO tbl_paciente_convenio (fk_paciente, fk_convenio, numero_carteirinha, validade, ativo) VALUES (:p,:c,:n,:v,1)")
           ->execute(['p' => $pid, 'c' => $cid, 'n' => $cart, 'v' => $val]);
    }
}
echo "== Dados mestres dos pacientes atualizados\n";

// ------------------------------------------------------------------
// 3) Triagens + respostas
// ------------------------------------------------------------------
function inserirTriagem($db, array $t): int {
    $db->prepare(
        "INSERT INTO tbl_triagem
            (fk_paciente, fk_profissional, fk_analisado_por, nome_solicitante, status,
             classificacao, ia_classificacao, ia_justificativa_classificacao,
             ia_perfil_recomendado, ia_justificativa_perfil, ia_profissional_sugerido,
             ia_modelo, ia_analisada_em, queixa_principal, descricao_sintomas,
             data_inicio_sintomas, justificativa_reprovacao, justificativa_encaminhamento,
             data_aprovacao)
         VALUES
            (:fk_paciente, :fk_profissional, :fk_analisado_por, :nome_solicitante, :status,
             :classificacao, :ia_classificacao, :ia_just_class,
             :ia_perfil, :ia_just_perfil, :ia_prof_sug,
             :ia_modelo, :ia_analisada_em, :queixa, :descricao,
             :inicio_sintomas, :just_reprovacao, :just_encaminhamento,
             :data_aprovacao)"
    )->execute($t);
    return (int) $db->lastInsertId();
}

$t1 = inserirTriagem($db, [
    'fk_paciente' => 1, 'fk_profissional' => null, 'fk_analisado_por' => null,
    'nome_solicitante' => 'Roberto Alencar', 'status' => 'pendente',
    'classificacao' => null,
    'ia_classificacao' => 'amarelo',
    'ia_just_class' => 'Dor toracica aos esforcos com fatores de risco cardiovascular.',
    'ia_perfil' => 'medico',
    'ia_just_perfil' => 'Caso com suspeita de origem cardiaca; encaminhar a medico.',
    'ia_prof_sug' => 1, 'ia_modelo' => 'openai/gpt-oss-20b', 'ia_analisada_em' => dt(dia(0), '08:10'),
    'queixa' => 'Dor no peito ao esforco', 'descricao' => 'Dor retroesternal ao subir escadas, melhora com repouso. Iniciou ha 3 dias.',
    'inicio_sintomas' => dia(-3), 'just_reprovacao' => null,
    'just_encaminhamento' => null, 'data_aprovacao' => null,
]);

$t2 = inserirTriagem($db, [
    'fk_paciente' => 2, 'fk_profissional' => $prof32, 'fk_analisado_por' => 10,
    'nome_solicitante' => 'Sandra Regina', 'status' => 'pendente', 'classificacao' => 'amarelo',
    'ia_classificacao' => 'amarelo', 'ia_just_class' => 'Quadro febril com mialgia e cefaleia.',
    'ia_perfil' => 'medico', 'ia_just_perfil' => 'Suspeita de arbovirose; avaliacao medica.',
    'ia_prof_sug' => $prof32, 'ia_modelo' => 'openai/gpt-oss-20b', 'ia_analisada_em' => dt(dia(-1), '15:20'),
    'queixa' => 'Febre e dor no corpo', 'descricao' => 'Febre ha 4 dias, dor muscular, cefaleia e manchas na pele.',
    'inicio_sintomas' => dia(-4), 'just_reprovacao' => null,
    'just_encaminhamento' => 'Encaminhada ao Dr. Richard para investigacao de dengue.', 'data_aprovacao' => null,
]);

$t3 = inserirTriagem($db, [
    'fk_paciente' => 3, 'fk_profissional' => 1, 'fk_analisado_por' => 11,
    'nome_solicitante' => 'Thiago Ramos', 'status' => 'pendente', 'classificacao' => 'verde',
    'ia_classificacao' => 'verde', 'ia_just_class' => 'Lesao dermatologica sem sinais de gravidade.',
    'ia_perfil' => 'medico', 'ia_just_perfil' => 'Avaliacao dermatologica eletiva.',
    'ia_prof_sug' => 3, 'ia_modelo' => 'openai/gpt-oss-20b', 'ia_analisada_em' => dt(dia(-2), '09:00'),
    'queixa' => 'Mancha na perna', 'descricao' => 'Mancha pigmentada em perna direita, indolor, ha 2 meses.',
    'inicio_sintomas' => dia(-60), 'just_reprovacao' => null,
    'just_encaminhamento' => 'Encaminhada para avaliacao dermatologica.', 'data_aprovacao' => null,
]);

$t4 = inserirTriagem($db, [
    'fk_paciente' => 4, 'fk_profissional' => 2, 'fk_analisado_por' => 12,
    'nome_solicitante' => 'Vanessa Lopes', 'status' => 'aprovada', 'classificacao' => 'amarelo',
    'ia_classificacao' => 'amarelo', 'ia_just_class' => 'Crise asmatica moderada.', 'ia_perfil' => 'medico',
    'ia_just_perfil' => 'Necessita avaliacao medica.', 'ia_prof_sug' => 2,
    'ia_modelo' => 'openai/gpt-oss-20b', 'ia_analisada_em' => dt(dia(-3), '10:00'),
    'queixa' => 'Falta de ar', 'descricao' => 'Crises de tosse e chiado ha 2 dias.', 'inicio_sintomas' => dia(-2),
    'just_reprovacao' => null, 'just_encaminhamento' => null, 'data_aprovacao' => dt(dia(-3), '11:00'),
]);

$t5 = inserirTriagem($db, [
    'fk_paciente' => 5, 'fk_profissional' => 3, 'fk_analisado_por' => 13,
    'nome_solicitante' => 'William Pires', 'status' => 'recusada', 'classificacao' => 'verde',
    'ia_classificacao' => 'verde', 'ia_just_class' => 'Queixa leve, sem urgencia.', 'ia_perfil' => 'enfermeiro',
    'ia_just_perfil' => 'Pode ser acompanhado pela enfermagem.', 'ia_prof_sug' => 5,
    'ia_modelo' => 'openai/gpt-oss-20b', 'ia_analisada_em' => dt(dia(-5), '08:30'),
    'queixa' => 'Orientacao sobre dieta', 'descricao' => 'Busca orientacao nutricional.', 'inicio_sintomas' => dia(-1),
    'just_reprovacao' => 'Paciente foi orientado a procurar a unidade basica.', 'just_encaminhamento' => null,
    'data_aprovacao' => null,
]);

$t6 = inserirTriagem($db, [
    'fk_paciente' => 6, 'fk_profissional' => null, 'fk_analisado_por' => null,
    'nome_solicitante' => 'Paciente Teste WhatsApp', 'status' => 'pendente', 'classificacao' => null,
    'ia_classificacao' => 'verde', 'ia_just_class' => 'Quadro de baixa complexidade.', 'ia_perfil' => 'medico',
    'ia_just_perfil' => 'Avaliacao clinica geral.', 'ia_prof_sug' => $prof32,
    'ia_modelo' => 'openai/gpt-oss-20b', 'ia_analisada_em' => dt(dia(0), '07:45'),
    'queixa' => 'Dor de garganta', 'descricao' => 'Dor de garganta ha 2 dias, sem febre.',
    'inicio_sintomas' => dia(-2), 'just_reprovacao' => null, 'just_encaminhamento' => null, 'data_aprovacao' => null,
]);

// Respostas de protocolo (triagem t1 -> protocolo 1; t2 -> protocolo 2)
$resp = [
    [$t1, 1, 'Dor retroesternal ao subir escadas, sete de 6/10, melhora com repouso.'],
    [$t1, 2, 'Ha 3 dias'],
    [$t1, 3, 'Intermitentes'],
    [$t1, 4, 'Nao fez tratamento ainda.'],
    [$t1, 5, 'Pai com historico de infarto.'],
    [$t2, 6, '4 dias'],
    [$t2, 7, 'Sim'],
    [$t2, 8, 'Sim'],
    [$t2, 9, 'Sim'],
    [$t2, 10, 'Nao'],
    [$t2, 11, '39.2'],
];
// tbl_resposta_protocolo.resposta tem CHECK (json_valid(resposta)):
// o valor precisa ser um JSON valido (ex.: "39.2" ou "texto").
$stResp = $db->prepare("INSERT INTO tbl_resposta_protocolo (fk_triagem, fk_pergunta, resposta) VALUES (?,?,?)");
foreach ($resp as [$tri, $pergunta, $valor]) {
    $stResp->execute([$tri, $pergunta, json_encode($valor, JSON_UNESCAPED_UNICODE)]);
}
echo "== Triagens e respostas criadas\n";

// ------------------------------------------------------------------
// 4) Agendamentos / atendimentos / prontuarios
// ------------------------------------------------------------------
$profUserId = [];
foreach ($db->query("SELECT id, fk_usuario FROM tbl_profissional") as $r) {
    $profUserId[(int) $r['id']] = (int) $r['fk_usuario'];
}

$INS_AG = $db->prepare(
    "INSERT INTO tbl_agendamento
        (fk_paciente, fk_profissional, data_hora_inicio, data_hora_fim, status,
         duracao_minutos, fk_triagem, justificativa_cancelamento,
         classificacao_ia, justificativa_classificacao_ia, classificacao_final)
     VALUES (:pac, :prof, :ini, :fim, :st, :dur, :tri, :just, :cia, :jcia, :cf)"
);
$INS_AT = $db->prepare(
    "INSERT INTO tbl_atendimento (fk_agendamento, fk_profissional, horario_inicio, horario_fim, status, observacoes)
     VALUES (:ag, :prof, :ini, :fim, :st, :obs)"
);
$INS_PR = $db->prepare(
    "INSERT INTO tbl_prontuario
        (fk_atendimento, queixa_principal, historico_medico, sinais_vitais, analise_ia,
         diagnostico_final, conduta_medica)
     VALUES (:at, :q, :h, :sv, :ia, :d, :c)"
);
$INS_LEM = $db->prepare(
    "INSERT INTO tbl_lembrete (fk_agendamento, mensagem, status, enviado_em)
     VALUES (:ag, :msg, 'enviado', NOW())"
);
$INS_FB = $db->prepare(
    "INSERT INTO tbl_feedback (fk_atendimento, fk_profissional, fk_paciente, fk_usuario, nota, comentario)
     VALUES (:at, :prof, :pac, :user, :nota, :com)"
);
$INS_DX = $db->prepare(
    "INSERT INTO tbl_diagnostico
        (fk_paciente, fk_medico, queixa_principal, sintomas, historico_medico,
         sinais_vitais, diagnostico_final, conduta_medica)
     VALUES (:pac, :med, :q, :s, :h, :sv, :d, :c)"
);

$criados = 0;

/**
 * @param array $cfg pac, prof, inicio('Y-m-d H:i'), dur(min), st, tri, just
 * @param array|null $clinica queixa, historico, sinais_vitais, analise_ia, diag, conduta, nota, comentario, sintomas
 */
function criarVisita($db, $cfg, $clinica = null, $criarLembrete = true) {
    global $INS_AG, $INS_AT, $INS_PR, $INS_LEM, $INS_FB, $INS_DX, $profUserId, $criados;

    $ini = str_replace('T', ' ', $cfg['inicio']);
    $ini = date('Y-m-d H:i:s', strtotime($ini));
    $fim = date('Y-m-d H:i:s', strtotime($ini) + $cfg['dur'] * 60);

    $INS_AG->execute([
        'pac' => $cfg['pac'], 'prof' => $cfg['prof'], 'ini' => $ini, 'fim' => $fim,
        'st' => $cfg['st'], 'dur' => $cfg['dur'], 'tri' => $cfg['tri'] ?? null,
        'just' => $cfg['just'] ?? null, 'cia' => $cfg['cia'] ?? null, 'jcia' => $cfg['jcia'] ?? null,
        'cf' => $cfg['cf'] ?? null,
    ]);
    $agId = (int) $db->lastInsertId();
    $criados++;

    if (in_array($cfg['st'], ['em_andamento', 'concluido', 'cancelado', 'ausente'], true)) {
        $INS_AT->execute([
            'ag' => $agId, 'prof' => $cfg['prof'], 'ini' => $ini,
            'fim' => $cfg['st'] === 'concluido' ? $fim : null,
            'st' => $cfg['st'], 'obs' => null,
        ]);
        $atId = (int) $db->lastInsertId();

        if ($cfg['st'] === 'concluido' && $clinica) {
            $INS_PR->execute([
                'at' => $atId, 'q' => $clinica['queixa'], 'h' => $clinica['historico'],
                'sv' => json_encode(['texto' => $clinica['sinais_vitais']], JSON_UNESCAPED_UNICODE),
                'ia' => $clinica['analise_ia'],
                'd' => $clinica['diag'], 'c' => $clinica['conduta'],
            ]);
            $INS_FB->execute([
                'at' => $atId, 'prof' => $cfg['prof'], 'pac' => $cfg['pac'],
                'user' => $cfg['pac_user'] ?? null, 'nota' => $clinica['nota'] ?? 5,
                'com' => $clinica['comentario'] ?? 'Atendimento avaliado.',
            ]);
            $INS_DX->execute([
                'pac' => $cfg['pac'], 'med' => $profUserId[$cfg['prof']] ?? null,
                'q' => $clinica['queixa'], 's' => $clinica['sintomas'] ?? null,
                'h' => $clinica['historico'], 'sv' => $clinica['sinais_vitais'],
                'd' => $clinica['diag'], 'c' => $clinica['conduta'],
            ]);
        }
    }

    if ($cfg['st'] === 'agendado' && $criarLembrete) {
        $INS_LEM->execute(['ag' => $agId, 'msg' => 'Lembrete de consulta agendada no EloMed.']);
    }
    return $agId;
}

$pacUser = [1 => 16, 2 => 17, 3 => 18, 4 => 19, 5 => 20, 6 => 33];
function clinica(string $queixa, string $historico, string $sv, string $diag, string $conduta, string $sintomas = '', int $nota = 5, string $com = 'Atendimento avaliado.'): array {
    return [
        'queixa' => $queixa, 'historico' => $historico, 'sinais_vitais' => $sv,
        'analise_ia' => json_encode([
            'resumo' => $queixa,
            'hipoteses' => [$diag],
            'classificacao' => 'amarelo',
            'modelo' => 'openai/gpt-oss-20b',
        ], JSON_UNESCAPED_UNICODE),
        'diag' => $diag, 'conduta' => $conduta, 'sintomas' => $sintomas, 'nota' => $nota, 'comentario' => $com,
    ];
}

// ---- Profissional 32 (Richard) ----
$p = $prof32;
criarVisita($db, ['pac' => 1, 'pac_user' => 16, 'prof' => $p, 'inicio' => dt(dia(0), '09:00'), 'dur' => 60, 'st' => 'concluido'],
    clinica('Dor no peito ao esforco', 'Hipertenso, em uso de losartana. Pai com historico de IAM.',
        'PA 148/92 mmHg; FC 88 bpm; Tax 36.5C; SatO2 97%', 'Angina estavel',
        'Solicitado ECG e troponina; iniciado AAS 100mg; retorno em 7 dias.', 'Dor retroesternal aos esforcos', 5, 'Muito atencioso, explicou tudo.'));
criarVisita($db, ['pac' => 2, 'pac_user' => 17, 'prof' => $p, 'inicio' => dt(dia(0), '10:00'), 'dur' => 60, 'st' => 'em_andamento']);
criarVisita($db, ['pac' => 3, 'pac_user' => 18, 'prof' => $p, 'inicio' => dt(dia(0), '11:00'), 'dur' => 60, 'st' => 'agendado']);
criarVisita($db, ['pac' => 5, 'pac_user' => 20, 'prof' => $p, 'inicio' => dt(dia(0), '14:00'), 'dur' => 30, 'st' => 'agendado']);
criarVisita($db, ['pac' => 4, 'pac_user' => 19, 'prof' => $p, 'inicio' => dt(dia(0), '15:30'), 'dur' => 30, 'st' => 'pendente',
    'cia' => 'amarelo', 'jcia' => 'Crise asmatica.', 'cf' => 'amarelo']);
criarVisita($db, ['pac' => 6, 'pac_user' => 33, 'prof' => $p, 'inicio' => dt(dia(1), '09:00'), 'dur' => 30, 'st' => 'agendado']);
criarVisita($db, ['pac' => 2, 'pac_user' => 17, 'prof' => $p, 'inicio' => dt(dia(2), '10:00'), 'dur' => 30, 'st' => 'agendado']);
criarVisita($db, ['pac' => 1, 'pac_user' => 16, 'prof' => $p, 'inicio' => dt(dia(3), '11:00'), 'dur' => 30, 'st' => 'pendente', 'tri' => $t2,
    'cia' => 'amarelo', 'jcia' => 'Retorno pos-triagem.', 'cf' => 'amarelo']);
// Passado
criarVisita($db, ['pac' => 2, 'pac_user' => 17, 'prof' => $p, 'inicio' => dt(dia(-1), '10:00'), 'dur' => 30, 'st' => 'concluido'],
    clinica('Febre e dor no corpo', 'Sem doencas cronicas. Viajou para area endemica ha 6 dias.',
        'Tax 38.9C; PA 112/74; FC 96 bpm', 'Dengue (suspeita clinica)',
        'Hidratacao oral, dipirona SOS, hemograma e sorologia. Retorno se sinais de alarme.', 'Mialgia e cefaleia', 4, 'Oriuntada com clareza sobre sinais de alarme.'));
criarVisita($db, ['pac' => 3, 'pac_user' => 18, 'prof' => $p, 'inicio' => dt(dia(-3), '09:00'), 'dur' => 30, 'st' => 'concluido'],
    clinica('Controle de diabetes', 'Diabetes tipo 2 ha 8 anos em uso de metformina.',
        'PA 130/85; IMC 29; Glicemia jejum 146 mg/dL', 'Diabetes mellitus tipo 2 descompensado',
        'Ajuste de metformina 1000mg 2x/dia; orientacao dietetica; HbA1c solicitada.', 'Poliuria e polidipsia', 5, 'Gostei do acompanhamento.'));
criarVisita($db, ['pac' => 5, 'pac_user' => 20, 'prof' => $p, 'inicio' => dt(dia(-5), '14:00'), 'dur' => 30, 'st' => 'concluido'],
    clinica('Retorno hipertensao', 'Hipertenso e dislipidemico, pos-revascularizacao.',
        'PA 138/86; FC 72 bpm', 'Hipertensao arterial controlada',
        'Mantida losartana + sinvastatina; retorno em 3 meses.', '', 5, 'Profissional excelente.'));
criarVisita($db, ['pac' => 4, 'pac_user' => 19, 'prof' => $p, 'inicio' => dt(dia(-8), '10:00'), 'dur' => 30, 'st' => 'cancelado',
    'just' => 'Paciente solicitou cancelamento por motivos pessoais.']);
criarVisita($db, ['pac' => 1, 'pac_user' => 16, 'prof' => $p, 'inicio' => dt(dia(-10), '09:00'), 'dur' => 30, 'st' => 'ausente']);
criarVisita($db, ['pac' => 6, 'pac_user' => 33, 'prof' => $p, 'inicio' => dt(dia(-14), '10:00'), 'dur' => 30, 'st' => 'concluido'],
    clinica('Consulta inicial', 'Paciente sem comorbidades.', 'PA 120/80; FC 78 bpm; Tax 36.6C', 'Saudavel',
        'Sem medicacoes. Orientacoes gerais de saude.', '', 5, 'Primeiro atendimento, otimo.'));
echo "== Agendamentos para o profissional $p (Richard) criados\n";

// ---- Profissional 1 (Dr. Arnaldo) ----
criarVisita($db, ['pac' => 1, 'pac_user' => 16, 'prof' => 1, 'inicio' => dt(dia(0), '08:00'), 'dur' => 30, 'st' => 'agendado']);
criarVisita($db, ['pac' => 3, 'pac_user' => 18, 'prof' => 1, 'inicio' => dt(dia(0), '13:00'), 'dur' => 30, 'st' => 'em_andamento']);
criarVisita($db, ['pac' => 5, 'pac_user' => 20, 'prof' => 1, 'inicio' => dt(dia(0), '16:00'), 'dur' => 30, 'st' => 'pendente',
    'cia' => 'amarelo', 'jcia' => 'Dislipidemia.', 'cf' => 'amarelo']);
criarVisita($db, ['pac' => 2, 'pac_user' => 17, 'prof' => 1, 'inicio' => dt(dia(1), '08:30'), 'dur' => 30, 'st' => 'agendado']);
criarVisita($db, ['pac' => 3, 'pac_user' => 18, 'prof' => 1, 'inicio' => dt(dia(4), '09:00'), 'dur' => 30, 'st' => 'pendente', 'tri' => $t3,
    'cia' => 'verde', 'jcia' => 'Dermatologia.', 'cf' => 'verde']);
criarVisita($db, ['pac' => 1, 'pac_user' => 16, 'prof' => 1, 'inicio' => dt(dia(-2), '08:00'), 'dur' => 30, 'st' => 'concluido'],
    clinica('Check-up cardiologico', 'Hipertenso controlado.', 'PA 128/82; FC 70 bpm', 'Hipertensao arterial sistemica',
        'Mantido tratamento; solicitado ECG de controle.', '', 5, 'Muito bom.'));
criarVisita($db, ['pac' => 2, 'pac_user' => 17, 'prof' => 1, 'inicio' => dt(dia(-6), '09:00'), 'dur' => 30, 'st' => 'concluido'],
    clinica('Palpitacoes', 'Episodios esporadicos de palpitacao.', 'PA 118/76; FC 82 bpm', 'Extrassistoles benignas',
        'Holter de 24h; evitar cafeina.', '', 4, 'Atencioso.'));

// ---- Outros profissionais ----
criarVisita($db, ['pac' => 3, 'pac_user' => 18, 'prof' => 2, 'inicio' => dt(dia(0), '09:30'), 'dur' => 30, 'st' => 'agendado']);
criarVisita($db, ['pac' => 4, 'pac_user' => 19, 'prof' => 2, 'inicio' => dt(dia(-1), '09:00'), 'dur' => 30, 'st' => 'concluido'],
    clinica('Crise de asma', 'Asmatica leve.', 'FR 22; SatO2 95%', 'Crise asmatica leve',
        'Salbutamol spray; orientado retorno se piora.', '', 5, 'Bom atendimento.'));
criarVisita($db, ['pac' => 1, 'pac_user' => 16, 'prof' => 3, 'inicio' => dt(dia(0), '10:30'), 'dur' => 30, 'st' => 'agendado']);
criarVisita($db, ['pac' => 2, 'pac_user' => 17, 'prof' => 3, 'inicio' => dt(dia(-3), '10:00'), 'dur' => 30, 'st' => 'concluido'],
    clinica('Lesao de pele', 'Lesao em dorso.', 'Pele integra, lesao pigmentada 1cm', 'Nevo melanocitico',
        'Orientado observacao; retorno se mudanca.', '', 5, 'Otimo.'));
criarVisita($db, ['pac' => 6, 'pac_user' => 33, 'prof' => 4, 'inicio' => dt(dia(0), '11:00'), 'dur' => 30, 'st' => 'agendado']);
criarVisita($db, ['pac' => 3, 'pac_user' => 18, 'prof' => 4, 'inicio' => dt(dia(-2), '11:00'), 'dur' => 30, 'st' => 'concluido'],
    clinica('Dor lombar', 'Dor lombar mecanica.', 'Sem deficit neurologico', 'Lombalgia aguda',
        'Analgesico e fisioterapia.', '', 4, 'Bom.'));
echo "== Agendamentos dos demais profissionais criados ($criados no total)\n";

// ------------------------------------------------------------------
// 5) Prontuario do paciente (append-only, por secao)
// ------------------------------------------------------------------
$INS_REG = $db->prepare(
    "INSERT INTO tbl_prontuario_registro
        (fk_paciente, fk_usuario, fk_profissional, fk_agendamento, secao, conteudo, status, data_hora)
     VALUES (:pac, :user, :prof, :ag, :secao, :conteudo, :status, :dh)"
);
$registros = [
    // paciente, autor(user), prof, secao, status, conteudo
    [6, 32, $prof32, 'anamnese', 'finalizado', 'Paciente relata quadro de dor de garganta ha 2 dias, sem febre. Refere bom estado geral. Nega alergias.'],
    [6, 32, $prof32, 'evolucao', 'finalizado', 'Avaliacao inicial: orofaringe hiperemiada, sem exsudato. Conduta: sintomaticos e hidratacao.'],
    [6, 32, $prof32, 'evolucao', 'finalizado', 'Retorno: melhora significativa dos sintomas. Mantida observacao.'],
    [6, 32, $prof32, 'evolucao', 'rascunho', 'Evolucao em elaboracao: aguardando resultado de exames laboratoriais.'],
    [6, 32, $prof32, 'complementar', 'finalizado', 'Solicitados exames: hemograma completo e PCR. Oriundo da triagem via WhatsApp.'],
    [1, 1, 1, 'anamnese', 'finalizado', 'Paciente hipertenso, refere dor toracica aos esforcos. Historico familiar de infarto.'],
    [1, 1, 1, 'evolucao', 'finalizado', 'Iniciado AAS e solicitado ECG. Pressao controlada com losartana.'],
    [1, 32, $prof32, 'evolucao', 'rascunho', 'Nota do Dr. Richard: revisar resultado do ECG antes do retorno do paciente.'],
    [1, 1, 1, 'complementar', 'finalizado', 'Exames: ECG com alteracoes inespecificas. Troponina negativa.'],
    [2, 32, $prof32, 'anamnese', 'finalizado', 'Quadro febril ha 4 dias com mialgia e cefaleia. Investigacao de dengue em curso.'],
    [2, 32, $prof32, 'evolucao', 'finalizado', 'Hidratacao e sintomaticos. Solicitado hemograma e sorologia.'],
    [3, 1, 1, 'anamnese', 'finalizado', 'Paciente diabetico, em uso de metformina. Queixa de poliuria.'],
    [3, 1, 1, 'evolucao', 'finalizado', 'Ajuste de dose de metformina. Orientacao dietetica reforcada.'],
];
foreach ($registros as [$pac, $user, $prof, $secao, $status, $conteudo]) {
    $INS_REG->execute([
        'pac' => $pac, 'user' => $user, 'prof' => $prof, 'ag' => null,
        'secao' => $secao, 'conteudo' => $conteudo, 'status' => $status,
        'dh' => date('Y-m-d H:i:s', strtotime('-' . random_int(1, 40) . ' day +' . random_int(0, 20) . ' hour')),
    ]);
}
echo "== Lancamentos do prontuario do paciente criados\n";

// ------------------------------------------------------------------
// 6) Uso de IA
// ------------------------------------------------------------------
$stIA = $db->prepare("INSERT INTO tbl_uso_ia (fk_usuario, modelo, quantidade_consultas, data_consulta) VALUES (?,?,?,?)");
$stIA->execute([32, 'openai/gpt-oss-20b', 7, dia(0)]);
$stIA->execute([32, 'openai/gpt-oss-20b', 4, dia(-1)]);
$stIA->execute([1, 'openai/gpt-oss-20b', 3, dia(0)]);
echo "== Uso de IA registrado\n";

echo "\nOK. Total de agendamentos criados: $criados\n";
