# EloMed — Explicação do Código

Documento que descreve, pasta por pasta e arquivo por arquivo, todo o código do projeto **EloMed** (exceto `vendor/`, explicado de forma geral no fim).

---

## 1. Visão geral da arquitetura

O EloMed é um **MVC artesanal em PHP 8** (sem framework). O fluxo de uma requisição é:

1. `public/.htaccess` reescreve qualquer URL para `public/index.php?url=...`.
2. `public/index.php` (front controller) carrega config/autoload/helpers, inicia a sessão, quebra a URL em `controller/método/params` e procura dinamicamente um arquivo `*Controller.php` (via `glob`), instanciando a classe e chamando o método **público** via `ReflectionMethod` (isso impede que métodos privados sejam chamados pela URL).
3. O controller usa **models** (PDO), **middleware** (`Session`, `RBAC`, `CSRF`), **helpers** e faz `require` de uma **view**.
4. Os nomes de rota são `snake_case` e batem com o nome do método (ex.: `tecnicoenfermagem/triagens` → `TecnicoEnfermagemController::triagens`).

**Padrões transversais:**
- Autenticação/perfil via **sessão** (`Session`) + **RBAC** por permissões (tabelas `tbl_roles`, `tbl_permissions`...).
- Feedback ao usuário **sempre** por *toasts* (`flash_toasts()` + `toasts.js`).
- Proteção CSRF em todos os POSTs (`CSRF::getField()` / `CSRF::validateRequest()`).
- Escape de saída com `e()`.

---

## 2. Árvore de diretórios (resumida)

```
elomed/
├── index.php                  # entry point legado (redireciona p/ public/)
├── .htaccess                  # bloqueio de arquivos/pastas sensíveis (Apache)
├── .env                       # variáveis de ambiente (não versionado)
├── .gitignore
├── composer.json / composer.lock
├── README.md
├── elomed.sql                 # script principal (schema + dados demo)
├── app/
│   ├── .htaccess              # Require all denied
│   ├── config/                # autoload, Config, Constants, Env
│   ├── controllers/           # regra de negócio (16 controllers + ErroController)
│   ├── exceptions/            # AppException, DatabaseException
│   ├── helpers/               # helpers.php, flash_toasts, Logger, Mailer, Pagination, Validator, WhatsAppService
│   ├── middleware/            # Session, CSRF, RBAC
│   ├── models/                # acesso a dados (PDO)
│   └── views/                 # telas (HTML+PHP)
├── config/
│   ├── Database.php           # singleton PDO
│   ├── api_key.php            # chave OpenAI
│   └── groq_api_key.php       # chave/modelo Groq
├── logs/                      # app.log (gerado em runtime)
├── public/                    # raiz web
│   ├── index.php              # FRONT CONTROLLER
│   ├── .htaccess / web.config
│   └── Assets/                # css, js, images
├── sql/                       # migrações 002..007 + seed_demo.php
└── vendor/                    # dependências do Composer (gerado)
```

---

## 3. Raiz do projeto

| Arquivo | Descrição |
|---|---|
| **`index.php`** | Entry point legado; apenas redireciona para `public/index.php`. |
| **`.htaccess`** | Segurança (Apache): desativa listagem de diretórios e devolve 403 para `.env`, `.gitignore`, `composer.json/lock`, arquivos `.log/.sql/.md/.txt/.bak/.cache` e pastas `logs/`, `vendor/`, `config/`, `app/`. |
| **`.env`** | Variáveis de ambiente (não versionado): credenciais MySQL, sessão, chave/modelo GROQ, OAuth Google, SMTP, WhatsApp (Evolution API), `APP_DEBUG`, `ALLOWED_HOSTS`. |
| **`.gitignore`** | Exclui `.env`, logs, cache, uploads/fotos, `config/*_api_key.php`, `vendor/`, `composer.lock`, arquivos de IDE/OS. |
| **`composer.json`** | Declara as dependências: `dompdf/dompdf ^3.1` (PDF) e `phpmailer/phpmailer ^7.1` (e-mail). |
| **`composer.lock`** | Trava as versões exatas e subdependências resolvidas (também ignorado no Git). |
| **`README.md`** | Documentação do projeto (árvore, instalação, credenciais demo, migrações). |
| **`elomed.sql`** | Script principal completo: cria o banco `elomed`, as 28 tabelas e insere todos os dados de demonstração. |
| **`logs/app.log`** | Log gerado pelo `Logger` (rotacionado a cada 10 MB, até 30 arquivos). |

