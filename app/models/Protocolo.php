<?php


class Protocolo extends Model {
    protected $table = 'tbl_protocolos';
    
    /**
     * Fetches all active protocols
     */
    public function getAllAtivos() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE ativo = TRUE ORDER BY nome ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetches a protocol by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetches questions for a protocol
     */
    public function getPerguntas($protocoloId) {
        $stmt = $this->db->prepare("SELECT * FROM tbl_perguntas_protocolo WHERE fk_protocolo = ? ORDER BY ordem ASC");
        $stmt->execute([$protocoloId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON for options and conditionals
        foreach ($questions as &$question) {
            if (!empty($question['opcoes'])) {
                $question['opcoes'] = json_decode($question['opcoes'], true);
            }
            if (!empty($question['condicional'])) {
                $question['condicional'] = json_decode($question['condicional'], true);
            }
        }
        
        return $questions;
    }
    
    /**
     * Fetches protocol by name (case insensitive)
     */
    public function getByNome($nome) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE LOWER(nome) = LOWER(?)");
        $stmt->execute([$nome]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Creates a new protocol
     */
    public function create($dados) {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (nome, descricao, categoria, ativo) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $dados['nome'],
            $dados['descricao'] ?? null,
            $dados['categoria'] ?? 'agudo',
            $dados['ativo'] ?? true
        ]);
    }
    
    /**
     * Adds a question to a protocol
     */
    public function addPergunta($dados) {
        $stmt = $this->db->prepare("INSERT INTO tbl_perguntas_protocolo (fk_protocolo, ordem, tipo, pergunta, placeholder, obrigatorio, opcoes, condicional) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $dados['fk_protocolo'],
            $dados['ordem'] ?? 0,
            $dados['tipo'],
            $dados['pergunta'],
            $dados['placeholder'] ?? null,
            $dados['obrigatorio'] ?? false,
            !empty($dados['opcoes']) ? json_encode($dados['opcoes']) : null,
            !empty($dados['condicional']) ? json_encode($dados['condicional']) : null
        ]);
    }
}
