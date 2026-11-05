<?php

/**
 * Escapes output for safe HTML rendering (XSS prevention).
 *
 * @param mixed $value
 * @return string
 */
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('perfil_meta')) {
    function perfil_meta(?string $perfil): array {
        $map = [
            'admin'              => ['Administrador', 'bi-shield-fill-check'],
            'medico'             => ['Médico', 'bi-heart-pulse'],
            'enfermeiro'         => ['Enfermeiro', 'bi-person-fill-check'],
            'tecnico_enfermagem' => ['Técnico Enfermagem', 'bi-clipboard2-pulse'],
            'paciente'           => ['Paciente', 'bi-person-heart'],
        ];
        return $map[$perfil] ?? ['Usuário', 'bi-person'];
    }
}

if (!function_exists('rotulo_profissional')) {
    function rotulo_profissional(?string $perfil): string {
        $rotulos = [
            'medico'             => 'Médico(a)',
            'enfermeiro'         => 'Enfermeiro(a)',
            'tecnico_enfermagem' => 'Técnico(a) de Enfermagem',
        ];
        return $rotulos[$perfil] ?? ucfirst(str_replace('_', ' ', (string) $perfil));
    }
}

if (!function_exists('badge_risco')) {
    function badge_risco(?string $classificacao): string {
        if ($classificacao === 'vermelho') {
            return 'badge-error';
        }
        if ($classificacao === 'amarelo') {
            return 'badge-warning';
        }
        return 'badge-success';
    }
}

if (!function_exists('rotulo_classificacao')) {
    function rotulo_classificacao(?string $classificacao): string {
        $rotulos = [
            'vermelho' => 'Vermelho - urgente',
            'amarelo'  => 'Amarelo - prioridade',
            'verde'    => 'Verde - comum',
        ];
        return $rotulos[$classificacao] ?? '';
    }
}

if (!function_exists('rotulo_perfil_triagem')) {
    function rotulo_perfil_triagem(?string $perfil): string {
        return $perfil === 'medico' ? 'Médico(a)' : 'Enfermeiro(a)';
    }
}