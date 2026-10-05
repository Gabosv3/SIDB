/* Asistente de navegación: entiende frases como "quiero crear un usuario" y lleva a la pantalla. Sin IA. */
(function () {
    'use strict';

    var DATA = window.ASISTENTE || { items: [], faq: [] };
    if (!DATA.items || DATA.items.length === 0) return;

    var STOP = ('el la los las un una unos unas de del al a en y o u para por con sin que como quiero necesito quisiera puedo puede podria ' +
        'me mi mis lo le se es esta este esto ese eso hay ir ver abrir llevame lleva llevar mostrar muestrame dime donde ayuda favor hoy ahora ' +
        'hacer hago hacerlo cual cuales cuanto cuantos').split(' ');
    var VERBOS_CREAR = ['crear', 'nuevo', 'nueva', 'agregar', 'anadir', 'registrar', 'alta', 'crea', 'agrega', 'registra', 'creo', 'agrego'];

    function norm(s) {
        return String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
    }
    function stem(w) { return w.length > 4 ? w.replace(/(es|s)$/, '') : w; }
    function tokens(s) { return norm(s).split(' ').filter(Boolean); }

    // "hoy" se quita como palabra suelta pero cuenta dentro de frases como "ventas de hoy".
    function puntuar(consulta, qTokens, frases) {
        var mejor = 0;
        frases.forEach(function (frase) {
            var f = norm(frase);
            if (!f) return;
            var fTok = f.split(' ').map(stem);
            var s = 0;
            if (consulta.indexOf(f) !== -1) {
                s = 3 + fTok.length;
            } else {
                var coinciden = qTokens.filter(function (t) { return fTok.indexOf(t) !== -1; }).length;
                if (coinciden > 0) s = coinciden / Math.max(1, Math.min(fTok.length, 3)) + (coinciden > 1 ? 1 : 0.5);
            }
            if (s > mejor) mejor = s;
        });
        return mejor;
    }

    function analizar(texto) {
        var consulta = norm(texto);
        var todos = tokens(texto);
        var quiereCrear = todos.some(function (t) { return VERBOS_CREAR.indexOf(t) !== -1; });
        var q = todos.filter(function (t) {
            return STOP.indexOf(t) === -1 && VERBOS_CREAR.indexOf(t) === -1;
        }).map(stem);

        var nav = DATA.items.map(function (it) {
            var base = puntuar(consulta, q, it.k);
            if (base === 0) return { it: it, s: 0 };
            if (it.a === 'crear') base += quiereCrear ? 2 : -1;
            else if (quiereCrear && it.a === 'ver') base -= 0.5;
            return { it: it, s: base };
        }).filter(function (x) { return x.s > 0; }).sort(function (a, b) { return b.s - a.s; });

        var faq = DATA.faq.map(function (f) { return { f: f, s: puntuar(consulta, q, f.k) }; })
            .filter(function (x) { return x.s >= 3; }).sort(function (a, b) { return b.s - a.s; });

        return { nav: nav, faq: faq, quiereCrear: quiereCrear, vacia: q.length === 0 && !quiereCrear };
    }

    // ── Interfaz ─────────────────────────────────────────────────────────────
    var panel, lista, entrada, temporizador;

    function estilos() {
        var s = document.createElement('style');
        s.textContent =
            '#asist-btn{position:fixed;right:68px;bottom:18px;z-index:60;width:42px;height:42px;border-radius:50%;border:none;cursor:pointer;background:#2563eb;color:#fff;font-size:19px;box-shadow:0 4px 14px rgba(0,0,0,.3)}' +
            '#asist-panel{position:fixed;right:18px;bottom:70px;z-index:61;width:340px;max-width:calc(100vw - 36px);height:430px;max-height:calc(100vh - 100px);display:none;flex-direction:column;background:#fff;color:#111827;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.25);font:14px/1.4 system-ui,sans-serif;overflow:hidden}' +
            '#asist-panel.abierto{display:flex}' +
            '#asist-head{background:#2563eb;color:#fff;padding:10px 14px;font-weight:600;display:flex;justify-content:space-between;align-items:center}' +
            '#asist-head button{background:none;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1}' +
            '#asist-msgs{flex:1;overflow-y:auto;padding:10px;display:flex;flex-direction:column;gap:8px;background:#f9fafb}' +
            '.asist-m{max-width:88%;padding:8px 11px;border-radius:12px;word-wrap:break-word}' +
            '.asist-bot{background:#fff;border:1px solid #e5e7eb;align-self:flex-start}' +
            '.asist-yo{background:#2563eb;color:#fff;align-self:flex-end}' +
            '.asist-acc{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}' +
            '.asist-acc a,.asist-acc button{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:999px;padding:4px 10px;font:inherit;font-size:13px;cursor:pointer;text-decoration:none}' +
            '#asist-form{display:flex;gap:6px;padding:8px;border-top:1px solid #e5e7eb;background:#fff}' +
            '#asist-in{flex:1;border:1px solid #d1d5db;border-radius:8px;padding:7px 10px;font:inherit;color:#111827;background:#fff}' +
            '#asist-form button{background:#2563eb;color:#fff;border:none;border-radius:8px;padding:0 12px;cursor:pointer}';
        document.head.appendChild(s);
    }

    function mensaje(texto, quien, acciones) {
        var d = document.createElement('div');
        d.className = 'asist-m ' + (quien === 'yo' ? 'asist-yo' : 'asist-bot');
        d.textContent = texto;
        if (acciones && acciones.length) {
            var c = document.createElement('div');
            c.className = 'asist-acc';
            acciones.forEach(function (a) {
                var el = a.u ? document.createElement('a') : document.createElement('button');
                if (a.u) el.href = a.u; else el.type = 'button';
                el.textContent = a.t;
                if (a.fn) el.addEventListener('click', a.fn);
                c.appendChild(el);
            });
            d.appendChild(c);
        }
        lista.appendChild(d);
        lista.scrollTop = lista.scrollHeight;
        return d;
    }

    function ir(it) {
        mensaje('Llevándote a «' + it.t + '»…', 'bot', [{ t: 'Cancelar', fn: function () { clearTimeout(temporizador); mensaje('Listo, me quedo aquí.', 'bot'); } }]);
        clearTimeout(temporizador);
        temporizador = setTimeout(function () { window.location.href = it.u; }, 1200);
    }

    // Registra en el servidor las frases que no se entendieron, para ampliar las palabras clave.
    function registrar(texto, tipo) {
        var cfg = window.ASISTENTE_LOG;
        if (!cfg || !window.fetch) return;
        try {
            fetch(cfg.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.token, 'Accept': 'application/json' },
                body: JSON.stringify({ frase: texto.slice(0, 300), tipo: tipo, pantalla: location.pathname.slice(0, 200) }),
                credentials: 'same-origin'
            }).catch(function () {});
        } catch (e) {}
    }

    function responder(texto) {
        var r = analizar(texto);

        if (/^(hola|buenas|buenos dias|buenas tardes|buenas noches|hey)\b/.test(norm(texto)) && !r.nav.length && !r.faq.length) {
            return mensaje('¡Hola! Dime qué quieres hacer. Por ejemplo: «crear un usuario» o «ver las ventas de hoy».', 'bot', sugerencias());
        }

        var topNav = r.nav[0];
        var topFaq = r.faq[0];

        if (topFaq && (!topNav || topFaq.s >= topNav.s)) {
            var acc = topFaq.f.u ? [{ t: topFaq.f.l || 'Ir', u: topFaq.f.u }] : [];
            return mensaje(topFaq.f.r, 'bot', acc);
        }

        if (topNav && topNav.s >= 2) {
            var seg = r.nav[1];
            var claro = !seg || topNav.s - seg.s >= 1;
            if (claro) return ir(topNav.it);
            return mensaje('Encontré varias opciones, ¿cuál quieres?', 'bot', r.nav.slice(0, 3).map(function (x) { return { t: x.it.t, u: x.it.u }; }));
        }

        if (r.nav.length) {
            registrar(texto, 'dudosa');
            return mensaje('No estoy seguro. ¿Alguna de estas?', 'bot', r.nav.slice(0, 3).map(function (x) { return { t: x.it.t, u: x.it.u }; }));
        }

        registrar(texto, 'sin_resultado');
        mensaje('No entendí eso. Prueba con frases como «crear un cliente», «ver ventas», «hacer un respaldo» o «cómo corrijo un precio».', 'bot', sugerencias());
    }

    function sugerencias() {
        var out = [];
        ['Crear Usuario', 'Ver Clientes', 'Ver Ventas'].forEach(function (t) {
            var it = DATA.items.filter(function (x) { return x.t === t; })[0];
            if (it) out.push({ t: it.t, u: it.u });
        });
        return out;
    }

    function enviar() {
        var v = entrada.value.trim();
        if (!v) return;
        entrada.value = '';
        mensaje(v, 'yo');
        responder(v);
    }

    function init() {
        if (document.getElementById('asist-btn')) return;
        estilos();

        var b = document.createElement('button');
        b.id = 'asist-btn';
        b.type = 'button';
        b.title = 'Asistente';
        b.textContent = '💬';
        document.body.appendChild(b);

        panel = document.createElement('div');
        panel.id = 'asist-panel';
        panel.innerHTML =
            '<div id="asist-head"><span>Asistente</span><button type="button" id="asist-x" title="Cerrar">&times;</button></div>' +
            '<div id="asist-msgs"></div>' +
            '<form id="asist-form" autocomplete="off"><input id="asist-in" type="text" placeholder="¿Qué quieres hacer?"><button type="submit">Enviar</button></form>';
        document.body.appendChild(panel);

        lista = panel.querySelector('#asist-msgs');
        entrada = panel.querySelector('#asist-in');

        b.addEventListener('click', function () {
            var abierto = panel.classList.toggle('abierto');
            if (abierto) {
                if (!lista.children.length) mensaje('¡Hola! Dime qué quieres hacer y te llevo a esa pantalla. Por ejemplo: «quiero crear un usuario».', 'bot', sugerencias());
                entrada.focus();
            }
        });
        panel.querySelector('#asist-x').addEventListener('click', function () { panel.classList.remove('abierto'); });
        panel.querySelector('#asist-form').addEventListener('submit', function (e) { e.preventDefault(); enviar(); });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
    document.addEventListener('livewire:navigated', function () {
        ['asist-btn', 'asist-panel'].forEach(function (id) { var e = document.getElementById(id); if (e) e.remove(); });
        init();
    });
})();