---

## 4. `public/` (raiz web)

- **`public/index.php` — FRONT CONTROLLER.** Ponto único de entrada. Carrega `Config`, `autoload` e `helpers`; registra um *handler* global de exceções (renderiza página de erro Bootstrap; só revela a mensagem crua/`SQL` se `APP_DEBUG=true`); inicia a sessão; lê `$_GET['url']`, valida cada segmento (só `a-zA-Z0-9_-`); localiza o controller via `glob('*Controller.php')` (aceita classes compostas como `TecnicoEnfermagem`); aplica o **bloqueio de troca de senha obrigatória** (usuário só acessa `perfil/trocar_senha` até trocar); e despacha o método **público** por `ReflectionMethod` (senão `ErroController::erro404()`).
- **`public/.htaccess`** — rewrite de URLs amigáveis para `index.php?url=$1 [QSA,L]`.
- **`public/web.config`** — mesma regra de rewrite para **IIS** (não usada no XAMPP/Apache; alternativa de ambiente).
- **`public/Assets/`** — front-end (detalhado na seção 8).

---

## 5. `app/config/` (núcleo de configuração)

- **`autoload.php`** — `spl_autoload_register` simples: ao encontrar uma classe, procura `NomeDaClasse.php` em `app/config`, `controllers`, `models`, `middleware`, `helpers`, `exceptions` e na raiz `config/` (onde fica `Database`).
- **`Config.php`** — define `BASE_URL` (com validação de host contra `ALLOWED_HOSTS`, evitando *host header injection*), `APP_NAME` e timezone `America/Sao_Paulo`; classe `Config::get()` expõe seções (`session`, `security`, `cache`, `logging`) e chaves do `.env`.
- **`Constants.php`** — fonte única de constantes de domínio: perfis, status (usuário/agendamento/triagem/visita), categorias/tipos de protocolo, ações de auditoria, limites de paginação/cache, proteção anti brute-force, **slugs de permissão RBAC** (`PERM_*`) e constantes do prontuário (`PRONTUARIO_SECAO_*`, `PRONTUARIO_STATUS_*`).
- **`Env.php`** — leitor manual de `.env` (sem dependência externa): `load()` faz o parse e não sobrescreve variáveis já definidas; `get($chave, $default)` lê via `getenv` (valor vazio = "não configurado").

---

## 6. `config/` (raiz, fora de `app/`)

- **`Database.php`** — singleton de conexão **PDO MySQL** (UTF-8mb4, `ERRMODE_EXCEPTION`, fetch associativo). Ajusta `SET time_zone` da sessão igual ao offset do PHP; em falha, loga e devolve HTTP 500.
- **`api_key.php`** — retorna a chave da OpenAI (`getenv('OPENAI_API_KEY')`); se vazia, a IA cai em modo **simulação**.
- **`groq_api_key.php`** — retorna `groq_api_key` (`GROQ_API_KEY`) e `model` (`GROQ_MODEL`, padrão `openai/gpt-oss-20b`).

---

## 7. `app/` — código da aplicação

### 7.1 `app/middleware/`

- **`Session.php`** — inicia a sessão com cookies `httponly/samesite/secure`, regenera o ID a cada 30 min (anti fixação de sessão) e oferece `set/get/has/remove`, sistema **flash** (`flash/getFlash/peekFlash/forgetFlash`) e a fila de **`toastsFlash()`/`limparFlash()`**.
- **`CSRF.php`** — gera token de 64 hex, valida com `hash_equals` e expiração; `getField()` imprime o `<input hidden>`; `validateRequest()` aceita o token do POST ou do header `X-CSRF-Token` (só valida em POST).
- **`RBAC.php`** — controle de acesso por papel: lê `tbl_user_role`, `tbl_roles`, `tbl_role_permission`, `tbl_permissions`; **cacheia papéis/permissões na sessão** (`carregarSessao()`); `hasRole()`/`can()`; e `atribuirRole()`/`removerRoles()`/`redefinirRole()` para sincronizar com o perfil. Como cacheia, mudanças de permissão exigem **relogar**.

