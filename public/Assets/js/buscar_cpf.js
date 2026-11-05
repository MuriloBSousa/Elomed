(function () {
    'use strict';

    function soDigitos(v) {
        return (v || '').replace(/\D/g, '');
    }

    function baseDaUrl() {
        const src = document.currentScript && document.currentScript.src ? document.currentScript.src : '';
        const idx = src.indexOf('/Assets/js/');
        return idx > -1 ? src.slice(0, idx) : '';
    }

    const BASE = baseDaUrl();

    function setField(form, name, value) {
        if (!form) return;
        const el = form.elements.namedItem(name);
        if (!el) return;
        if (value === null || value === undefined) return;
        if (el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') {
            el.value = value;
        }
    }

    function aplicarMascaraTelefone(v) {
        const d = soDigitos(v).slice(0, 11);
        if (d.length === 0) return v || '';
        if (d.length <= 2) return '(' + d;
        if (d.length <= 6) return '(' + d.slice(0, 2) + ') ' + d.slice(2);
        if (d.length <= 10) return '(' + d.slice(0, 2) + ') ' + d.slice(2, 6) + '-' + d.slice(6);
        return '(' + d.slice(0, 2) + ') ' + d.slice(2, 7) + '-' + d.slice(7);
    }

    function consultar(cpfInput) {
        const cpf = soDigitos(cpfInput.value);
        if (cpf.length !== 11) return;

        const form = cpfInput.form;
        fetch(BASE + '/cpf/consultar/' + cpf)
            .then(function (res) { return res.json(); })
            .then(function (dados) {
                if (!dados || !dados.success) {
                    return;
                }
                setField(form, 'nome', dados.nome);
                setField(form, 'data_nascimento', dados.data_nascimento);
                setField(form, 'sexo', dados.sexo);
                setField(form, 'estado_civil', dados.estado_civil);
                if (dados.telefone_principal) {
                    setField(form, 'telefone_principal', aplicarMascaraTelefone(dados.telefone_principal));
                }
                if (dados.rg) {
                    setField(form, 'rg', dados.rg);
                }
                if (typeof showToast === 'function') {
                    showToast('success', 'Dados do CPF preenchidos automaticamente.');
                }
            })
            .catch(function () {});
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.campo-cpf').forEach(function (input) {
            input.addEventListener('blur', function () { consultar(input); });
            input.addEventListener('input', function () {
                if (soDigitos(input.value).length === 11) {
                    consultar(input);
                }
            });
        });
    });
})();
