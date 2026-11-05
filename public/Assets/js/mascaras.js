function soDigitos(v) {
    return (v || '').replace(/\D/g, '');
}

function formatarCpf(v) {
    const d = soDigitos(v).slice(0, 11);
    if (d.length <= 3) return d;
    if (d.length <= 6) return d.replace(/(\d{3})(\d)/, '$1.$2');
    if (d.length <= 9) return d.replace(/(\d{3})(\d{3})(\d)/, '$1.$2.$3');
    return d.replace(/(\d{3})(\d{3})(\d{3})(\d)/, '$1.$2.$3-$4');
}

function formatarTelefone(v) {
    const d = soDigitos(v).slice(0, 11);
    if (d.length === 0) return d;
    if (d.length <= 2) return '(' + d;
    if (d.length <= 6) return '(' + d.slice(0, 2) + ') ' + d.slice(2);
    if (d.length <= 10) return '(' + d.slice(0, 2) + ') ' + d.slice(2, 6) + '-' + d.slice(6);
    return '(' + d.slice(0, 2) + ') ' + d.slice(2, 7) + '-' + d.slice(7);
}

function formatarCep(v) {
    const d = soDigitos(v).slice(0, 8);
    if (d.length <= 5) return d;
    return d.slice(0, 5) + '-' + d.slice(5);
}

// Autenticidade do CPF: 11 digitos, sem repeticao e com os dois
// digitos verificadores validos (mesmo algoritmo do Validator::isValidCpf).
function cpfValido(valor) {
    const cpf = soDigitos(valor);
    if (cpf.length !== 11) return false;
    if (/^(\d)\1{10}$/.test(cpf)) return false;

    let soma = 0;
    for (let i = 0; i < 9; i++) soma += parseInt(cpf[i], 10) * (10 - i);
    const d1 = (soma * 10) % 11 % 10;
    if (parseInt(cpf[9], 10) !== d1) return false;

    soma = 0;
    for (let i = 0; i < 10; i++) soma += parseInt(cpf[i], 10) * (11 - i);
    const d2 = (soma * 10) % 11 % 10;
    return parseInt(cpf[10], 10) === d2;
}

function marcarCpfInvalido(input) {
    input.classList.add('is-invalid');
    let feedback = (input.parentElement || input).querySelector('.cpf-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block cpf-feedback';
        (input.parentElement || input).appendChild(feedback);
    }
    feedback.textContent = 'CPF inválido: confira os 11 dígitos.';
}

function limparCpfInvalido(input) {
    input.classList.remove('is-invalid');
    const feedback = (input.parentElement || input).querySelector('.cpf-feedback');
    if (feedback) feedback.remove();
}

const MASCARA = {
    'campo-cpf': formatarCpf,
    'campo-telefone': formatarTelefone,
    'campo-cep': formatarCep,
};

document.addEventListener('input', (e) => {
    const t = e.target;
    if (!t || !t.classList) return;
    for (const [classe, fn] of Object.entries(MASCARA)) {
        if (t.classList.contains(classe)) {
            t.value = fn(t.value);
        }
    }
    // Enquanto o usuario corrige, o aviso fica ativo ate o CPF ficar valido.
    if (t.classList && t.classList.contains('campo-cpf') && t.classList.contains('is-invalid')) {
        if (soDigitos(t.value) === '' || cpfValido(t.value)) limparCpfInvalido(t);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    for (const [classe, fn] of Object.entries(MASCARA)) {
        document.querySelectorAll('.' + classe).forEach((input) => {
            if (input.value) input.value = fn(input.value);
        });
    }
});

document.addEventListener('submit', (e) => {
    // Autenticacao do CPF: nao deixa enviar com digito verificador errado.
    let cpfInvalido = false;
    e.target.querySelectorAll('.campo-cpf').forEach((input) => {
        const digits = soDigitos(input.value);
        if (digits === '') {
            limparCpfInvalido(input);
            return;
        }
        if (!cpfValido(digits)) {
            cpfInvalido = true;
            marcarCpfInvalido(input);
        } else {
            limparCpfInvalido(input);
        }
    });
    if (cpfInvalido) {
        e.preventDefault();
        return;
    }

    e.target.querySelectorAll('.campo-cpf, .campo-telefone, .campo-cep').forEach((input) => {
        input.value = soDigitos(input.value);
    });
});