### 7.2 `app/helpers/`

- **`helpers.php`** — função global `e()` = `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` (escape XSS padrão do template).
- **`flash_toasts.php`** — função `flash_toasts()`: lê a fila de flash da sessão, limpa e emite `<script>` chamando `showToast()` (com JSON escapado anti-XSS). É o **único** caminho de feedback.
- **`Logger.php`** — log em arquivo (`debug/info/warning/error/critical`) + `audit()` (anexa usuário, IP, user-agent, timestamp); sanitiza quebras de linha (anti log injection) e rotaciona o arquivo.
- **`Mailer.php`** — fachada do PHPMailer: `Mailer::enviar($para, $assunto, $html, $texto)`; valida SMTP, configura TLS/SSL, UTF-8 e retorna bool.
- **`Pagination.php`** — paginação (offset/limit até 100/página, anterior/próxima, janela de páginas); `render()` inclui `views/components/pagination.php`.
- **`Validator.php`** — validação encadeável: `required/email/min/max/numeric/integer/in/matches/password/date` e **`cpf()`** (valida dígitos verificadores) e `dateNascimento()`.
- **`WhatsAppService.php`** — notificações via Evolution API (cURL); **best-effort** (nunca lança exceção, apenas loga); `enviarConfirmacaoAgendamento()`, `enviarAvisoReagendamento()`, `telefoneDoUsuario()`.

### 7.3 `app/exceptions/`

- **`AppException.php`** — exceção base com array `$context` extra para log.
- **`DatabaseException.php`** — específica de banco; guarda o SQL causador; a mensagem **só é exposta** quando `APP_DEBUG=true` (evita vazar SQL).

### 7.4 `app/models/` (acesso a dados — todos herdam de `Model`)

- **`Model.php`** — classe base abstrata: conexão PDO (`$db`), `find()/all()` (filtrando `status='ativo'`), `insert/update/delete` parametrizados, `likeEscape()` e consultas genéricas de usuários/profissionais.
- **`Usuario.php`** — `tbl_usuario`; autenticação (`verificarLogin()` com `password_verify`, exige `status='ativo'`), `criar/atualizar/desativar/reativar/trocarSenha`, `criarGoogle()` e `COLUNA_PERFIL` (subquery que deriva o perfil via RBAC, pois **não há coluna `perfil`**). Busca paciente por nome/CPF.
- **`Paciente.php`** — `tbl_paciente` + tabelas complementares (telefone, endereço, contato de emergência, convênio, anamnese); `criar/atualizar` sincronizam tudo; gere triagens, agendamentos, visitas, lembretes e a linha do tempo.
- **`Profissional.php`** — `tbl_profissional`; CRUD, **ranking/encaminhamento de triagem** (especialidade + experiência + carga) e agendas/filas de atendimento.
- **`Agendamento.php`** — `tbl_agendamento`; criação/reagendamento com checagem de conflito e pausa, mudança de status espelhada em `tbl_atendimento` (`sincronizarVisita`), geração de lembretes e ponte triagem↔visita.
- **`Triagem.php`** — `tbl_triagem`; sobrescreve `all()/find()` (a tabela não tem `status='ativo'`) e `getAgendamento()`.
- **`Prontuario.php`** — `tbl_prontuario` ligado ao atendimento; `criar/atualizar/atualizarDiagnosticoFinal`; resolve/cria o atendimento ("mirror") a partir do agendamento.
- **`ProntuarioRegistro.php`** — `tbl_prontuario_registro` (**append-only** por seção: anamnese/evolução/complementar); rascunho editável só pelo autor; finalizado é imutável; `timeline()` para PDF.
- **`Protocolo.php`** — `tbl_protocolos` + `tbl_perguntas_protocolo`; perguntas com opções/condicionais (JSON).
- **`Feedback.php`** — `tbl_feedback`; avaliação (1 por atendimento, `INSERT IGNORE`), indicadores e médias por profissional.
- **`IA.php`** — integração Groq/OpenAI (cURL) com **fallback de simulação**; `analisarVisitaEnfermagem`, `avaliarTriagemComProfissionais` (escolhe profissional real); controle de uso em `tbl_uso_ia`.
- **`Painel.php`** — consultas agregadas do dashboard (estatísticas, escala do dia, triagens, prontuários).
- **`Auditoria.php`** — `tbl_auditoria`; `registrar()`, listagens com filtro, resumo de sessões/acessos.
- **`Endereco.php`** — `tbl_endereco`; `doUsuario()` e `salvar()` (upsert).
- **`Telefone.php`** — `tbl_telefone`; `substituir()` (apaga e reinsere, normalizando DDD+número).
- **`RecuperacaoSenha.php`** — `tbl_recuperacao_senha`; token aleatório com hash SHA-256, expiração e **uso único** (fluxo "esqueci minha senha").

