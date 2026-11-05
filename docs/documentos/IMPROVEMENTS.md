# Melhorias Implementadas no Sistema EloMed

Este documento descreve todas as melhorias de segurança, arquitetura e funcionalidades implementadas no sistema.

## 📋 Índice

- [Segurança](#segurança)
- [Arquitetura](#arquitetura)
- [Funcionalidades](#funcionalidades)
- [Helpers](#helpers)
- [Configuração](#configuração)

## 🔒 Segurança

### 1. Constants Centralizadas
**Arquivo:** `app/config/Constants.php`

Define todas as constantes do sistema em um único lugar:
- Perfis de usuário (admin, médico, enfermeiro, etc.)
- Status de agendamento, triagem, visita
- Categorias de protocolo
- Tipos de pergunta
- Níveis de log
- Ações de auditoria

**Uso:**
```php
use Constants;
$status = Constants::AGENDAMENTO_AGENDADO;
$perfil = Constants::PERFIL_MEDICO;
```

### 2. Configuration Management
**Arquivo:** `app/config/Config.php`

Gerenciamento centralizado de configurações com suporte a variáveis de ambiente:
- Configurações de banco de dados
- Configurações de sessão
- Configurações de segurança
- Configurações de API de IA
- Configurações de cache
- Configurações de logging
- Configurações de email
- Configurações de upload

**Uso:**
```php
use Config;
$dbHost = Config::get('db.host');
$isDebug = Config::isDebug();
```

### 3. Exception Handling Customizado
**Arquivos:**
- `app/exceptions/AppException.php` - Exceção base
- `app/exceptions/ValidationException.php` - Erros de validação
- `app/exceptions/DatabaseException.php` - Erros de banco
- `app/exceptions/AuthenticationException.php` - Erros de autenticação
- `app/exceptions/AuthorizationException.php` - Erros de autorização
- `app/exceptions/NotFoundException.php` - Recurso não encontrado

**Uso:**
```php
use AuthenticationException;
throw new AuthenticationException("Credenciais inválidas");
```

### 4. CSRF Protection
**Arquivo:** `app/middleware/CSRF.php`

Proteção contra ataques CSRF com tokens gerados dinamicamente:
- Geração de tokens seguros
- Validação de tokens em requisições POST
- Expiração automática de tokens
- Campo HTML automático para formulários

**Uso:**
```php
use CSRF;

// No controller
if (!CSRF::validateRequest()) {
    throw new ValidationException("Token CSRF inválido");
}

// Na view
<?= CSRF::getField() ?>
```

### 5. Input Validation Global
**Arquivo:** `app/helpers/Validator.php`

Validação e sanitização de dados de entrada:
- Validação de campos obrigatórios
- Validação de email, números, datas
- Validação de senhas com requisitos complexos
- Sanitização automática de inputs
- Mensagens de erro customizáveis

**Uso:**
```php
use Validator;

$validator = new Validator($_POST);
$validator->required('nome')
          ->email('email')
          ->min('senha', 8)
          ->password('senha');

if ($validator->fails()) {
    throw new ValidationException("Erro de validação", $validator->getErrors());
}

$data = $validator->sanitizeAll();
```

### 6. Session Security Melhorada
**Arquivo:** `app/middleware/Session.php`

Gerenciamento seguro de sessões:
- Configurações de cookie seguro
- Regeneração periódica de ID de sessão
- Prevenção de session fixation
- Mensagens flash (exibem uma vez)
- Interface simplificada para manipulação

**Uso:**
```php
use Session;

Session::start();
Session::set('usuario_id', 123);
$id = Session::get('usuario_id');
Session::flash('success', 'Operação realizada!');
Session::destroy();
```

## 🏗️ Arquitetura

### 1. Sistema de Logging e Auditoria
**Arquivo:** `app/helpers/Logger.php`

Sistema completo de logging para auditoria e debug:
- Níveis de log (debug, info, warning, error, critical)
- Log de ações de auditoria com contexto completo
- Rotação automática de arquivos de log
- Limpeza de logs antigos
- Configuração de nível mínimo de log

**Uso:**
```php
use Logger;

Logger::info("Usuário logou", ['user_id' => 123]);
Logger::error("Erro na conexão", ['error' => $e->getMessage()]);
Logger::audit(Constants::AUDITORIA_ACAO_CRIAR_PRONTUARIO, ['prontuario_id' => 456]);
```

### 2. Sistema de Cache
**Arquivo:** `app/helpers/Cache.php`

Cache simples baseado em arquivos:
- Armazenamento de dados com expiração
- Verificação de existência de cache
- Remoção de itens específicos
- Limpeza completa do cache
- Método "remember" para cache lazy

**Uso:**
```php
use Cache;

// Armazenar
Cache::set('protocolos', $data, 3600);

// Recuperar
$data = Cache::get('protocolos', []);

// Verificar existência
if (Cache::has('protocolos')) {
    // ...
}

// Cache lazy
$data = Cache::remember('protocolos', 3600, function() {
    return $this->protocoloModel->getAllAtivos();
});
```

### 3. Sistema de Paginação
**Arquivo:** `app/helpers/Pagination.php`

Paginação de resultados com interface completa:
- Cálculo automático de offset e limit
- Navegação entre páginas
- Geração de URLs
- Informações de paginação (from, to, total)
- Renderização HTML

**Uso:**
```php
use Pagination;

$total = $this->model->count();
$pagination = new Pagination($total, 20, $currentPage, $url);

// Na query SQL
$sql = "SELECT * FROM tabela LIMIT {$pagination->getLimit()} OFFSET {$pagination->getOffset()}";

// Na view
<?= $pagination->render() ?>
```

## 🛠️ Funcionalidades

### Controller de IA Separado
**Arquivos:**
- `app/models/IA.php` - Model para API de IA
- `app/controllers/IAController.php` - Controller de IA

Lógica de IA isolada para uso por qualquer profissional:
- Configuração de API (Groq/OpenAI)
- Chamada à API com tratamento de erros
- Fallback quando não há API key
- Reutilizável por médicos, enfermeiros, técnicos

## 📝 Configuração

### Variáveis de Ambiente

Crie um arquivo `.env` na raiz do projeto:

```env
# Banco de Dados
DB_HOST=localhost
DB_NAME=elomed
DB_USER=root
DB_PASS=

# Sessão
SESSION_NAME=elomed_session
SESSION_LIFETIME=3600
SESSION_SECURE=false
SESSION_DOMAIN=

# API de IA
GROQ_API_KEY=
GROQ_MODEL=openai/gpt-oss-20b
OPENAI_API_KEY=

# Cache
CACHE_ENABLED=true
CACHE_DRIVER=file

# Logging
LOGGING_ENABLED=true
LOG_LEVEL=info

# Aplicação
APP_ENV=development
APP_DEBUG=true

# Email (opcional)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@elomed.com
MAIL_FROM_NAME=EloMed
```

### Estrutura de Diretórios Criada

```
elomed/
├── app/
│   ├── config/
│   │   ├── Constants.php (NOVO)
│   │   └── Config.php (NOVO)
│   ├── exceptions/
│   │   ├── AppException.php (NOVO)
│   │   ├── ValidationException.php (NOVO)
│   │   ├── DatabaseException.php (NOVO)
│   │   ├── AuthenticationException.php (NOVO)
│   │   ├── AuthorizationException.php (NOVO)
│   │   └── NotFoundException.php (NOVO)
│   ├── middleware/
│   │   ├── CSRF.php (NOVO)
│   │   └── Session.php (NOVO)
│   ├── helpers/
│   │   ├── Validator.php (NOVO)
│   │   ├── Logger.php (NOVO)
│   │   ├── Cache.php (NOVO)
│   │   └── Pagination.php (NOVO)
│   ├── models/
│   │   └── IA.php (NOVO)
│   └── controllers/
│       └── IAController.php (NOVO)
├── cache/ (NOVO - criado automaticamente)
└── logs/ (NOVO - criado automaticamente)
```

## 🚀 Próximos Passos Recomendados

1. **Integrar as melhorias nos controllers existentes**
   - Adicionar validação em todos os formulários
   - Adicionar proteção CSRF em todos os POST
   - Adicionar logging de ações críticas
   - Adicionar paginação em listas longas

2. **Implementar testes**
   - Criar testes unitários para models
   - Criar testes de integração para controllers
   - Criar testes E2E para fluxos principais

3. **Melhorar frontend**
   - Adicionar loading states
   - Melhorar mensagens de erro
   - Validação em tempo real
   - Design responsivo

4. **Funcionalidades adicionais**
   - Autenticação 2FA
   - Sistema de notificações
   - Exportação de dados
   - Dashboard analítico

## 📚 Exemplos de Uso

### Exemplo 1: Controller com todas as melhorias

```php
<?php
require_once __DIR__ . '/../config/Constants.php';
require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../middleware/Session.php';
require_once __DIR__ . '/../middleware/CSRF.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../helpers/Logger.php';

class ExemploController {
    public function criar() {
        Session::start();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Valida CSRF
            if (!CSRF::validateRequest()) {
                Session::flash('error', 'Token inválido');
                header('Location: index.php?controller=exemplo&action=index');
                exit;
            }
            
            // Valida inputs
            $validator = new Validator($_POST);
            $validator->required('nome')
                      ->required('email')
                      ->email('email')
                      ->min('senha', 8)
                      ->password('senha');
            
            if ($validator->fails()) {
                Session::flash('error', $validator->getFirstError());
                require_once __DIR__ . '/../views/exemplo/criar.php';
                return;
            }
            
            // Sanitiza dados
            $data = $validator->sanitizeAll();
            
            try {
                // Cria registro
                $this->model->criar($data);
                
                // Log de auditoria
                Logger::audit(Constants::AUDITORIA_ACAO_CRIAR_USUARIO, [
                    'nome' => $data['nome'],
                    'email' => $data['email']
                ]);
                
                Session::flash('success', 'Registro criado com sucesso!');
                header('Location: index.php?controller=exemplo&action=index');
                exit;
                
            } catch (DatabaseException $e) {
                Logger::error("Erro ao criar registro", ['error' => $e->getMessage()]);
                Session::flash('error', 'Erro ao criar registro');
                require_once __DIR__ . '/../views/exemplo/criar.php';
                return;
            }
        }
        
        require_once __DIR__ . '/../views/exemplo/criar.php';
    }
}
```

### Exemplo 2: View com CSRF

```php
<form method="POST" action="index.php?controller=exemplo&action=criar">
    <?= CSRF::getField() ?>
    
    <input type="text" name="nome" required>
    <input type="email" name="email" required>
    <input type="password" name="senha" required>
    
    <button type="submit">Salvar</button>
</form>
```

### Exemplo 3: Paginação em listas

```php
public function index() {
    Session::start();
    
    $page = $_GET['page'] ?? 1;
    $perPage = 20;
    
    $total = $this->model->count();
    $pagination = new Pagination($total, $perPage, $page, 'index.php?controller=exemplo&action=index');
    
    $dados = $this->model->getAll($pagination->getLimit(), $pagination->getOffset());
    
    require_once __DIR__ . '/../views/exemplo/index.php';
}
```

## ⚠️ Notas Importantes

1. **Compatibilidade:** Estas melhorias requerem PHP 7.4 ou superior
2. **Permissões:** Certifique-se de que os diretórios `cache/` e `logs/` têm permissões de escrita
3. **Configuração:** Configure as variáveis de ambiente antes de usar em produção
4. **Testes:** Teste todas as melhorias em ambiente de desenvolvimento antes de usar em produção
5. **Backup:** Faça backup do sistema antes de implementar as melhorias

## 📞 Suporte

Para dúvidas ou problemas, consulte a documentação ou entre em contato com a equipe de desenvolvimento.
