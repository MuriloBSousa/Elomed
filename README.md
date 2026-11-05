# EloMed — Sistema de Gestão de Atendimento Domiciliar

Plataforma web para gestão de atendimento domiciliar em saúde, integrando **agendamento de visitas**, **triagem com classificação de risco**, **prontuário eletrônico** e **apoio com inteligência artificial** (sempre com revisão humana). Sistema com perfis de acesso distintos (admin, médico, enfermeiro, técnico de enfermagem e paciente), trilha de auditoria e relatórios em PDF.

---

## Sumário

- [Funcionalidades](#funcionalidades)
- [Perfis de acesso](#perfis-de-acesso)
- [Tecnologias](#tecnologias)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração (.env)](#configuração-env)
- [Banco de dados](#banco-de-dados)
- [Credenciais de demonstração](#credenciais-de-demonstração)
- [Estrutura do projeto](#estrutura-do-projeto)
- [Rotas principais](#rotas-principais)
- [Regras de negócio](#regras-de-negócio)
- [Segurança e auditoria](#segurança-e-auditoria)
- [Limitações e itens em desenvolvimento](#limitações-e-itens-em-desenvolvimento)

---

## Funcionalidades

- **Login e cadastro** — login por e-mail e senha, login com **Google OAuth 2.0**, recuperação de senha por e-mail (token de uso único com validade de 60 minutos) e autocadastro restrito aos perfis médico, enfermeiro, técnico de enfermagem e paciente.
- **Escolha de perfil pós-login** — o administrador entra direto no painel; os demais escolhem o perfil que desejam usar (apenas perfis que o usuário realmente possui).
- **Agendamento de visitas** — agendamento online pelo paciente ou pela administração, com verificação de conflitos de horário (profissional e paciente, inclusive **limite diário** e **pausas**), bloqueio de datas passadas, fluxo de confirmação pelo médico e **reagendamento** pelo paciente (limitado a 2 por consulta).
- **Triagem** — envio de triagem pelo paciente (sem auto-classificação) já dispara uma **avaliação prévia da IA**, que classifica o risco, indica o perfil (médico ou enfermeiro) e **nomeia o profissional concreto** com base na **especialidade** e no **tempo de experiência** cadastrados. A triagem chega ao **técnico de enfermagem** com essa recomendação, que confirma, corrige ou recusa antes de **encaminhar ao profissional escolhido**; o médico/enfermeiro de destino valida a classificação final (vermelho / amarelo / verde).
- **Feedback do paciente** — após o término da consulta o paciente avalia o profissional com **0 a 5 estrelas** e um comentário opcional. Os resultados ficam visíveis **exclusivamente para o administrador**.
- **Atendimento e prontuário eletrônico** — questionário guiado por protocolos clínicos, sinais vitais, anamnese, diagnóstico final e conduta médica; o prontuário registra as visualizações. Abertura da visita muda o status para "em andamento"; o profissional pode marcar o paciente como **ausente**.
- **Inteligência artificial** — análise clínica preliminar gerada por IA (Groq ou OpenAI) com **limite de 5 consultas por dia por usuário** e aviso explícito de que aguarda validação do médico. O diagnóstico final é sempre confirmado por um profissional.
- **Painel administrativo** — indicadores (KPIs), escala de visitas do dia e relatórios em PDF (agenda, triagens e prontuários).
- **Auditoria** — registro de todas as ações relevantes (login/logout, usuários, agendamentos, prontuários, uso de IA, recuperação de senha), com telas de consulta, monitoramento e exportação em PDF.- **Dashboards por perfil** — visões específicas para médico, enfermeiro, técnico de enfermagem, paciente e administrador.

## Perfis de acesso

| Perfil | Acessa | Observação |
|---|---|---|
| `admin` | `/painel` | Criado apenas pela administração; não aparece no autocadastro |
| `medico` | `/medico` | Confirma agendamentos, atende, preenche prontuário, valida análise de IA |
| `enfermeiro` | `/enfermeiro` | Agenda própria, histórico, triagens encaminhadas |
| `tecnico_enfermagem` | `/tecnicoenfermagem` | Analisa e encaminha triagens, agenda própria, histórico |
| `paciente` | `/paciente` | Solicita agendamento, envia triagem, acompanha histórico e linha do tempo |

## Tecnologias

| Camada | Escolha |
|---|---|
| Linguagem | PHP (desenvolvimento em 8.2; código compatível com 7.4+) |
| Servidor | XAMPP / Apache (porta 8080 no ambiente de desenvolvimento) |
| Banco de dados | MySQL / MariaDB com PDO (`ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `utf8mb4`) |
| Frontend | Bootstrap 5.3.3 (CDN), Bootstrap Icons, Google Fonts Inter, CSS próprio |
| PDF | dompdf 3.1 |
| E-mail | PHPMailer 7.1 (SMTP Gmail) |
| APIs externas | Google OAuth 2.0, Groq (`openai/gpt-oss-20b`) e OpenAI (`gpt-4o-mini`) — via cURL |
| Gerenciador de dependências | Composer |

## Requisitos

- PHP 7.4+ (recomendado 8.x) com extensões `pdo_mysql`, `curl`, `mbstring`, `openssl`, `fileinfo`
- MySQL ou MariaDB
- Composer
- Apache (ou servidor compatível com `.htaccess` / IIS com `web.config`)
- Acesso à internet para as APIs externas (Google, Groq/OpenAI) e envio de e-mail

## Instalação

1. Copie o projeto para o diretório web do servidor (ex.: `C:\xampp\htdocs\elomed`).
2. Crie o banco de dados `elomed` e importe o script:
   ```bash
   mysql -u root -p elomed < elomed.sql
   ```
   O script é **idempotente** — pode ser executado mais de uma vez sem causar erros (usar `CREATE ... IF NOT EXISTS` e `ALTER` condicionais). Ele cria as tabelas e popula dados de demonstração.
3. Instale as dependências:
   ```bash
   composer install
   ```
4. Configure o ambiente: copie/ajuste o arquivo `.env` (veja [Configuração (.env)](#configuração-env)).
5. Acesse a aplicação:
   ```
   http://localhost:8080/elomed/public/login/index
   ```
   O `BASE_URL` é montado automaticamente a partir do host da requisição; em produção, ajuste `Config::BASE_URL_FORÇADO` se necessário.

### Dica de hospedagem

Em produção, aponte o **document root** para a pasta `public/` — o front controller é `public/index.php` e todos os acessos passam por ele (o `.htaccess` da raiz bloqueia o acesso direto a `.env`, `elomed.sql`, `composer.*`, `logs/`, `vendor/`, `config/` e `app/`).

## Configuração (.env)

O arquivo `.env` fica na raiz do projeto e não deve ser versionado (está no `.gitignore`). Chaves disponíveis:

```env
# Banco de dados
DB_HOST, DB_NAME, DB_USER, DB_PASS

# Sessão
SESSION_NAME, SESSION_LIFETIME, SESSION_SECURE, SESSION_DOMAIN, SESSION_SAMESITE

# Inteligência artificial
IA_PROVIDER, GROQ_API_KEY, GROQ_MODEL, OPENAI_API_KEY

# Google OAuth 2.0
GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI

# E-mail (PHPMailer / SMTP Gmail)
MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION,
MAIL_FROM_NAME, MAIL_FROM_EMAIL

# Aplicação
APP_ENV, APP_DEBUG

# Cache
CACHE_ENABLED, CACHE_PREFIX, CACHE_LIFETIME, CACHE_DRIVER

# Logs
LOG_PATH, LOG_FILENAME, LOG_ENABLED, LOG_LEVEL, LOG_MAX_FILES

# Segurança
CSRF_TOKEN_LIFETIME, PASSWORD_MIN_LENGTH,
PASSWORD_REQUIRE_UPPER, PASSWORD_REQUIRE_NUMBER, PASSWORD_REQUIRE_SPECIAL
```

Observações:

- **Google**: o `GOOGLE_REDIRECT_URI` precisa estar cadastrado exatamente igual no Google Cloud Console. Ex.: `http://localhost:8080/elomed/public/login/google_callback`. Com `SESSION_SAMESITE=Strict` o callback do OAuth é bloqueado — use `Lax` ou a mesma origem.
- **IA**: sem chave configurada, o sistema usa um provedor simulado (`none`) para demonstração.
- **E-mail**: sem credenciais SMTP, em ambiente `APP_ENV=development` os links de recuperação de senha são exibidos na tela; em produção é exibida apenas uma mensagem genérica.

## Banco de dados

Banco `elomed`, MySQL/MariaDB, InnoDB, `utf8mb4`. Tabelas:

| Tabela | Finalidade |
|---|---|
| `tbl_usuario` | contas (e-mail único), perfil, status, `primeiro_acesso`, troca de senha |
| `tbl_profissional` | especialidade, registro (CRM), `tempo_experiencia_anos`, `limite_diario`, pausas, FK → usuário |
| `tbl_paciente` | cadastro completo (CPF único, documentos, endereço, convênio, foto, anamnese) |
| `tbl_agendamento` | agenda de visitas (datas, status, `vezes_reagendado`, `duracao_minutos`, justificativa, `fk_triagem` quando a visita nasce de uma triagem) |
| `tbl_visita` | espelho do agendamento usado no acompanhamento da visita |
| `tbl_triagem` | triagens com classificação de risco, avaliação prévia da IA (risco, perfil e profissional sugerido) e fluxo de aprovação |
| `tbl_atendimento` | execução da visita (início/fim, status, profissional) |
| `tbl_feedback` | avaliação do paciente após o atendimento concluído (0 a 5 estrelas, comentário) |
| `tbl_prontuario` | prontuário eletrônico (queixa, histórico, sinais vitais, análise IA, diagnóstico) |
| `tbl_prontuario_visualizacao` | trilha de quem visualizou o prontuário |
| `tbl_protocolos` | protocolos clínicos (crônico / agudo / investigação / acompanhamento) |
| `tbl_perguntas_protocolo` | questões dos protocolos (texto, número, textarea, select, radio, checkbox) |
| `tbl_auditoria` | log de auditoria (ação, descrição, propriedades em JSON, IP, data/hora) |
| `tbl_lembrete` | lembretes de consulta (simulados) |
| `tbl_uso_ia` | controle do limite diário de consultas de IA |
| `tbl_recuperacao_senha` | tokens de recuperação de senha (uso único, expiração) |
| `tbl_roles`, `tbl_permissions`, `tbl_role_permission`, `tbl_user_role` | RBAC (papéis, permissões, vínculos) |

## Credenciais de demonstração

| Perfil | E-mail | Senha |
|---|---|---|
| Admin | `admin@gmail.com` | `Admin@123` |
| Médico | `arnaldo.silva@elomed.com` | `EloMed@123` |
| Enfermeiro | `eduardo.souza@elomed.com` | `EloMed@123` |
| Téc. Enfermagem | `igor.carvalho@elomed.com` | `EloMed@123` |
| Paciente | `roberto.alencar@gmail.com` | `EloMed@123` |

> O comentário `all passwords are: 123456` no final do `elomed.sql` está **desatualizado**: os usuários de demonstração usam as senhas acima. Troque as credenciais e as chaves reais do `.env` antes de publicar.

## Estrutura do projeto

```
elomed/
├── public/                  # Ponto de entrada (document root sugerido)
│   ├── index.php            # Front controller (rotas amigáveis)
│   ├── .htaccess            # Reescrita: tudo para index.php
│   ├── web.config           # Equivalente para IIS
│   └── Assets/              # CSS, JS e imagens
├── app/
│   ├── config/              # Config.php, Constants.php, Env.php, Database.php, autoload.php
│   ├── controllers/         # 14 controllers (Login, Cadastro, Usuario, Paciente,
│   │                        #   Agendamento, Medico, Enfermeiro, TecnicoEnfermagem,
│   │                        #   Prontuario, IA, Painel, Auditoria, Perfil, Home)
│   ├── models/              # Usuario, Profissional, Paciente, Agendamento, Triagem,
│   │                        #   Prontuario, Protocolo, IA, Painel, Auditoria,
│   │                        #   RecuperacaoSenha, Visita, Model (base)
│   ├── middleware/          # Session (cookie seguro), CSRF, RBAC
│   ├── helpers/             # helpers.php (e()), Validator, Logger, Mailer, Pagination, WhatsAppService
│   ├── views/               # auth, home, cadastro, perfil, usuario, paciente, medico,
│   │                        #   enfermeiro, tecnico_enfermagem, painel, auditoria,
│   │                        #   header.php e footer.php
│   └── exceptions/          # AppException, DatabaseException
├── config/                  # Database.php e arquivos de chave de API (gitignored)
├── logs/                    # logs da aplicação (app.log)
├── vendor/                  # dependências do Composer
├── .env                     # configuração do ambiente (não versionar)
├── elomed.sql               # schema + dados de demonstração (script principal)
├── composer.json            # dompdf ^3.1, phpmailer ^7.1
├── docs/                    # arquivo histórico (docs e código obsoletos)
└── index.php                # entrada legada (redireciona para public/)
```

## Rotas principais

Rotas amigáveis no formato `/controller/action/parametros`. O controller é resolvido por correspondência de nome (ex.: `tecnicoenfermagem` → `TecnicoEnfermagemController`).

| Rota | Descrição | Acesso |
|---|---|---|
| `/home/index` | Página inicial pública (landing) | público |
| `/login/index` | Tela de login | público |
| `/login/recuperar` / `/login/redefinir/{token}` | Recuperação de senha | público |
| `/login/google`, `/login/google_callback`, `/login/google_cadastrar` | Login com Google | público |
| `/login/escolher_perfil` | Seletor de perfil pós-login | logado |
| `/cadastro/index` | Autocadastro (perfis públicos) | público |
| `/painel/index` | Painel administrativo + KPIs | admin |
| `/painel/feedback` | Avaliações dos pacientes (visível **apenas** para admin) | `feedback_visualizar` |
| `/painel/agenda_pdf`, `/painel/triagens_pdf`, `/painel/prontuarios_pdf` | Relatórios em PDF | admin |
| `/usuario/index` | Gestão de usuários | `usuario_gerenciar` |
| `/paciente/index` | Dashboard do paciente | paciente |
| `/paciente/agendamento` | Solicitar agendamento | paciente |
| `/paciente/historico` | Histórico de visitas + avaliação (0 a 5 estrelas) | paciente |
| `/paciente/avaliar_atendimento/{id}` | Registra a nota e o comentário do atendimento | `feedback_registrar` |
| `/medico/pendencias` | Confirmar/rejeitar agendamentos, validar as triagens encaminhadas pela equipe e atribuir consulta à equipe | `medico_pendencias` |
| `/medico/aprovar_triagem/{id}`, `/medico/rejeitar_triagem/{id}` | Aprovar/recusar triagem com classificação final | `triagem_aprovar` |
| `/medico/iniciar_atendimento/{id}` | Atendimento + prontuário | `atendimento_iniciar` |
| `/medico/diagnostico_ia` | Análise preliminar por IA | `diagnostico_ia` |
| `/medico/historico_visitas` | Histórico com diagnóstico final | médico |
| `/tecnicoenfermagem/triagens` | Fila de triagens com a avaliação prévia da IA; define classificação, destino, **data e horário da visita** e encaminha (virando agendamento) | `triagem_analisar` |
| `/tecnicoenfermagem/encaminhar/{id}`, `/tecnicoenfermagem/recusar_triagem/{id}` | Encaminhar (criando o agendamento) ou recusar triagem após análise | `triagem_analisar` |
| `/enfermeiro/minha_agenda`, `/tecnicoenfermagem/minha_agenda` | Agendas da equipe | respectivos perfis |
| `/auditoria/index` | Registros de auditoria | `auditoria_visualizar` |
| `/auditoria/monitoramento`, `/auditoria/pdf` | Monitoramento e exportação | `auditoria_monitorar` |

## Regras de negócio

- **Agendamento** — horário obrigatório com `fim > início`; não são aceitas **datas passadas**; há checagem de **conflito de horário** tanto para o profissional quanto para o paciente; novo agendamento nasce com status `pendente` e só vira `agendado` após confirmação do médico. Cancelamento grava justificativa (permitido a partir dos status `pendente`/`agendado`). O limite diário de visitas do profissional (`limite_diario`) e as **pausas** programadas são verificados na solicitação; o paciente pode **reagendar** consultas `pendente`/`agendado` (máximo `AGENDAMENTO_MAX_REAGENDAMENTOS` = 2).
- **Atendimento** — ao abrir a tela de atendimento (botão "Iniciar Visita") o status muda de `agendado` para `em_andamento`; existe o botão **"Marcar Ausente"** (apenas para consultas confirmadas, muda para `ausente`); concluir o prontuário reabre a visita em `concluido`.
- **Triagem** — enviada pelo paciente (queixa, sintomas e data de início) sem auto-classificação. No momento da solicitação a **IA gera uma avaliação prévia** (`ia_*` em `tbl_triagem`): classifica o risco, indica o perfil (médico ou enfermeiro) e **nomeia o profissional concreto**, considerando a **especialidade** e o **tempo de experiência** (`tbl_profissional.tempo_experiencia_anos`) de cada profissional ativo, além da carga de visitas do dia. A triagem chega ao **técnico de enfermagem** já com essa recomendação; ele confirma, corrige ou recusa, define a classificação final e o destino. Nada é automático: se a IA falhar, a triagem entra na fila sem recomendação e o técnico classifica manualmente. O médico/enfermeiro que recebeu o encaminhamento aprova ou recusa a triagem com a classificação final. No agendamento, a **IA sugere a classificação (pulseira), o profissional indicado e a justificativa**; o médico pode **alterar a classificação manualmente** e **atribuir a consulta ao enfermeiro/técnico de enfermagem**. Aprovar a consulta também aprova a triagem pendente do paciente (com a classificação final).
- **Triagem gera agendamento** — o encaminhamento feito pelo **técnico de enfermagem** vira um **agendamento** em nome do paciente: ele informa **data, horário de início e horário de fim** da visita, e a triagem é vinculada ao agendamento por `tbl_agendamento.fk_triagem` (UNIQUE — no máximo uma visita por triagem). Criação e encaminhamento ocorrem na **mesma transação**, com as mesmas validações do agendamento comum (data não-passada, `fim > início`, conflito do profissional e do paciente, `limite_diario` e pausas). A visita nasce com status `pendente` e é confirmada por quem recebeu a triagem: **aprovar a triagem agenda a visita** (`pendente → agendado`, com lembrete) e **recusar a triagem cancela a visita** (`pendente → cancelado`, com justificativa). O paciente **não** escolhe data/horário nesse fluxo — o agendamento de origem de triagem não aparece com o botão de reagendar em `/paciente/agendamento`, e o POST é bloqueado no servidor. O fluxo **"Solicitar Agendamento" do paciente permanece intacto**: nele o paciente continua escolhendo o profissional, a data e o horário, e pode reagendar.
- **Ordenação de encaminhamento** — a lista de profissionais do técnico é ordenada por pontuação: **aderência da especialidade à queixa** (peso maior), **tempo de experiência** (limitado a 20 anos) e **capacidade restante no dia**. A ordenação é apenas uma sugestão de ordem; o técnico escolhe quem recebe.
- **Feedback** — o paciente avalia o atendimento **concluído** com nota de **0 a 5 estrelas** e comentário opcional (até 500 caracteres), pelo botão *Avaliar* em `/paciente/historico`. Uma avaliação por atendimento (`uq_feedback_atendimento`), nota validada de 0 a 5 e `CHECK (nota BETWEEN 0 AND 5)`. **A leitura é exclusiva do administrador** (permissão `feedback_visualizar`, concedida apenas ao papel `admin`): o profissional avaliado, os demais perfis e o próprio paciente **não** têm acesso à tela nem aos resultados.
- **Prontuário** — só é finalizado (status `concluido`) após o médico salvar o diagnóstico final e a conduta; as visualizações ficam registradas.
- **IA** — limite de **5 consultas por dia por usuário** para análises manuais; a resposta é sempre marcada como análise preliminar aguardando validação médica. A análise de triagem/encaminhamento é **gerada automaticamente** a cada solicitação de agendamento (sem consumir o limite do usuário) e fica à espera do `override` do médico. A avaliação prévia da triagem roda com **timeout de 10s** e nunca bloqueia a criação da triagem: falhas são registradas em log e a triagem segue sem recomendação.
- **Primeiro acesso** — contas com `primeiro_acesso = 1` são obrigadas a definir uma nova senha antes de usar o sistema (o front controller redireciona qualquer outra página para `perfil/trocar_senha`).
- **Login** — bloqueio temporário de **5 minutos após 5 tentativas** inválidas (contadores por sessão) e e-mail normalizado com `trim`.
- **Usuários (admin)** — o admin não pode **desativar a própria conta** nem **remover/alterar o último administrador ativo**; usuários desativados aparecem na listagem e podem ser **reativados** (restaurando o papel RBAC); o cadastro de profissional exige perfil `medico`/`enfermeiro`/`tecnico_enfermagem` e a ficha de paciente exige perfil `paciente`; troca de senha na edição exige confirmação.
- **Valores declarados no schema (a implementar ou parcial):** validade de 30 dias da triagem aprovada.

## Segurança e auditoria

- Senhas com `password_hash`/`password_verify` (`PASSWORD_DEFAULT`).
- **CSRF** em toda requisição POST (token de 32 bytes, comparação com `hash_equals`, validação por campo `csrf_token` ou cabeçalho `X-CSRF-TOKEN`).
- **Sessão** com cookie `httponly`, `SameSite`, `Secure` configurável, nome customizado (padrão `elomed_session`) e **regeneração de ID a cada 30 minutos** e no login.
- **RBAC**: papéis e permissões em tabelas; todos os controllers protegidos verificam login + permissão no construtor.
- **Front controller** valida os segmentos da URL (somente `[a-zA-Z0-9_-]`), bloqueia acesso direto a arquivos sensíveis e registra exceções em `logs/app.log`.
- **Auditoria**: as ações relevantes são gravadas em `tbl_auditoria` (ação, descrição, propriedades em JSON, IP, data/hora) e também no log da aplicação.

## Limitações e itens em desenvolvimento

Pontos já mapeados no código, aguardando implementação/ajuste:

1. **E-mail SMTP** — o envio real depende de credenciais configuradas no `.env` (Gmail requer 2FA + senha de app). Em ambiente de desenvolvimento, o link de recuperação é exibido diretamente na tela.
2. **Validade da triagem (30 dias)** — a triagem aprovada não expira automaticamente após 30 dias.

> **Itens já corrigidos nesta rodada de manutenção:** filtros da auditoria (`propriedades`/`data_hora`), escala do dia (`Painel::getEscalaDoDia` com `WHERE` de data), fluxo de `primeiro_acesso`, bloqueio de login por tentativas, hash de tokens de recuperação (armazenados como `sha256`), logout via POST+CSRF, transição `agendado → em_andamento` no atendimento, status `ausente`, reagendamento de consultas (`vezes_reagendado`), aplicação de `limite_diario` e pausas no agendamento, criação de agendamento por admin com seletor de paciente (`fk_usuario`), proteções de administrador (auto-desativação e último admin), reativação de usuários e feedback neutro de e-mail na tela de login (anti-enumeração).

---

Projeto acadêmico — Sistema de Gestão de Atendimento Domiciliar **EloMed**.