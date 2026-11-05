<?php
require_once __DIR__ . '/../config/Constants.php';
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../middleware/Session.php';
require_once __DIR__ . '/../helpers/Pagination.php';
require_once __DIR__ . '/../models/Auditoria.php';

class AuditoriaController {
    private $auditoriaModel;

    public function __construct() {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }
        if (!RBAC::can(Constants::PERM_AUDITORIA_VISUALIZAR)) {
            header('Location: ' . BASE_URL . '/painel/index');
            exit;
        }
        $this->auditoriaModel = new Auditoria();
    }

    public function index() {
        $acao = trim($_GET['acao'] ?? '');
        $por  = trim($_GET['por']  ?? '');
        $de   = trim($_GET['data_inicio'] ?? '');
        $ate  = trim($_GET['data_fim'] ?? '');
        $pagina = max(1, (int) ($_GET['page'] ?? 1));
        $porPagina = 15;

        $whereClauses   = [];
        $params = [];

        if ($acao !== '') {
            $whereClauses[] = 'acao = :acao';
            $params['acao'] = $acao;
        }
        if ($por !== '') {
            $whereClauses[] = "(usuario_nome LIKE :por OR usuario_perfil LIKE :por)";
            $params['por'] = '%' . $por . '%';
        }
        if ($de !== '') {
            $whereClauses[] = 'DATE(data_hora) >= :de';
            $params['de'] = $de;
        }
        if ($ate !== '') {
            $whereClauses[] = 'DATE(data_hora) <= :ate';
            $params['ate'] = $ate;
        }
        $where = $whereClauses ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

        $filtros = array_filter([
            'acao'        => $acao,
            'por'         => $por,
            'data_inicio' => $de,
            'data_fim'    => $ate
        ], function ($v) { return $v !== ''; });

        $total = $this->auditoriaModel->contar($where, $params);
        $pagination = new Pagination($total, $porPagina, $pagina, BASE_URL . '/auditoria/index', $filtros);

        $registros = $this->auditoriaModel->listar(
            $where, $params, $pagination->getLimit(), $pagination->getOffset()
        );

        $acoesDisponiveis = $this->auditoriaModel->listarAcoes();

        require_once __DIR__ . '/../views/auditoria/index.php';
    }

    public function monitoramento() {
        if (!RBAC::can(Constants::PERM_AUDITORIA_MONITORAR)) {
            header('Location: ' . BASE_URL . '/painel/index');
            exit;
        }

        $por = trim($_GET['por'] ?? '');
        $de  = trim($_GET['data_inicio'] ?? '');
        $ate = trim($_GET['data_fim'] ?? '');

        $stats = [
            'usuarios_ativos' => $this->auditoriaModel->contarUsuariosAtivos(),
            'logins_hoje'     => $this->auditoriaModel->contarAcessosHoje(),
            'visitas'         => $this->auditoriaModel->contarVisitasConcluidas(),
            'acoes'           => $this->auditoriaModel->contarAcoes()
        ];

        $sessoes = $this->auditoriaModel->resumoSessoes($de, $ate, $por);
        $acessos = $this->auditoriaModel->registrarAcesso($de, $ate, $por, 100);
        $visitas = $this->auditoriaModel->visitasRealizadas(50);

        // Fill in the stay-duration column (time between the first login and last logout of the day).
        foreach ($sessoes as &$s) {
            $s['permanencia'] = '';
            if (!empty($s['primeira_entrada']) && !empty($s['ultima_saida'])) {
                $seconds = max(0, strtotime($s['ultima_saida']) - strtotime($s['primeira_entrada']));
                if ($seconds > 0) {
                    $hours = floor($seconds / 3600);
                    $minutes = floor(($seconds % 3600) / 60);
                    $s['permanencia'] = ($hours > 0 ? $hours . 'h ' : '') . $minutes . 'min';
                }
            }
            $s['concluida'] = !empty($s['ultima_saida']);
        }
        unset($s);

        require_once __DIR__ . '/../views/auditoria/monitoramento.php';
    }

    public function pdf() {
        if (!RBAC::can(Constants::PERM_AUDITORIA_PDF)) {
            header('Location: ' . BASE_URL . '/painel/index');
            exit;
        }

        $acao = trim($_GET['acao'] ?? '');
        $por  = trim($_GET['por'] ?? '');
        $de   = trim($_GET['data_inicio'] ?? '');
        $ate  = trim($_GET['data_fim'] ?? '');

        $whereClauses   = [];
        $params = [];
        if ($acao !== '') { $whereClauses[] = 'acao = :acao';            $params['acao'] = $acao; }
        if ($por  !== '') { $whereClauses[] = "(usuario_nome LIKE :por OR usuario_perfil LIKE :por)"; $params['por'] = '%' . $por . '%'; }
        if ($de   !== '') { $whereClauses[] = 'DATE(data_hora) >= :de';  $params['de'] = $de; }
        if ($ate  !== '') { $whereClauses[] = 'DATE(data_hora) <= :ate'; $params['ate'] = $ate; }
        $where = $whereClauses ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

        $registros = $this->auditoriaModel->listar($where, $params, 10000, 0);

        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        ob_start();
        require __DIR__ . '/../views/auditoria/pdf.php';
        $html = ob_get_clean();

        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('trilha_auditoria_' . date('Ymd_His') . '.pdf', ['Attachment' => false]);
        exit;
    }
}
