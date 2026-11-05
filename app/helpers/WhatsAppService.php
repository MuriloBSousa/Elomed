<?php

/**
 * WhatsAppService - envio de mensagens via Evolution API.
 *
 * Endpoints (REST / cURL puro, o projeto nao usa Guzzle):
 *   POST {WHATSAPP_API_URL}/message/sendText/{WHATSAPP_INSTANCE}
 *   Header: apikey: {WHATSAPP_API_TOKEN}
 *
 * Regra de negocio: notificacao e best-effort. Este servico NUNCA lanca
 * excecao - falha de WhatsApp nao pode impedir um agendamento de salvar.
 * Tudo e registrado via Logger.
 */
class WhatsAppService {
    private string $apiUrl;
    private string $token;
    private string $instance;
    private bool $habilitado;

    public function __construct() {
        $this->apiUrl     = rtrim((string) Env::get('WHATSAPP_API_URL', ''), '/');
        $this->token      = (string) Env::get('WHATSAPP_API_TOKEN', '');
        $this->instance   = trim((string) Env::get('WHATSAPP_INSTANCE', ''));
        $this->habilitado = filter_var(Env::get('WHATSAPP_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Só envia se estiver ligado no .env e com as credenciais completas.
     */
    public function estaAtivo(): bool {
        if (!$this->habilitado) {
            return false;
        }
        if ($this->apiUrl === '' || $this->token === '' || $this->instance === '') {
            Logger::warning('WhatsApp desativado: WHATSAPP_API_URL / WHATSAPP_API_TOKEN / WHATSAPP_INSTANCE incompletos no .env');
            return false;
        }
        return true;
    }

    /**
     * Formata o numero para E.164 com DDI do Brasil.
     * Ex.: (61) 98888-7777 -> 5561988887777
     */
    public function formatarTelefone(string $telefone): string {
        $limpo = preg_replace('/\D/', '', $telefone);
        if (strlen($limpo) === 10 || strlen($limpo) === 11) {
            $limpo = '55' . $limpo;
        }
        return $limpo;
    }

    /**
     * Envia um texto para um numero. Retorna true somente em HTTP 2xx.
     */
    public function enviar(string $telefone, string $mensagem): bool {
        if (!$this->estaAtivo()) {
            return false;
        }

        $numero = $this->formatarTelefone($telefone);
        if (strlen($numero) < 12) {
            Logger::warning('WhatsApp: numero invalido ignorado', ['telefone' => $telefone]);
            return false;
        }

        try {
            $endpoint = $this->apiUrl . '/message/sendText/' . rawurlencode($this->instance);
            $payload = [
                'number'  => $numero,
                'text'    => $mensagem,
                'options' => [
                    'delay'    => 1200,
                    'presence' => 'composing',
                ],
            ];

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'apikey: ' . $this->token,
                ],
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT        => 8,
            ]);

            $resposta = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $erroCurl = curl_error($ch);
            curl_close($ch);

            if ($resposta === false || $httpCode < 200 || $httpCode >= 300) {
                Logger::error('WhatsApp: falha no envio', [
                    'http_status' => $httpCode,
                    'erro_curl'   => $erroCurl,
                    'resposta'    => is_string($resposta) ? substr($resposta, 0, 300) : '',
                    'numero'      => $numero,
                ]);
                return false;
            }

            Logger::info('WhatsApp: mensagem enviada', ['numero' => $numero, 'http_status' => $httpCode]);
            return true;
        } catch (Throwable $e) {
            Logger::error('WhatsApp: excecao no envio', ['erro' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Localiza o telefone principal (tbl_telefone.tipo = 'principal')
     * do usuario; cai para o primeiro cadastrado se nao houver principal.
     */
    public function telefoneDoUsuario(int $usuarioId): string {
        try {
            $telefones = (new Telefone())->doUsuario($usuarioId);
        } catch (Throwable $e) {
            Logger::error('WhatsApp: falha ao buscar telefone', ['usuario' => $usuarioId, 'erro' => $e->getMessage()]);
            return '';
        }

        foreach ($telefones as $t) {
            $numero = trim((string) ($t['telefone'] ?? ''));
            if ($numero !== '' && ($t['tipo'] ?? '') === 'principal') {
                return $numero;
            }
        }
        foreach ($telefones as $t) {
            $numero = trim((string) ($t['telefone'] ?? ''));
            if ($numero !== '') {
                return $numero;
            }
        }
        return '';
    }

    /**
     * Confirmacao enviada apos a criacao do agendamento.
     */
    public function enviarConfirmacaoAgendamento(int $usuarioId, string $nomePaciente, string $data, string $inicio, string $fim): bool {
        $telefone = $this->telefoneDoUsuario($usuarioId);
        if ($telefone === '') {
            Logger::info('WhatsApp: agendamento sem telefone cadastrado, confirmacao nao enviada', ['usuario' => $usuarioId]);
            return false;
        }

        $dataBr = date('d/m/Y', strtotime($data));
        $mensagem  = "\xF0\x9F\x8F\xA5 *EloMed - Confirmacao de Agendamento*\n\n";
        $mensagem .= "Ola, *" . $nomePaciente . "*!\n";
        $mensagem .= "Sua visita/consulta foi agendada com sucesso.\n\n";
        $mensagem .= "\xF0\x9F\x93\x85 *Data/Hora:* {$dataBr}, das {$inicio} as {$fim}\n";
        $mensagem .= "\nPara duvidas ou cancelamentos, responda a esta mensagem.";

        return $this->enviar($telefone, $mensagem);
    }

    /**
     * Aviso enviado apos o reagendamento feito pelo paciente.
     */
    public function enviarAvisoReagendamento(int $usuarioId, string $nomePaciente, string $data, string $inicio, string $fim): bool {
        $telefone = $this->telefoneDoUsuario($usuarioId);
        if ($telefone === '') {
            Logger::info('WhatsApp: reagendamento sem telefone cadastrado, aviso nao enviado', ['usuario' => $usuarioId]);
            return false;
        }

        $dataBr = date('d/m/Y', strtotime($data));
        $mensagem  = "\xF0\x9F\x93\x85 *EloMed - Agendamento Reagendado*\n\n";
        $mensagem .= "Ola, *" . $nomePaciente . "*!\n";
        $mensagem .= "Seu novo horario foi registrado com sucesso.\n\n";
        $mensagem .= "\xF0\x9F\x93\x85 *Nova data/Hora:* {$dataBr}, das {$inicio} as {$fim}\n";
        $mensagem .= "\nAguardando a confirmacao da equipe. Qualquer problema, responda a esta mensagem.";

        return $this->enviar($telefone, $mensagem);
    }
}
