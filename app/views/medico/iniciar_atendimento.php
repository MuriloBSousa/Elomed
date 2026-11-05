<?php
Session::start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Consulta Médica - EloMed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/Assets/css/app.css">
    <style>
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--elomed-primary);
            border-bottom: 2px solid #eef2f7;
            padding-bottom: .5rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .medication-block {
            background: #f8fafc;
            border: 1px dashed var(--elomed-primary);
            border-radius: .6rem;
            padding: 1rem;
            position: relative;
            margin-bottom: .75rem;
        }
        .btn-remove {
            position: absolute;
            top: .5rem;
            right: .6rem;
            background: none;
            border: none;
            color: var(--elomed-danger);
            font-weight: 600;
            font-size: .8rem;
        }
    </style>
    <script>
    function loadProtocolQuestions() {
        const protocolId = document.getElementById('protocolo_id').value;
        const questionsFieldset = document.getElementById('perguntas_protocolo_fieldset');
        const anamneseFieldset = document.getElementById('anamnese_fieldset');
        const questionsContainer = document.getElementById('perguntas_container');

        if (!protocolId) {
            questionsFieldset.style.display = 'none';
            anamneseFieldset.style.display = 'block';
            questionsContainer.innerHTML = '';
            return;
        }

        fetch(`<?= BASE_URL ?>/medico/get_perguntas_protocolo/${protocolId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.perguntas.length > 0) {
                    questionsFieldset.style.display = 'block';
                    anamneseFieldset.style.display = 'none';
                    renderQuestions(data.perguntas);
                } else {
                    questionsFieldset.style.display = 'none';
                    anamneseFieldset.style.display = 'block';
                    questionsContainer.innerHTML = '';
                }
            })
            .catch(error => {
                console.error('Error loading questions:', error);
                questionsFieldset.style.display = 'none';
                anamneseFieldset.style.display = 'block';
            });
    }

    function renderQuestions(questions) {
        const container = document.getElementById('perguntas_container');
        container.innerHTML = '';

        questions.forEach(question => {
            const colSpan = question.tipo === 'textarea' ? 'col-md-12' : 'col-md-6';
            const div = document.createElement('div');
            div.className = `mb-3 ${colSpan}`;

            const label = document.createElement('label');
            label.className = 'form-label';
            label.textContent = question.pergunta;
            if (question.obrigatorio) {
                label.textContent += ' *';
            }
            div.appendChild(label);

            let input;

            switch(question.tipo) {
                case 'texto':
                    input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'form-control';
                    input.name = `pergunta_${question.id}`;
                    input.placeholder = question.placeholder || '';
                    if (question.obrigatorio) input.required = true;
                    break;

                case 'numero':
                    input = document.createElement('input');
                    input.type = 'number';
                    input.className = 'form-control';
                    input.name = `pergunta_${question.id}`;
                    input.placeholder = question.placeholder || '';
                    if (question.obrigatorio) input.required = true;
                    break;

                case 'textarea':
                    input = document.createElement('textarea');
                    input.className = 'form-control';
                    input.name = `pergunta_${question.id}`;
                    input.rows = 3;
                    input.placeholder = question.placeholder || '';
                    if (question.obrigatorio) input.required = true;
                    break;

                case 'select':
                    input = document.createElement('select');
                    input.className = 'form-select';
                    input.name = `pergunta_${question.id}`;
                    if (question.obrigatorio) input.required = true;

                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = '-- Selecione --';
                    input.appendChild(defaultOption);

                    if (question.opcoes && Array.isArray(question.opcoes)) {
                        question.opcoes.forEach(optionVal => {
                            const option = document.createElement('option');
                            option.value = optionVal;
                            option.textContent = optionVal;
                            input.appendChild(option);
                        });
                    }
                    break;

                case 'radio':
                    let radioCounter = 0;
                    if (question.opcoes && Array.isArray(question.opcoes)) {
                        question.opcoes.forEach((optionVal, index) => {
                            const wrapper = document.createElement('div');
                            wrapper.className = 'form-check';

                            const radio = document.createElement('input');
                            radio.type = 'radio';
                            radio.className = 'form-check-input';
                            radio.name = `pergunta_${question.id}`;
                            radio.value = optionVal;
                            radio.id = `pergunta_${question.id}_${index}`;
                            if (index === 0 && question.obrigatorio) radio.required = true;

                            const lbl = document.createElement('label');
                            lbl.className = 'form-check-label';
                            lbl.htmlFor = radio.id;
                            lbl.textContent = ' ' + optionVal;

                            wrapper.appendChild(radio);
                            wrapper.appendChild(lbl);
                            div.appendChild(wrapper);
                            radioCounter++;
                        });
                    }
                    if (!radioCounter) div.remove();
                    container.appendChild(div);
                    return;

                case 'checkbox':
                    if (question.opcoes && Array.isArray(question.opcoes)) {
                        question.opcoes.forEach(optionVal => {
                            const wrapper = document.createElement('div');
                            wrapper.className = 'form-check';

                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.className = 'form-check-input';
                            checkbox.name = `pergunta_${question.id}[]`;
                            checkbox.value = optionVal;
                            checkbox.id = `pergunta_${question.id}_${optionVal}`;

                            const lbl = document.createElement('label');
                            lbl.className = 'form-check-label';
                            lbl.htmlFor = checkbox.id;
                            lbl.textContent = ' ' + optionVal;

                            wrapper.appendChild(checkbox);
                            wrapper.appendChild(lbl);
                            div.appendChild(wrapper);
                        });
                    }
                    container.appendChild(div);
                    return;
            }

            if (input) {
                div.appendChild(input);
            }
            container.appendChild(div);
        });
    }
    </script>
</head>
<body>
    <div class="container py-4">
        <div class="table-container">
            <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4"
                 style="border-color:#bae6fd;background:#f0f9ff;">
                <div>
                    <div class="fw-bold">
                        <i class="bi bi-person-square me-1"></i>
                        <?= e($agendamento['fk_paciente'] ? 'Paciente registrado' : 'Paciente') ?>
                        <?php if (!empty($agendamento['data_agendamento'])): ?>
                            <span class="text-muted fw-normal">— <?= e(date('d/m/Y', strtotime($agendamento['data_agendamento']))) ?>
                            <?= e(substr($agendamento['horario_inicio'], 0, 5)) ?> às <?= e(substr($agendamento['horario_fim'], 0, 5)) ?></span>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted">Atendimento #<?= (int) $id_agendamento ?> · Status:
                        <span class="status-table <?= e($agendamento['status'] ?? '') ?>"><?= ucfirst(e($agendamento['status'] ?? '')) ?></span>
                    </small>
                </div>
            </div>

            <div class="text-center mb-4">
                <h1 class="h4 fw-bold">Atendimento Clínico e Anamnese</h1>
                <p class="text-muted mb-0">Formulário de triagem diagnóstica e prescrição de medicamentos</p>
            </div>

            <form action="<?= BASE_URL ?>/medico/dados_atendimento" method="POST">
                <?= CSRF::getField() ?>
                <input type="hidden" name="id_agendamento" value="<?= (int)($id_agendamento ?? 0) ?>">

                <div class="mb-4">
                    <h2 class="section-title"><i class="bi bi-tag"></i> Propósito do Atendimento</h2>
                    <label for="protocolo_id" class="form-label">Selecione o propósito ou condição do atendimento:</label>
                    <select id="protocolo_id" name="protocolo_id" class="form-select" onchange="loadProtocolQuestions()">
                        <option value="">-- Selecione o propósito --</option>
                        <?php foreach ($protocolos as $proto): ?>
                            <option value="<?= (int)$proto['id'] ?>" data-categoria="<?= e($proto['categoria']) ?>">
                                <?= e($proto['nome']) ?>
                                <?php if ($proto['categoria'] === 'investigacao'): ?>
                                    (Não sei o diagnóstico)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Selecione "Investigação Diagnóstica" se não souber a condição do paciente.</div>
                </div>

                <div class="mb-4">
                    <h2 class="section-title"><i class="bi bi-activity"></i> Triagem e Sinais Vitais</h2>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="pa" class="form-label">Pressão Arterial (PA)</label>
                            <input type="text" class="form-control" id="pa" name="pa" placeholder="Ex: 120/80 mmHg">
                        </div>
                        <div class="col-md-3">
                            <label for="fc" class="form-label">Frequência Cardíaca</label>
                            <input type="number" class="form-control" id="fc" name="fc" placeholder="bpm">
                        </div>
                        <div class="col-md-3">
                            <label for="temperatura" class="form-label">Temperatura</label>
                            <input type="text" class="form-control" id="temperatura" name="temperatura" placeholder="°C">
                        </div>
                        <div class="col-md-3">
                            <label for="glicemia" class="form-label">Glicemia Capilar</label>
                            <input type="number" class="form-control" id="glicemia" name="glicemia" placeholder="mg/dL">
                        </div>
                    </div>
                </div>

                <div class="mb-4" id="perguntas_protocolo_fieldset" style="display: none;">
                    <h2 class="section-title"><i class="bi bi-ui-checks"></i> Perguntas Específicas do Protocolo</h2>
                    <div id="perguntas_container" class="row g-3"></div>
                </div>

                <div id="anamnese_fieldset">
                    <h2 class="section-title"><i class="bi bi-clipboard2-pulse"></i> Anamnese (Perguntas para Investigação)</h2>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="queixa_principal" class="form-label">Qual é a queixa principal que motivou a consulta?</label>
                            <textarea class="form-control" id="queixa_principal" name="queixa_principal" rows="2" placeholder="Descreva os sintomas relatados pelo paciente..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="tempo_sintomas" class="form-label">Há quanto tempo os sintomas começaram? (Evolução)</label>
                            <input type="text" class="form-control" id="tempo_sintomas" name="tempo_sintomas" placeholder="Ex: 3 dias, 2 semanas...">
                        </div>

                        <div class="col-md-6">
                            <span class="form-label d-block">Os sintomas são contínuos ou surgem em episódios?</span>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="frequencia_sintomas" value="continuo" id="freq_continuo">
                                <label class="form-check-label" for="freq_continuo">Contínuos</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="frequencia_sintomas" value="intermitente" id="freq_intermitente">
                                <label class="form-check-label" for="freq_intermitente">Intermitentes (vêm e vão)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="frequencia_sintomas" value="pioram_noite" id="freq_pioram">
                                <label class="form-check-label" for="freq_pioram">Pioram em períodos específicos</label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label for="fatores_alivio_piora" class="form-label">Existe algo que faça o sintoma melhorar ou piorar? (Alimentação, repouso, posição, etc.)</label>
                            <input type="text" class="form-control" id="fatores_alivio_piora" name="fatores_alivio_piora" placeholder="Ex: Piora ao tossir; melhora ao deitar...">
                        </div>

                        <div class="col-md-6">
                            <span class="form-label d-block">O paciente possui alergia a algum medicamento?</span>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tem_alergia" value="nao" id="alergia_nao" checked>
                                <label class="form-check-label" for="alergia_nao">Não</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tem_alergia" value="sim" id="alergia_sim">
                                <label class="form-check-label" for="alergia_sim">Sim</label>
                            </div>
                            <input type="text" class="form-control mt-2" id="quais_alergias" name="quais_alergias" placeholder="Se sim, quais?">
                        </div>

                        <div class="col-md-6">
                            <label for="historico_medico" class="form-label">Antecedentes médicos relevantes (Ex: Hipertensão, Diabetes, Cirurgias recentes?)</label>
                            <input type="text" class="form-control" id="historico_medico" name="historico_medico" placeholder="Descreva brevemente...">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="<?= BASE_URL ?>/medico/minha_agenda" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="bi bi-check-lg me-1"></i> Enviar Dados da Consulta
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