### 7.5 `app/controllers/` (regra de negócio)

- **`HomeController.php`** — `index()`: se já logado redireciona ao painel do perfil (mapa de rotas por `Constants::PERFIL_*`); senão renderiza a **landing page pública** (`views/home/index.php`).
- **`LoginController.php`** — o maior. `index()` (tela `auth/index`), `logar()` (com **anti brute-force** `bloqueadoPorTentativas()`/`registrarFalhaLogin()`), `escolher_perfil()` (múltiplos perfis), `sair()`, `recuperar()`/`redefinir()` (senha) e todo o fluxo **Google OAuth** (`google`, `google_callback`, `google_cadastrar`).
- **`CadastroController.php`** — autosserviço público de cadastro (usado pela tela `auth/index`): `index()` e `cadastrar()`.
- **`AgendamentoController.php`** — `solicitar_agendamento()` (form `paciente/solicitar_agendamento`) e `enviar_dados()` (cria o agendamento; dispara `gerarAnaliseIaAgendamento()`).
- **`PacienteController.php`** — área do paciente: `index`, `completar_cadastro`/`salvar_completar_cadastro`, `minhas_triagens`/`processar_triagem` (abre triagem + avaliação IA inicial), `agendamento`, `reagendar_agendamento`, `historico`, `avaliar_atendimento` (feedback) e `linha_do_tempo`.
- **`MedicoController.php`** — `index`, `pendencias`, `minha_agenda`, `historico_visitas`, `aceitar`/`rejeitar` (decisão de agendamento por IA), `aprovar_triagem`/`rejeitar_triagem`, `iniciar_atendimento`, `get_perguntas_protocolo` (AJAX), `dados_atendimento` (grava anamnese + IA → `diagnostico_ia`) e `salvar_diagnostico_final`; privados `salvarProntuario` e `podeAgirNoAgendamento/NaTriagem` (autorização).
- **`EnfermeiroController.php`** — `pendencias`, `aprovar_triagem`/`recusar_triagem` (com `triagemPertenceAoEnfermeiro`), `index`, `minha_agenda`, `historico`.
- **`TecnicoEnfermagemController.php`** — `triagens` (é quem **define data/hora** e encaminha), `encaminhar` (com `minutosDoDia`), `recusar_triagem`, `index`, `minha_agenda`, `historico`.
- **`ProntuarioController.php`** — núcleo do prontuário: `buscar`, `dados($id)` (dados cadastrais + RBAC), `salvar_dados($id)` (valida CPF via `dbCpfEmUso`), `secao($id,$secao)`, `registrar($id)` (rascunho/finalizado), `editar_registro`/`salvar_registro`/`finalizar_registro`/`excluir_registro` (só rascunho do próprio autor — `ehMeuRascunho`); helpers `ehAjax`/`responderJson`/`registrarAuditoria`/`secoesValidas`.
- **`PainelController.php`** — dashboard admin: `index`, `feedback` e relatórios PDF (`relatorio_pdf_agenda`, `relatorio_pdf_triagens`, `relatorio_pdf_prontuarios` — usam DomPDF).
- **`UsuarioController.php`** — administração de usuários: `index`, `cadastrar`, `cadastrar_profissional`, `cadastrar_paciente`, `editar`, `excluir` (desativa), `reativar`, `procurar`/`procurar_paciente`/`listar`/`listarProfissionaisAtendendo` (busca/AJAX).
- **`PerfilController.php`** — perfil do usuário logado: `completar_cadastro` ("Meu Cadastro"), `salvar` (com `salvarFoto` e `perfisProfissionais`), `trocar_senha`.
- **`AuditoriaController.php`** — `index` (trilha), `monitoramento` (dashboard de acessos) e `pdf` (exporta trilha).
- **`EnderecoController.php`** — `consultar_cep($cep)`: endpoint **AJAX/JSON** de CEP (ViaCEP com fallback BrasilAPI); sem view.
- **`IAController.php`** — `gerar_diagnostico`: endpoint que chama a IA para o atendimento, validando que o usuário pode agir (`podeAgirNoAgendamento`).
- **`ErroController.php`** — apenas `static erro404()` (HTML simples "página não encontrada"); chamado pelo front controller.

