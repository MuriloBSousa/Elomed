<?php

/**
 * System Constants
 * Defines constant values for statuses, profiles and other settings
 */

class Constants {
    // User profiles
    const PERFIL_ADMIN = 'admin';
    const PERFIL_MEDICO = 'medico';
    const PERFIL_ENFERMEIRO = 'enfermeiro';
    const PERFIL_TECNICO_ENFERMAGEM = 'tecnico_enfermagem';
    const PERFIL_PACIENTE = 'paciente';

    /**
     * Profiles allowed to self-register (login screen and Google sign-in).
     * Admin is never an option here: it is created only by the administration.
     */
    const PERFIS_AUTOCADASTRO = ['medico', 'enfermeiro', 'tecnico_enfermagem', 'paciente'];
    
    // User statuses
    const STATUS_ATIVO = 'ativo';
    const STATUS_DESATIVADO = 'inativo';
    const STATUS_BLOQUEADO = 'bloqueado';
    
    // Appointment statuses
    const AGENDAMENTO_PENDENTE = 'pendente';
    const AGENDAMENTO_AGENDADO = 'agendado';
    const AGENDAMENTO_EM_ANDAMENTO = 'em_andamento';
    const AGENDAMENTO_CONCLUIDO = 'concluido';
    const AGENDAMENTO_CANCELADO = 'cancelado';
    const AGENDAMENTO_AUSENTE = 'ausente';
    const AGENDAMENTO_MAX_REAGENDAMENTOS = 2;
    
    // Triage statuses
    const TRIAGEM_PENDENTE = 'pendente';
    const TRIAGEM_APROVADA = 'aprovada';
    const TRIAGEM_REJEITADA = 'recusada';
    
    // Password recovery
    const RECUPERACAO_EXPIRACAO_MINUTOS = 60;

    // Brute-force protection for login
    const LOGIN_MAX_TENTATIVAS = 5;
    const LOGIN_BLOQUEIO_MINUTOS = 5;
    
    // Visit statuses
    const VISITA_EM_ANDAMENTO = 'em_andamento';
    const VISITA_CONCLUIDO = 'concluido';
    
    // Protocol categories
    const PROTOCOLO_CATEGORIA_AGUDO = 'agudo';
    const PROTOCOLO_CATEGORIA_CRONICO = 'cronico';
    const PROTOCOLO_CATEGORIA_PREVENTIVO = 'preventivo';
    
    // Protocol question types
    const PERGUNTA_TIPO_TEXTO = 'texto';
    const PERGUNTA_TIPO_NUMERO = 'numero';
    const PERGUNTA_TIPO_TEXTAREA = 'textarea';
    const PERGUNTA_TIPO_SELECT = 'select';
    const PERGUNTA_TIPO_RADIO = 'radio';
    const PERGUNTA_TIPO_CHECKBOX = 'checkbox';
    
    // Log levels
    const LOG_LEVEL_DEBUG = 'debug';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_CRITICAL = 'critical';
    
    // Audit actions
    const AUDITORIA_ACAO_LOGIN = 'login';
    const AUDITORIA_ACAO_LOGOUT = 'logout';
    const AUDITORIA_ACAO_CRIAR_USUARIO = 'criar_usuario';
    const AUDITORIA_ACAO_ATUALIZAR_USUARIO = 'atualizar_usuario';
    const AUDITORIA_ACAO_DESATIVAR_USUARIO = 'desativar_usuario';
    const AUDITORIA_ACAO_CRIAR_AGENDAMENTO = 'criar_agendamento';
    const AUDITORIA_ACAO_ATUALIZAR_AGENDAMENTO = 'atualizar_agendamento';
    const AUDITORIA_ACAO_CRIAR_PRONTUARIO = 'criar_prontuario';
    const AUDITORIA_ACAO_ATUALIZAR_PRONTUARIO = 'atualizar_prontuario';
    const AUDITORIA_ACAO_GERAR_DIAGNOSTICO_IA = 'gerar_diagnostico_ia';
    const AUDITORIA_ACAO_USO_IA = 'uso_ia';
    const AUDITORIA_ACAO_CRIAR_TRIAGEM = 'criar_triagem';
    const AUDITORIA_ACAO_ANALISE_IA_ENCAMINHAMENTO = 'analise_ia_encaminhamento';
    const AUDITORIA_ACAO_ATRIBUIR_ENCAMINHAMENTO = 'atribuir_encaminhamento';
    const AUDITORIA_ACAO_ENCAMINHAR_TRIAGEM = 'encaminhar_triagem';
    const AUDITORIA_ACAO_APROVAR_TRIAGEM = 'aprovar_triagem';
    const AUDITORIA_ACAO_RECUSAR_TRIAGEM = 'recusar_triagem';
    const AUDITORIA_ACAO_RECUPERAR_SENHA = 'recuperar_senha';
    const AUDITORIA_ACAO_REGISTRAR_FEEDBACK = 'registrar_feedback';
    const AUDITORIA_ACAO_REDEFINIR_SENHA = 'redefinir_senha';
    const AUDITORIA_ACAO_LOGIN_GOOGLE = 'login_google';
    
    // Session settings
    const SESSION_LIFETIME = 3600; // 1 hour in seconds
    const SESSION_NAME = 'elomed_session';
    
