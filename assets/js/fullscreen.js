(function () {
    const ICON_EXPAND = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="8 3 3 3 3 8"/><polyline points="16 3 21 3 21 8"/><polyline points="21 16 21 21 16 21"/><polyline points="3 16 3 21 8 21"/></svg>';
    const ICON_COMPRESS = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 3 9 8 4 8"/><polyline points="15 3 15 8 20 8"/><polyline points="9 21 9 16 4 16"/><polyline points="15 21 15 16 20 16"/></svg>';

    const botoes = document.querySelectorAll('.btn-tela-cheia');
    if (!botoes.length) return;

    function estaEmTelaCheia() {
        return !!(document.fullscreenElement || document.webkitFullscreenElement);
    }

    function alternar() {
        let promessa;
        if (estaEmTelaCheia()) {
            promessa = (document.exitFullscreen || document.webkitExitFullscreen).call(document);
        } else {
            const el = document.documentElement;
            promessa = (el.requestFullscreen || el.webkitRequestFullscreen).call(el);
        }
        promessa && promessa.catch(function () {});
    }

    function atualizarBotoes() {
        const ativo = estaEmTelaCheia();
        botoes.forEach(function (btn) {
            btn.innerHTML = ativo ? ICON_COMPRESS : ICON_EXPAND;
            const texto = ativo ? 'Sair da tela cheia' : 'Tela cheia';
            btn.title = texto;
            btn.setAttribute('aria-label', texto);
        });
    }

    botoes.forEach(function (btn) { btn.addEventListener('click', alternar); });
    document.addEventListener('fullscreenchange', atualizarBotoes);
    document.addEventListener('webkitfullscreenchange', atualizarBotoes);
})();