### 7.6 `app/views/` (telas)

**Layout e componentes**
- **`header.php`** — layout base (sidebar + topo) de todas as telas internas: logo, menu por perfil com **badges de pendências**, avatar/usuário, modal de logout, carrega Bootstrap/Bootstrap Icons/Inter, scripts e `flash_toasts()`. Usa `$perfilMeta`, `$currentPage/$currentAction`, `RBAC`, `CSRF`.
- **`footer.php`** — rodapé simples (copyright 2026).
- **`components/pagination.php`** — componente de paginação reutilizável (usa `$pagination->toArray()`).
- **`pesquisa.php`** — formulário de filtros (ID/nome/e-mail/perfil) da listagem de usuários.
- **`home/index.php`** — landing page pública (hero, "como funciona", perfis, CTA).

**Auth (`auth/`)**
- **`index.php`** — tela **única** de login + cadastro (painel deslizante), com Google, validação de e-mail e toasts.
- **`escolher_perfil.php`** — escolha de perfil quando o usuário tem mais de um.
- **`google_perfil.php`** — escolha de perfil após login/cadastro via Google.
- **`recuperar.php`** / **`redefinir.php`** — solicitar link de recuperação / definir nova senha com token.

**Painel (`painel/`)**
- **`index.php`** — dashboard operacional (6 KPIs, escala de visitas, botões de PDF).
- **`feedback.php`** — avaliações dos pacientes (médias, distribuição, por profissional).
- **`pdf_agenda.php`**, **`pdf_triagens.php`**, **`pdf_prontuarios.php`** — templates HTML usados pelo DomPDF.

**Médico (`medico/`)**
- **`index.php`** — dashboard do médico (fila de hoje).
- **`pendencias.php`** — aprovar/rejeitar agendamentos e triagens (seletor de profissional, override de classificação).
- **`minha_agenda.php`** — agenda futura/passada com ações (iniciar visita, ausente).
- **`historico_visitas.php`** — visitas concluídas com diagnóstico/conduta.
- **`iniciar_atendimento.php`** — formulário clínico (protocolo/anamnese + sinais vitais).
- **`diagnostico_ia.php`** — validação da análise de IA + diagnóstico final.

**Enfermeiro (`enfermeiro/`)**
- **`index.php`**, **`minha_agenda.php`**, **`historico.php`** (somente leitura) e **`pendencias.php`** (aprovar/recusar triagens).

**Técnico de Enfermagem (`tecnico_enfermagem/`)**
- **`triagens.php`** — análise das triagens e **agendamento** (define data/hora/profissional) com sugestão da IA.
- **`index.php`**, **`minha_agenda.php`**, **`historico.php`** (somente leitura).

**Paciente (`paciente/`)**
- **`index.php`** — painel do paciente.
- **`solicitar_agendamento.php`** — solicitar agendamento (paciente ou admin).
- **`agendamento.php`** — "meus agendamentos" + reagendar (respeita `AGENDAMENTO_MAX_REAGENDAMENTOS`).
- **`minhas_triagens.php`** — abrir/acompanhar triagens.
- **`historico.php`** — visitas concluídas + **avaliar atendimento** (estrelas).
- **`linha_do_tempo.php`** — linha do tempo de eventos.
- **`completar_cadastro.php`** — ficha completa (foto, dados, endereço com CEP, contato, convênio, saúde).

**Perfil (`perfil/`)**
- **`completar_cadastro.php`** — "Meu Cadastro" de qualquer perfil (nome, e-mail, senha, especialidade/registro, foto).
- **`trocar_senha.php`** — tela de troca obrigatória/voluntária de senha.

