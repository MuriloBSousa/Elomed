<?php

/**
 * EnderecoController
 * Fetches Brazilian addresses by CEP (ViaCEP primary, BrasilAPI fallback) and
 * returns them as JSON so the CEP forms auto-fill street/bairro/city/UF.
 * It only reads third-party public APIs - nothing is persisted here.
 */

class EnderecoController {

    public function __construct() {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
            exit;
        }
    }

    public function consultar_cep($cep = null) {
        header('Content-Type: application/json; charset=utf-8');

        $cep = preg_replace('/\D/', '', (string) $cep);
        if (strlen($cep) !== 8) {
            echo json_encode(['success' => false, 'message' => 'CEP inválido.']);
            exit;
        }

        $dados = $this->viaCep($cep);
        if ($dados === null) {
            $dados = $this->brasilApi($cep);
        }

        if ($dados === null) {
            echo json_encode(['success' => false, 'message' => 'CEP não encontrado. Verifique o número e tente novamente.']);
            exit;
        }

        echo json_encode([
            'success'     => true,
            'logradouro'  => $dados['logradouro'] ?? '',
            'bairro'      => $dados['bairro'] ?? '',
            'cidade'      => $dados['cidade'] ?? '',
            'uf'          => $dados['uf'] ?? '',
            'complemento' => $dados['complemento'] ?? ''
        ]);
        exit;
    }

    private function requestJson(string $url): ?array {
        $contexto = stream_context_create([
            'http' => [
                'timeout'       => 8,
                'ignore_errors' => true,
                'header'        => "Accept: application/json\r\n"
            ]
        ]);
        $corpo = @file_get_contents($url, false, $contexto);
        if ($corpo === false) {
            return null;
        }
        $dados = json_decode($corpo, true);
        return is_array($dados) ? $dados : null;
    }

    private function viaCep(string $cep): ?array {
        $dados = $this->requestJson('https://viacep.com.br/ws/' . $cep . '/json/');
        if ($dados === null || !empty($dados['erro'])) {
            return null;
        }
        return [
            'logradouro'  => $dados['logradouro'] ?? '',
            'bairro'      => $dados['bairro'] ?? '',
            'cidade'      => $dados['localidade'] ?? '',
            'uf'          => $dados['uf'] ?? '',
            'complemento' => $dados['complemento'] ?? ''
        ];
    }

    private function brasilApi(string $cep): ?array {
        $dados = $this->requestJson('https://brasilapi.com.br/api/cep/v1/' . $cep);
        // BrasilAPI returns a "type" key on errors (404, 400, ...).
        if ($dados === null || isset($dados['type'])) {
            return null;
        }
        return [
            'logradouro'  => $dados['street'] ?? '',
            'bairro'      => $dados['neighborhood'] ?? '',
            'cidade'      => $dados['city'] ?? '',
            'uf'          => $dados['state'] ?? '',
            'complemento' => $dados['complemento'] ?? ''
        ];
    }
}