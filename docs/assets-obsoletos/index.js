// Sidebar Active State
document.querySelectorAll('.sidebar .item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelector('.sidebar .item.active')?.classList.remove('active');
        this.classList.add('active');
    });
});

// Modo Escuro (Alterna a classe CSS)
const btnDarkMode = document.getElementById('botao-escuro');
btnDarkMode?.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    console.log('Modo escuro alternado');
});