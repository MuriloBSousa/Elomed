/*
 * select-profissional.js
 * Melhoria progressiva para <select class="js-seletor-profissional">.
 * Mantém o <select> nativo no formulário (envio e fallback sem JS),
 * mas mostra um botão com o nome curto que abre um menu de cartões
 * com foto e descrição do profissional.
 *
 * Cada <option> pode trazer:
 *   data-nome       -> nome completo exibido no cartão
 *   data-curto      -> texto curto exibido no botão
 *   data-descricao  -> linha de descrição (especialidade · anos · registro)
 *   data-foto       -> URL da foto (opcional; sem ela usa as iniciais)
 */
(function () {
    'use strict';

    function iniciais(nome) {
        var partes = String(nome || '').trim().split(/\s+/).filter(Boolean);
        if (!partes.length) return '?';
        var primeira = partes[0].charAt(0);
        var ultima = partes.length > 1 ? partes[partes.length - 1].charAt(0) : '';
        return (primeira + ultima).toUpperCase();
    }

    function preencherAvatar(el, foto, nome) {
        el.textContent = '';
        if (foto) {
            var img = document.createElement('img');
            img.alt = '';
            img.loading = 'lazy';
            img.addEventListener('error', function () {
                el.textContent = iniciais(nome);
            });
            img.src = foto;
            el.appendChild(img);
        } else {
            el.textContent = iniciais(nome);
        }
    }

    function enhance(select) {
        if (select.dataset.selprofReady === '1') return;
        select.dataset.selprofReady = '1';

        var opcoes = Array.prototype.slice.call(select.options).filter(function (o) {
            return o.value !== '' && !o.disabled;
        });
        if (!opcoes.length) return;

        var placeholder = select.dataset.titulo || 'Selecione o profissional...';

        var root = document.createElement('div');
        root.className = 'selprof';

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'selprof-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');

        var triggerAvatar = document.createElement('span');
        triggerAvatar.className = 'selprof-trigger-avatar';
        var triggerText = document.createElement('span');
        triggerText.className = 'selprof-trigger-text';
        var caret = document.createElement('i');
        caret.className = 'bi bi-chevron-down selprof-caret';
        trigger.appendChild(triggerAvatar);
        trigger.appendChild(triggerText);
        trigger.appendChild(caret);

        var backdrop = document.createElement('div');
        backdrop.className = 'selprof-backdrop';

        var panel = document.createElement('div');
        panel.className = 'selprof-panel';
        panel.setAttribute('role', 'listbox');

        var head = document.createElement('div');
        head.className = 'selprof-panel-head';
        var headTitle = document.createElement('span');
        headTitle.textContent = placeholder;
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'selprof-close';
        close.setAttribute('aria-label', 'Fechar');
        close.innerHTML = '<i class="bi bi-x-lg"></i>';
        head.appendChild(headTitle);
        head.appendChild(close);

        var list = document.createElement('div');
        list.className = 'selprof-list';

        var botoes = [];

        opcoes.forEach(function (opt) {
            var nome = opt.dataset.nome || opt.textContent.trim();
            var desc = opt.dataset.descricao || '';
            var foto = opt.dataset.foto || '';

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'selprof-option';
            btn.setAttribute('role', 'option');
            btn.setAttribute('aria-selected', 'false');
            btn.dataset.value = opt.value;

            var avatar = document.createElement('span');
            avatar.className = 'selprof-avatar';
            preencherAvatar(avatar, foto, nome);
            btn.appendChild(avatar);

            var info = document.createElement('span');
            info.className = 'selprof-info';
            var nomeEl = document.createElement('span');
            nomeEl.className = 'selprof-nome';
            nomeEl.textContent = nome;
            info.appendChild(nomeEl);
            if (desc) {
                var descEl = document.createElement('span');
                descEl.className = 'selprof-desc';
                descEl.textContent = desc;
                info.appendChild(descEl);
            }
            btn.appendChild(info);

            var check = document.createElement('i');
            check.className = 'bi bi-check2-circle selprof-check';
            btn.appendChild(check);

            btn.addEventListener('click', function () {
                selecionar(opt.value);
                fechar();
                trigger.focus();
            });

            botoes.push(btn);
            list.appendChild(btn);
        });

        panel.appendChild(head);
        panel.appendChild(list);
        root.appendChild(trigger);
        root.appendChild(backdrop);
        root.appendChild(panel);

        select.classList.add('selprof-native');
        select.parentNode.insertBefore(root, select.nextSibling);

        function abaAtual() {
            for (var i = 0; i < opcoes.length; i++) {
                if (opcoes[i].value === select.value) return opcoes[i];
            }
            return null;
        }

        function atualizarTrigger() {
            var opt = abaAtual();
            if (opt) {
                var nome = opt.dataset.nome || opt.textContent.trim();
                triggerText.textContent = opt.dataset.curto || nome;
                preencherAvatar(triggerAvatar, opt.dataset.foto || '', nome);
            } else {
                triggerText.textContent = placeholder;
                triggerAvatar.textContent = '';
            }
            botoes.forEach(function (b) {
                b.setAttribute('aria-selected', b.dataset.value === select.value ? 'true' : 'false');
            });
        }

        function selecionar(value) {
            select.value = value;
            atualizarTrigger();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function abrir() {
            // Desktop: decide abrir para baixo/cima e limita a altura ao espaço
            // disponível para o painel nunca sair da tela. No celular é bottom sheet.
            if (window.innerWidth > 768) {
                var rect = trigger.getBoundingClientRect();
                var abaixo = window.innerHeight - rect.bottom;
                var acima = rect.top;
                var alturaDesejada = Math.min(380, window.innerHeight * 0.62) + 44;
                var usarAcima = abaixo < alturaDesejada && acima > abaixo;
                root.classList.toggle('selprof--up', usarAcima);
                var disponivel = (usarAcima ? acima : abaixo) - 20;
                panel.style.maxHeight = Math.max(200, Math.min(disponivel, window.innerHeight - 40)) + 'px';
            } else {
                root.classList.remove('selprof--up');
                panel.style.maxHeight = '';
            }
            root.classList.add('selprof--open');
            trigger.setAttribute('aria-expanded', 'true');
        }

        function fechar() {
            root.classList.remove('selprof--open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', function () {
            if (root.classList.contains('selprof--open')) fechar();
            else abrir();
        });
        close.addEventListener('click', fechar);
        backdrop.addEventListener('click', fechar);
        select.addEventListener('change', atualizarTrigger);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && root.classList.contains('selprof--open')) fechar();
        });
        document.addEventListener('click', function (e) {
            if (root.classList.contains('selprof--open') && !root.contains(e.target)) fechar();
        });

        atualizarTrigger();
    }

    function iniciar() {
        document.querySelectorAll('select.js-seletor-profissional').forEach(enhance);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