**Usuários (`usuarios/`)**
- **`index.php`** — container de administração de usuários (inclui `pesquisa.php`, `content.php` e botões de cadastro).
- **`content.php`** — partial com a tabela de usuários (agrupada por perfil; ações editar/desativar/reativar).
- **`cadastrar.php`** — cadastro genérico de usuário.
- **`cadastrar_paciente.php`** — transforma um usuário existente em paciente (busca + ficha completa).
- **`cadastrar_profissional.php`** — transforma um usuário existente em profissional (busca + especialidade/registro/foto).
- **`editar.php`** — edição de usuário.

**Prontuário (`prontuario/`)**
- **`buscar.php`** — busca de paciente por nome/CPF.
- **`dados.php`** — aba de dados cadastrais (edição restrita por permissão).
- **`secao.php`** — abas de registros (Anamnese/Evolução/Complementar) com rascunho/finalizar e histórico.
- **`editar.php`** — edição de um rascunho de registro.

**Auditoria (`auditoria/`)**
- **`index.php`** — trilha de auditoria com filtros + PDF.
- **`monitoramento.php`** — dashboard de acessos/sessões/visitas.
- **`pdf.php`** — template do PDF da trilha.

---

## 8. `public/Assets/` (front-end)

- **`js/mascaras.js`** — máscaras de CPF/telefone/CEP e **validação de CPF no cliente** (mesmo algoritmo do back-end); no `submit` limpa as máscaras (envia só dígitos).
- **`js/buscar_cep.js`** — autocompleta endereço consultando `endereco/consultar_cep/{cep}`; mostra o resultado via `showToast`.
- **`js/select-profissional.js`** — melhora o `<select class="js-seletor-profissional">` para cartões com avatar, mantendo o select nativo (fallback sem JS).
- **`js/toasts.js`** — notificações flutuantes; usa `textContent` (nunca `innerHTML`) para evitar XSS.
- **`css/app.css`** — todo o **design system** sobre o Bootstrap (variáveis `--elomed-*`, sidebar escura, métricas, tabelas, badges de status, formulários, telas de auth, landing, seletor de profissional e responsividade).
- **`images/Logo_EloMed.png`** — logo (favicon e marca).
- **`images/usuario/`** — avatares enviados (padrão `{tipo}_{id}_{timestamp}.ext`) e `index.html` vazio (impede listagem do diretório).

---

## 9. `sql/`

- **`002_triagem_ia_e_feedback.sql`** — colunas de IA na triagem + tabela `tbl_feedback` + permissões de feedback.
- **`003_triagem_vira_agendamento.sql`** — coluna `fk_triagem` em `tbl_agendamento` (triagem → agendamento).
- **`004_prontuario_do_paciente.sql`** — cria `tbl_prontuario_registro` + permissões.
- **`005_corrige_schema_dessincronizado.sql`** — reconcilia um banco antigo com o schema atual.
- **`006_foto_profissional.sql`** — coluna `foto` em `tbl_profissional`.
- **`007_permissoes_prontuario_paciente.sql`** — completa as permissões do prontuário (sem ela o controller bloqueia tudo).
- **`seed_demo.php`** — semeador idempotente de dados de demonstração (roda via `php sql/seed_demo.php`).

> Como o `elomed.sql` já contém tudo (foi regenerado via `mysqldump`), as migrações **002–007 são histórico**; só são necessárias se o banco já existir de antes.

---

## 10. `vendor/` (visão geral)

Pasta gerada pelo **Composer** com as bibliotecas de terceiros (não versionada — recriada por `composer install`). Contém:

- **`dompdf/dompdf`** — geração dos PDFs (agenda, triagens, prontuários, auditoria).
- **`phpmailer/phpmailer`** — envio de e-mail, encapsulado pelo helper `Mailer`.

O `composer.lock` trava as versões exatas. Nenhum arquivo de `vendor/` é código do projeto — não precisa ser mantido manualmente.

---

## 11. Credenciais e ambiente (demo / XAMPP)

- **Médico:** `arnaldo.silva@elomed.com` / `EloMed@123`
- **Paciente:** `teste.whatsapp@elomed.com` / `EloMed@123`
- **Banco:** MySQL/MariaDB em `localhost:3306`, usuário `root` sem senha, base **`elomed`**.
- **App:** `http://localhost/elomed/public` (Apache na porta 80).
