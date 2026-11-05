<?php

/**
 * Public landing page - explains how the system works BEFORE login.
 */
class HomeController {
    public function __construct() {
        Session::start();
    }

    public function index() {
        if (Session::has('usuario_id')) {
            $perfil = Session::get('usuario_perfil');
            if (!$perfil) {
                header('Location: ' . BASE_URL . '/login/escolher_perfil');
                exit;
            }
            $rotas = [
                Constants::PERFIL_ADMIN => 'painel',
                Constants::PERFIL_MEDICO => 'medico',
                Constants::PERFIL_ENFERMEIRO => 'enfermeiro',
                Constants::PERFIL_TECNICO_ENFERMAGEM => 'tecnicoenfermagem',
                Constants::PERFIL_PACIENTE => 'paciente'
            ];
            header('Location: ' . BASE_URL . '/' . ($rotas[$perfil] ?? 'paciente') . '/index');
            exit;
        }
        require_once __DIR__ . '/../views/home/index.php';
    }
}