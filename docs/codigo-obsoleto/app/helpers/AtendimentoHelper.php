<?php

/**
 * AtendimentoHelper
 * Maps rows from tbl_triagem / tbl_diagnostico into the shape that the
 * agenda / visita / historico views expect (data_agendamento, horario_inicio,
 * horario_fim, nome_paciente, especialidade, status), without touching the DB.
 */

class AtendimentoHelper {

    /**
     * Turns a tbl_triagem row into an "appointment/visit" row.
     *
     * @param array  $t            Triagem row (aliases paciente_nome/profissional_nome optional).
     * @param string $especialidade Professional specialty label (e.g. 'Médico', 'Enfermagem').
     * @return array
     */
    public static function triagemParaVisita(array $t, $especialidade = '') {
        $criado = strtotime($t['criado_em'] ?? 'now');
        $base = strtotime($t['data_aprovacao'] ?? '') ?: $criado;
        $duracao = (int) Constants::VISITA_DURACAO_MINUTOS;

        return [
            'id'                => (int) ($t['id'] ?? 0),
            'agendamento_id'    => (int) ($t['id'] ?? 0),
            'fk_paciente'       => $t['fk_paciente'] ?? null,
            'fk_profissional'   => $t['fk_profissional'] ?? null,
            'nome_paciente'     => $t['paciente_nome'] ?? '',
            'paciente_nome'     => $t['paciente_nome'] ?? '',
            'nome_profissional' => $t['profissional_nome'] ?? '',
            'profissional_nome' => $t['profissional_nome'] ?? '',
            'especialidade'     => $especialidade ?: self::especialidadeDePapel($t['papel_profissional'] ?? ''),
            'data_agendamento'  => date('Y-m-d', $criado),
            'horario_inicio'    => date('H:i', $criado),
            'horario_fim'       => date('H:i', $criado + ($duracao * 60)),
            'status'            => self::statusAgenda($t['status'] ?? ''),
            'status_origem'     => $t['status'] ?? '',
            'classificacao'     => $t['classificacao'] ?? '',
            'criado_em'         => $t['criado_em'] ?? '',
            'vezes_reagendado'  => 0,
        ];
    }

    /**
     * Turns a tbl_diagnostico row into a "concluded visit / prontuário" row.
     */
    public static function diagnosticoParaVisita(array $d) {
        $criado = strtotime($d['criado_em'] ?? 'now');
        $duracao = (int) Constants::VISITA_DURACAO_MINUTOS;

        return [
            'id'                => (int) ($d['id'] ?? 0),
            'agendamento_id'    => (int) ($d['id'] ?? 0),
            'fk_paciente'       => $d['fk_paciente'] ?? null,
            'fk_profissional'   => $d['fk_medico'] ?? null,
            'nome_paciente'     => $d['paciente_nome'] ?? '',
            'paciente_nome'     => $d['paciente_nome'] ?? '',
            'nome_profissional' => $d['medico_nome'] ?? '',
            'profissional_nome' => $d['medico_nome'] ?? '',
            'especialidade'     => $d['especialidade'] ?? 'Médico',
            'data_agendamento'  => date('Y-m-d', $criado),
            'horario_inicio'    => date('H:i', $criado),
            'horario_fim'       => date('H:i', $criado + ($duracao * 60)),
            'status'            => 'concluida',
            'queixa_principal'  => $d['queixa_principal'] ?? '',
            'historico_medico'  => $d['historico_medico'] ?? '',
            'diagnostico_final' => $d['diagnostico_final'] ?? '',
            'conduta_medica'    => $d['conduta_medica'] ?? '',
            'sintomas'          => $d['sintomas'] ?? '',
            'criado_em'         => $d['criado_em'] ?? '',
        ];
    }

    /**
     * Maps the internal triage status to the label used by agenda screens.
     */
    public static function statusAgenda($status) {
        switch ($status) {
            case Constants::TRIAGEM_APROVADA:
                return 'agendado';
            case Constants::TRIAGEM_REJEITADA:
                return 'cancelado';
            default:
                return 'pendente';
        }
    }

    /**
     * Human label for a professional role slug.
     */
    public static function especialidadeDePapel($slug) {
        switch ($slug) {
            case Constants::PERFIL_MEDICO:
                return 'Médico';
            case Constants::PERFIL_ENFERMEIRO:
                return 'Enfermagem';
            case Constants::PERFIL_TECNICO_ENFERMAGEM:
                return 'Técnico de Enfermagem';
            case Constants::PERFIL_PACIENTE:
                return 'Paciente';
            default:
                return '';
        }
    }

    /**
     * Filters a collection of mapped visits to those scheduled for today.
     */
    public static function soHoje(array $visitas) {
        $hoje = date('Y-m-d');
        return array_values(array_filter($visitas, function ($v) use ($hoje) {
            return isset($v['data_agendamento']) && $v['data_agendamento'] === $hoje;
        }));
    }

    /**
     * Maps triage rows into visits and keeps only today's, sorted by time.
     */
    public static function filaDoDia(array $triagens, $especialidade = '') {
        $visitas = self::soHoje(array_map(
            fn($t) => self::triagemParaVisita($t, $especialidade),
            $triagens
        ));
        usort($visitas, fn($a, $b) => strcmp($a['horario_inicio'], $b['horario_inicio']));
        return $visitas;
    }

    /**
     * Maps triage rows into visits and keeps only upcoming ones
     * (still open or scheduled for today / later).
     */
    public static function futuros(array $triagens, $especialidade = '') {
        $hoje = date('Y-m-d');
        $visitas = array_map(fn($t) => self::triagemParaVisita($t, $especialidade), $triagens);
        $visitas = array_values(array_filter($visitas, function ($v) use ($hoje) {
            return in_array($v['status'], ['agendado', 'pendente'], true)
                && $v['data_agendamento'] >= $hoje;
        }));
        usort($visitas, fn($a, $b) => strcmp($a['data_agendamento'] . $a['horario_inicio'], $b['data_agendamento'] . $b['horario_inicio']));
        return $visitas;
    }

    /**
     * Maps triage rows into visits and keeps the concluded/cancelled ones
     * (used by the "previous appointments" table).
     */
    public static function passados(array $triagens, $especialidade = '') {
        $hoje = date('Y-m-d');
        $visitas = array_map(fn($t) => self::triagemParaVisita($t, $especialidade), $triagens);
        return array_values(array_filter($visitas, function ($v) use ($hoje) {
            return !(in_array($v['status'], ['agendado', 'pendente'], true)
                && $v['data_agendamento'] >= $hoje);
        }));
    }
}