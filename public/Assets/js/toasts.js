// Sistema de Toasts para mensagens
function showToast(tipo, mensagem, duracao = 3000) {
    // Empilha ate 3 toasts no canto superior direito, de baixo para
    // cima. Antes este codigo REMOVIA o toast anterior a cada chamada
    // (querySelector + remove), entao dois avisos seguidos fazem o
    // primeiro sumir - foi assim que o "CEP nao encontrado" era
    // engolido pelo "endereco preenchido".
    const MAXIMO = 3;
    const existentes = document.querySelectorAll('.toast-notification');
    if (existentes.length >= MAXIMO) {
        existentes[0].remove();
    }
    const quantos = document.querySelectorAll('.toast-notification').length;
    const top = 20 + quantos * 76;

    const toast = document.createElement('div');
    toast.className = 'toast-notification toast-' + tipo;

    // Montado com createElement/textContent, e nao com innerHTML: as
    // mensagens do projeto vem de $e->getMessage() e de erros de
    // validacao, que podem conter texto digitado pelo usuario. Com
    // innerHTML, uma mensagem com "<img onerror=...>" viraria script.
    const conteudo = document.createElement('div');
    conteudo.className = 'toast-content';

    const icone = document.createElement('span');
    icone.className = 'toast-icon';
    icone.textContent = getIcon(tipo);

    const texto = document.createElement('span');
    texto.className = 'toast-message';
    texto.textContent = mensagem;

    conteudo.appendChild(icone);
    conteudo.appendChild(texto);
    toast.appendChild(conteudo);

    // Largura fluida: no desktop fica proximo de 380px e no celular
    // acompanha a tela (100vw - margem) para nunca gerar rolagem lateral.
    const largura = 'min(380px, calc(100vw - 24px))';
    toast.style.cssText = `
        position: fixed;
        top: ${top}px;
        right: 12px;
        left: auto;
        background: ${getBackgroundColor(tipo)};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        width: ${largura};
        max-width: calc(100vw - 24px);
        animation: slideIn 0.3s ease-out;
    `;

    document.body.appendChild(toast);

    // Remove apos a duracao (3 segundos por padrao).
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => toast.remove(), 300);
    }, duracao);
}

function getIcon(tipo) {
    const icons = {
        'success': '✓',
        'error': '✕',
        'warning': '⚠',
        'info': 'ℹ'
    };
    return icons[tipo] || 'ℹ';
}

function getBackgroundColor(tipo) {
    const colors = {
        'success': '#10b981',
        'error': '#ef4444',
        'warning': '#f59e0b',
        'info': '#3b82f6'
    };
    return colors[tipo] || '#3b82f6';
}

// Animacoes (o CSS de cada toast continua vindo daqui, como antes).
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to   { transform: translateX(0);   opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0);   opacity: 1; }
        to   { transform: translateX(100%); opacity: 0; }
    }
    .toast-content { display: flex; align-items: center; gap: 10px; }
    .toast-icon { font-size: 20px; font-weight: bold; }
    .toast-message { flex: 1; font-size: 14px; }
`;
document.head.appendChild(style);
