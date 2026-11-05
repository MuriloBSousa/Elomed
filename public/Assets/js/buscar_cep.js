// Auto-preenchimento de endereço a partir do CEP (ViaCEP / BrasilAPI).
// O endpoint é derivado do próprio src desta tag, então não depende de globais.
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

    // o campo de CEP usa "end_cep" (perfil) ou "endereco_cep" (padrão).
    function prefixoDoFormulario(cepInput) {
        const nome = (cepInput.name || '').toLowerCase();
        return nome === 'end_cep' ? 'end_' : 'endereco_';
    }

    function preencherEndereco(cepInput, dados) {
        const form = cepInput.form;
        const prefixo = prefixoDoFormulario(cepInput);
        if (!form) return;

        const set = function (campo, valor) {
            const el = form.elements.namedItem(prefixo + campo);
            if (el) el.value = valor || '';
        };

        set('rua', dados.logradouro);
        set('bairro', dados.bairro);
        set('cidade', dados.cidade);
        set('uf', dados.uf);
        set('complemento', dados.complemento);
    }

    function consultar(cepInput) {
        const cep = soDigitos(cepInput.value);
        if (cep.length !== 8) return;

        fetch(BASE + '/endereco/consultar_cep/' + cep)
            .then(function (res) { return res.json(); })
            .then(function (dados) {
                if (!dados.success) {
                    if (typeof showToast === 'function') showToast('warning', dados.message || 'CEP não encontrado.');
                    return;
                }
                preencherEndereco(cepInput, dados);
                if (typeof showToast === 'function') showToast('success', 'Endereço preenchido automaticamente.');
            })
            .catch(function () {
                if (typeof showToast === 'function') showToast('error', 'Erro ao consultar o CEP. Tente novamente.');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.campo-cep').forEach(function (input) {
            input.addEventListener('blur', function () { consultar(input); });
            input.addEventListener('input', function () {
                if (soDigitos(input.value).length === 8) consultar(input);
            });
        });
    });
})();