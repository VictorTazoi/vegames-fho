(function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const btnAbrir = document.getElementById('btnAbrirMenu');
    const btnFechar = document.getElementById('btnFecharMenu');

    function abrirMenu() {
        sidebar.classList.add('open');
        overlay.classList.add('open');
    }
    function fecharMenu() {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
    }

    btnAbrir && btnAbrir.addEventListener('click', abrirMenu);
    btnFechar && btnFechar.addEventListener('click', fecharMenu);
    overlay && overlay.addEventListener('click', fecharMenu);

    const btnSair = document.getElementById('btnSair');
    btnSair && btnSair.addEventListener('click', async function () {
        try {
            await fetch(window.VEGAMES_BASE_URL + '/api/auth/logout.php', { method: 'POST' });
        } catch (e) {  }
        window.location.href = window.VEGAMES_BASE_URL + '/index.php';
    });

    let toastTimer = null;
    window.mostrarToast = function (mensagem, tipo) {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = mensagem;
        toast.className = 'toast show' + (tipo === 'error' ? ' error' : '');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
    };
})();
