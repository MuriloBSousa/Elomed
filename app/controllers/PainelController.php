<?php
class PainelController {
    private $model;

    public function __construct() {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Location: ' . BASE_URL . '/login/index');
            exit;
        }

        // Business rule: the dashboard (general dashboard and PDFs) is for administrators.
        // Other roles are redirected to their own panels.
        if (!RBAC::can(Constants::PERM_PAINEL_ACESSAR)) {
            $rotas = [
                Constants::PERFIL_MEDICO               => 'medico',
                Constants::PERFIL_ENFERMEIRO           => 'enfermeiro',
                Constants::PERFIL_TECNICO_ENFERMAGEM   => 'tecnicoenfermagem',
                Constants::PERFIL_PACIENTE             => 'paciente'
            ];
            $d = $rotas[Session::get('usuario_perfil')] ?? 'login';
            header('Location: ' . BASE_URL . '/' . $d . '/index');
            exit;
        }

        $this->model = new Painel();
    }
        
    public function index() {
        $estatisticas = $this->model->getEstatisticaPainel();
        $escalaVisitas = $this->model->getEscalaDoDia();

        require_once __DIR__ . '/../views/painel/index.php';
    }

    /**
     * Feedback dos pacientes. Visível apenas para quem tem a permissão
     * feedback_visualizar, concedida somente ao papel admin.
     */
    public function feedback() {
        if (!RBAC::can(Constants::PERM_FEEDBACK_VISUALIZAR)) {
            Session::flash('error', 'Você não tem permissão para ver as avaliações dos pacientes.');
            header('Location: ' . BASE_URL . '/painel/index');
            exit;
        }

        $feedbackModel = new Feedback();
        $feedbacks = $feedbackModel->listarTodos();
        $porProfissional = $feedbackModel->resumoPorProfissional();
        $indicadores = $feedbackModel->indicadores();

        require_once __DIR__ . '/../views/painel/feedback.php';
    }

    public function relatorio_pdf_agenda() {
        $this->streamPdf('pdf_agenda.php', [
            'escala' => $this->model->getEscalaDoDia(),
            'data'   => date('d/m/Y'),
        ], 'agenda');
    }

    public function relatorio_pdf_triagens() {
        $this->streamPdf('pdf_triagens.php', [
            'triagens' => $this->model->getTriagens(date('Y-m-d')),
            'data'     => date('d/m/Y'),
        ], 'triagens');
    }

    public function relatorio_pdf_prontuarios() {
        $this->streamPdf('pdf_prontuarios.php', [
            'prontuarios' => $this->model->getProntuarios(date('Y-m-d')),
            'data'        => date('d/m/Y'),
        ], 'prontuarios');
    }

    private function streamPdf(string $view, array $variaveis, string $nomeArquivo) {
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        extract($variaveis);
        ob_start();
        require __DIR__ . '/../views/painel/' . $view;
        $html = ob_get_clean();

        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($nomeArquivo . '_' . date('Ymd_His') . '.pdf', ['Attachment' => false]);
        exit;
    }
}
