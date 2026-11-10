(function () {
    const base = window.VEGAMES_BASE_URL || '';

    const campos = {
        mqtt_host: document.getElementById('mqttHost'),
        mqtt_port: document.getElementById('mqttPort'),
        mqtt_client_id: document.getElementById('mqttClientId'),
        mqtt_user: document.getElementById('mqttUser'),
        mqtt_pass: document.getElementById('mqttPass'),
        mqtt_topic_filter: document.getElementById('mqttTopicFilter'),
        device_api_key: document.getElementById('deviceApiKey'),
    };

    function mostrarResultado(elId, sucesso, mensagem) {
        const el = document.getElementById(elId);
        el.style.display = 'flex';
        el.className = 'config-test-result ' + (sucesso ? 'ok' : 'erro');
        el.innerHTML = (sucesso
            ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 12 9 17 20 6"/></svg>'
            : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9.5 17H2.5L12 3z"/><line x1="12" y1="10" x2="12" y2="14.5"/><circle cx="12" cy="17.2" r="0.6" fill="currentColor"/></svg>')
            + '<span>' + mensagem + '</span>';
    }

    async function carregar() {
        try {
            const resp = await fetch(base + '/api/config/obter.php');
            const dados = await resp.json();
            if (!resp.ok) throw new Error(dados.erro || 'Falha ao carregar configurações.');

            Object.entries(campos).forEach(([chave, el]) => {
                el.value = dados.configuracoes[chave] ?? '';
            });
        } catch (err) {
            window.mostrarToast(err.message, 'error');
        }
    }

    function coletar() {
        return {
            mqtt_host: campos.mqtt_host.value.trim(),
            mqtt_port: parseInt(campos.mqtt_port.value, 10) || 0,
            mqtt_client_id: campos.mqtt_client_id.value.trim(),
            mqtt_user: campos.mqtt_user.value.trim(),
            mqtt_pass: campos.mqtt_pass.value,
            mqtt_topic_filter: campos.mqtt_topic_filter.value.trim(),
            device_api_key: campos.device_api_key.value.trim(),
        };
    }

    document.getElementById('btnMostrarChave').addEventListener('click', function () {
        const campo = campos.device_api_key;
        campo.type = campo.type === 'password' ? 'text' : 'password';
    });

    document.getElementById('btnMostrarMqttPass').addEventListener('click', function () {
        const campo = campos.mqtt_pass;
        campo.type = campo.type === 'password' ? 'text' : 'password';
    });

    document.getElementById('btnTestar').addEventListener('click', async function () {
        const btn = this;
        const dados = coletar();
        document.getElementById('testeResultado').style.display = 'none';

        btn.disabled = true;
        const textoOriginal = btn.innerHTML;
        btn.innerHTML = '<span class="spinner" style="border-color:rgba(22,48,92,.3);border-top-color:var(--navy-800);"></span> Testando...';

        try {
            const resp = await fetch(base + '/api/config/testar_mqtt.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados),
            });
            const resultado = await resp.json();
            mostrarResultado('testeResultado', !!resultado.sucesso, resultado.mensagem || resultado.erro);
        } catch (err) {
            mostrarResultado('testeResultado', false, 'Falha ao testar: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = textoOriginal;
        }
    });

    document.getElementById('formConfig').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnSalvar');
        document.getElementById('salvarResultado').style.display = 'none';
        btn.disabled = true;
        btn.textContent = 'Salvando...';

        try {
            const resp = await fetch(base + '/api/config/salvar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(coletar()),
            });
            const resultado = await resp.json();
            if (!resp.ok) throw new Error(resultado.erro || 'Não foi possível salvar.');
            mostrarResultado('salvarResultado', true, 'Configurações salvas com sucesso.');
            window.mostrarToast('Configurações salvas.');
        } catch (err) {
            mostrarResultado('salvarResultado', false, err.message);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Salvar Configurações';
        }
    });

    carregar();
})();