    // Pagination settings
    const PAGINATION_DEFAULT_LIMIT = 20;
    const PAGINATION_MAX_LIMIT = 100;
    
    // Cache settings
    const CACHE_LIFETIME_SHORT = 300; // 5 minutes
    const CACHE_LIFETIME_MEDIUM = 1800; // 30 minutes
    const CACHE_LIFETIME_LONG = 3600; // 1 hour

    // RBAC permissions (slugs)
    const PERM_MEU_CADASTRO = 'meu_cadastro';
    const PERM_DASHBOARD_VISUALIZAR = 'dashboard_visualizar';

    const PERM_PAINEL_ACESSAR = 'painel_acessar';
    const PERM_USUARIO_GERENCIAR = 'usuario_gerenciar';
    const PERM_USUARIO_CADASTRAR_PROFISSIONAL = 'usuario_cadastrar_profissional';
    const PERM_USUARIO_CADASTRAR_PACIENTE = 'usuario_cadastrar_paciente';
    const PERM_AUDITORIA_VISUALIZAR = 'auditoria_visualizar';
    const PERM_AUDITORIA_PDF = 'auditoria_gerar_pdf';
    const PERM_AUDITORIA_MONITORAR = 'auditoria_monitorar';
    const PERM_AGENDAMENTO_GERENCIAR = 'agendamento_gerenciar';
    const PERM_AGENDAMENTO_SOLICITAR = 'agendamento_solicitar';

    const PERM_MEDICO_ACESSAR = 'medico_acessar';
    const PERM_MEDICO_PENDENCIAS = 'medico_pendencias';
    const PERM_MEDICO_MINHA_AGENDA = 'medico_minha_agenda';
    const PERM_MEDICO_HISTORICO_ATENDIMENTOS = 'medico_historico_atendimentos';

    const PERM_ATENDIMENTO_INICIAR = 'atendimento_iniciar';
    const PERM_PRONTUARIO_GERENCIAR = 'prontuario_gerenciar';
    const PERM_DIAGNOSTICO_IA = 'diagnostico_ia';
    const PERM_TRIAGEM_APROVAR = 'triagem_aprovar';

    const PERM_ENFERMEIRO_ACESSAR = 'enfermeiro_acessar';
    const PERM_ENFERMEIRO_MINHA_AGENDA = 'enfermeiro_minha_agenda';
    const PERM_ENFERMEIRO_HISTORICO = 'enfermeiro_historico';

    const PERM_TRIAGEM_GERENCIAR = 'triagem_gerenciar';
    const PERM_TRIAGEM_ANALISAR = 'triagem_analisar';

    const PERM_TECNICO_ACESSAR = 'tecnico_acessar';
    const PERM_TECNICO_MINHA_AGENDA = 'tecnico_minha_agenda';
    const PERM_TECNICO_HISTORICO = 'tecnico_historico';


    const PERM_PACIENTE_ACESSAR = 'paciente_acessar';
    const PERM_PACIENTE_MINHAS_TRIAGENS = 'paciente_minhas_triagens';
    const PERM_PACIENTE_MEUS_AGENDAMENTOS = 'paciente_meus_agendamentos';
    const PERM_PACIENTE_HISTORICO = 'paciente_historico';
    const PERM_PACIENTE_LINHA_DO_TEMPO = 'paciente_linha_do_tempo';
    const PERM_FEEDBACK_REGISTRAR = 'feedback_registrar';
    const PERM_FEEDBACK_VISUALIZAR = 'feedback_visualizar';

    // ---- Prontuario do paciente (Parte 4) ----
    const PERM_PRONTUARIO_PACIENTE_ACESSAR   = 'prontuario_paciente_acessar';
    const PERM_PRONTUARIO_PACIENTE_REGISTRAR = 'prontuario_paciente_registrar';

    const PRONTUARIO_SECAO_ANAMNESE     = 'anamnese';
    const PRONTUARIO_SECAO_EVOLUCAO    = 'evolucao';
    const PRONTUARIO_SECAO_COMPLEMENTAR = 'complementar';

    const PRONTUARIO_STATUS_RASCUNHO    = 'rascunho';
    const PRONTUARIO_STATUS_FINALIZADO  = 'finalizado';

    // acoes de auditoria (nao colidirem com AUDITORIA_ACAO_CRIAR_PRONTUARIO,
    // que ja existe e pertence ao prontuario por atendimento)
    const AUDITORIA_ACAO_CONSULTAR_PRONTUARIO  = 'consultar_prontuario';
    const AUDITORIA_ACAO_REGISTRAR_PRONTUARIO  = 'registrar_prontuario';
    const AUDITORIA_ACAO_EDITAR_PRONTUARIO     = 'editar_prontuario';
    const AUDITORIA_ACAO_FINALIZAR_PRONTUARIO  = 'finalizar_prontuario';
    const AUDITORIA_ACAO_EXCLUIR_PRONTUARIO    = 'excluir_prontuario';

    // defaults das colunas ENUM de tbl_paciente (existem no schema como
    // DEFAULT, mas nao como constante no PHP - o controller precisa delas)
    const PACIENTE_SEXO_NAO_INFORMADO    = 'nao_informado';
    const PACIENTE_ESTADO_NAO_INFORMADO = 'nao_informado';
}