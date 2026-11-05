<?php

/**
 * CpfController
 * Consulta dados públicos associados a um CPF (quando disponíveis via APIs públicas)
 * e retorna JSON para autopreenchimento de formulários.
 * Não persiste dados.
 */
class CpfController
{
    public function __construct()
    {
        Session::start();
        if (!Session::has('usuario_id')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
            exit;
        }
    }

    public function consultar($cpf = null)
    {
        header('Content-Type: application/json; charset=utf-8');

        $cpf = preg_replace('/\D/', '', (string) $cpf);
        if (strlen($cpf) !== 11) {
            echo json_encode(['success' => false, 'message' => 'CPF inválido.']);
            exit;
        }

        $dados = $this->brasilApi($cpf);
        if ($dados === null) {
            $dados = $this->buscarLocal($cpf);
        }
        if ($dados === null) {
            $dados = $this->receitaWsMock($cpf);
        }

        if ($dados === null) {
            echo json_encode(['success' => false, 'message' => 'Não foi possível obter dados para este CPF no momento.']);
            exit;
        }

        echo json_encode($dados);
        exit;
    }

    private function requestJson(string $url): ?array
    {
        $contexto = stream_context_create([
            'http' => [
                'timeout'       => 8,
                'ignore_errors' => true,
                'header'        => "Accept: application/json\r\n",
                'user_agent'    => 'EloMed/1.0',
            ],
        ]);
        $corpo = @file_get_contents($url, false, $contexto);
        if ($corpo === false) {
            return null;
        }
        $dados = json_decode($corpo, true);
        return is_array($dados) ? $dados : null;
    }

    private function brasilApi(string $cpf): ?array
    {
        $url = 'https://brasilapi.com.br/api/cpf/v1/' . $cpf;
        $dados = $this->requestJson($url);
        if ($dados === null) {
            return null;
        }

        if (!empty($dados['error'])) {
            return null;
        }

        $sexo = null;
        if (!empty($dados['sexo'])) {
            $s = strtolower((string) $dados['sexo']);
            if (strpos($s, 'f') === 0) {
                $sexo = 'feminino';
            } elseif (strpos($s, 'm') === 0) {
                $sexo = 'masculino';
            } else {
                $sexo = 'outro';
            }
        }

        $nascimento = null;
        if (!empty($dados['dataNascimento'])) {
            $dn = (string) $dados['dataNascimento'];
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dn, $m)) {
                $nascimento = $m[1] . '-' . $m[2] . '-' . $m[3];
            } elseif (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dn, $m)) {
                $nascimento = $m[3] . '-' . $m[2] . '-' . $m[1];
            }
        }

        return [
            'success'         => true,
            'nome'            => $dados['nome'] ?? null,
            'data_nascimento' => $nascimento,
            'sexo'            => $sexo,
            'estado_civil'    => null,
            'telefone_principal' => null,
            'rg'              => null,
        ];
    }

    private function receitaWsMock(string $cpf): ?array
    {
        return null;
    }

    private function buscarLocal(string $cpf): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT p.cpf, p.rg, p.data_nascimento, p.sexo, p.estado_civil, u.nome FROM tbl_paciente p LEFT JOIN tbl_usuario u ON u.id=p.fk_usuario WHERE REPLACE(REPLACE(REPLACE(p.cpf,".",""),"-","")," ","") = ? LIMIT 1');
        $stmt->execute([$cpf]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $stmt2 = $pdo->prepare('SELECT p.cpf, p.rg, p.data_nascimento, p.sexo, p.estado_civil, u.nome FROM tbl_paciente p LEFT JOIN tbl_usuario u ON u.id=p.fk_usuario WHERE p.cpf = ? LIMIT 1');
            $stmt2->execute([$cpf]);
            $row = $stmt2->fetch(PDO::FETCH_ASSOC);
        }
        if ($row) {
            $nome = $row['nome'] ?? null;
            if ($nome === null && !empty($row['fk_usuario'])) {
            }
            return [
                'success'           => true,
                'nome'              => $nome,
                'data_nascimento'   => $row['data_nascimento'] ?? null,
                'sexo'              => $row['sexo'] ?? null,
                'estado_civil'      => $row['estado_civil'] ?? null,
                'telefone_principal' => null,
                'rg'                => $row['rg'] ?? null,
            ];
        }
        return null;
    }
}
