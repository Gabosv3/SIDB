/* Tutoriales guiados (Driver.js). Un recorrido por pantalla; botón "?" fijo y se ofrece solo la primera vez. */
(function () {
    'use strict';

    var wm = function (n) {
        return '[wire\\:model\\.live="' + n + '"],[wire\\:model="' + n + '"],[wire\\:model\\.live\\.debounce\\.400ms="' + n + '"]';
    };

    // Texto de cada módulo: nombre, para qué sirve y consejos extra (opcionales).
    var MODULOS = {
        'clientes': { n: 'Clientes', d: 'Catálogo de clientes con sus datos, crédito y saldo. De aquí salen las ventas y las rutas de cobro.', crear: 'Llena las pestañas de arriba (datos personales, otros datos, crédito). El código se asigna solo.' },
        'ventas': { n: 'Ventas', d: 'Todas las ventas, de contado y a crédito, con su estado, saldo y vendedor. La pestaña "Historial de canceladas" muestra las anuladas.', crear: 'Es un asistente por pasos: cliente, productos, totales y confirmar.' },
        'asignaciones-diarias': { n: 'Asignaciones Diarias', d: 'Mercadería que lleva cada vendedor al día y su liquidación al regresar. Las jornadas abiertas se liquidan solas a las 11 pm.', crear: 'Elige vendedor, fecha y agrega los productos con su cantidad.' },
        'comision-tramos': { n: 'Tramos de Comisión', d: 'Porcentaje de comisión según el precio de cada producto vendido.', crear: 'Indica desde qué monto aplica, hasta cuál (vacío = sin límite) y el porcentaje.' },
        'preventas': { n: 'Preventas', d: 'Pedidos anotados por los cobradores. Asigna un vendedor y conviértelos en venta.' },
        'gestion-cobros': { n: 'Gestiones de Cobro', d: 'Cuotas de las ventas a crédito: pendientes, cobradas y vencidas.' },
        'ruta-cobros': { n: 'Rutas de Cobro', d: 'Rutas con su cobrador, día de la semana y semana del ciclo. Sin día, la ruta aparece todos los días.', crear: 'Elige cobrador, nombre y, si quieres, el día y la semana del ciclo.' },
        'asignar-rutas-clientes': { n: 'Asignar Rutas a Clientes', d: 'Clientes que aún no tienen ruta. Asigna una a cada uno o a varios seleccionados.' },
        'clientes-inactivos': { n: 'Clientes Inactivos', d: 'Clientes con saldo sin visita ni abono en cierto número de días. Se puede exportar a PDF.' },
        'reintegros': { n: 'Reintegros', d: 'Cuentas muy atrasadas enviadas a un vendedor para recuperarlas.', crear: 'Elige la venta con saldo, el vendedor y el estado.' },
        'garantias': { n: 'Garantías', d: 'Reclamos por productos que fallan, con seguimiento hasta resolverlos.', crear: 'Elige la venta, quién la atiende y describe el problema.' },
        'encuesta-clientes': { n: 'Encuestas a Clientes', d: 'Encuestas del supervisor que comparan lo que dice el cliente con lo registrado.' },
        'productos': { n: 'Productos', d: 'Catálogo con precios, planes de cuotas, stock y combos. Hay acciones masivas para precios y stock.', crear: 'Usa las pestañas: información, precios y stock, adicional, imagen y proveedores.' },
        'categorias': { n: 'Categorías', d: 'Agrupan los productos del catálogo.' },
        'compras': { n: 'Compras', d: 'Pedidos a proveedores. El stock sube cuando la compra pasa a estado Recibida.', crear: 'Asistente por pasos: proveedor, artículos, totales y pago, finalizar.' },
        'proveedors': { n: 'Proveedores', d: 'Catálogo de proveedores con contacto y condiciones comerciales.' },
        'vehiculos': { n: 'Vehículos', d: 'Motos y carros de la empresa, a quién están asignados y su mantenimiento.' },
        'vales': { n: 'Gastos', d: 'Gastos y vales de empleados. Los pendientes se aprueban o rechazan con motivo.' },
        'vendedors': { n: 'Vendedores', d: 'Vendedores y su usuario de la app. Un vendedor con ventas se desactiva, no se elimina.' },
        'cobradors': { n: 'Cobradores', d: 'Cobradores y su usuario. "Excluir de reportes" los oculta del Resumen del Día y de la Liquidación.' },
        'supervisors': { n: 'Supervisores', d: 'Supervisores y las rutas que pueden supervisar.' },
        'supervisiones': { n: 'Supervisiones de Cobradores', d: 'Evaluaciones que los supervisores hacen a los cobradores en campo (solo consulta).' },
        'users': { n: 'Usuarios', d: 'Cuentas de acceso, sus roles y las sucursales que pueden ver.', crear: 'Pestaña Información (datos y contraseña) y pestaña Permisos (roles y sucursales).' },
        'sucursals': { n: 'Sucursales', d: 'Sedes de la empresa; los datos se separan por sucursal.' },
        'audit-logs': { n: 'Logs de Auditoría', d: 'Quién creó, modificó o eliminó registros. Solo lectura.' },
        'historial-pagos-eliminados': { n: 'Historial de Pagos Eliminados', d: 'Bitácora de pagos eliminados por un administrador, con motivo.' },
        'resumen-ventas-dia': { n: 'Resumen de Ventas del Día', d: 'Todo lo vendido en un día, semana o mes, con clientes nuevos y recurrentes.',
            pasos: [
                { s: wm('fecha'), t: 'Fecha', d: 'Elige el día (o el día de referencia de la semana o mes).' },
                { s: wm('periodo'), t: 'Período', d: 'Solo ese día, semana completa o mes completo.' },
                { s: wm('buscarCliente'), t: 'Buscar cliente', d: 'Por nombre, código o teléfono.' },
                { s: wm('filtroClientesNuevos'), t: 'Clientes nuevos', d: 'Filtra los nuevos con o sin ruta. Con este filtro puedes marcar clientes e imprimirlos con su código, dirección, ruta y producto.' },
                { s: wm('codigoDesde'), t: 'Rango de código', d: 'Muestra solo clientes cuyo código está entre "desde" y "hasta".' },
                { s: 'table', t: 'Detalle de ventas', d: 'Cada fila es una venta. Desde aquí puedes asignar ruta, confirmar la prima, corregir o cancelar.' }
            ] },
        'resumen-mensual': { n: 'Resumen Mensual', d: 'Ventas, cobros, compras, gastos (con vehículos), comisiones, cartera y un flujo estimado del mes. Cambia el mes arriba y descarga el PDF.',
            pasos: [
                { s: wm('mes'), t: 'Mes', d: 'Elige el mes a resumir.' },
                { s: 'a.rm-btn', t: 'PDF', d: 'Descarga este mismo resumen en PDF.' },
                { s: '.rm-table', t: 'Secciones', d: 'Cada bloque resume un área; las comisiones y el flujo son estimados.' }
            ] },
        'resumen-cobros-dia': { n: 'Resumen de Cobros del Día', d: 'Lo cobrado por cada cobrador en el día. Úsalo al cierre para cuadrar el efectivo.' },
        'resumen-encuestas-cliente': { n: 'Resumen de Encuestas de Cliente', d: 'Resumen de encuestas del día por cobrador.' },
        'resumen-reintegros': { n: 'Resumen de Reintegros del Día', d: 'Reintegros del día; usa "Gestionar reintegros" para el listado completo.' },
        'resumen-garantias': { n: 'Resumen de Garantías del Día', d: 'Garantías reportadas ese día; usa "Gestionar garantías" para el listado completo.' },
        'liquidacion-semanal': { n: 'Liquidación Semanal de Cobradores', d: 'Comisión de cada cobrador por semana menos anticipos y vales de consumo. "Liquidar" marca los anticipos como descontados.' },
        'liquidacion-semanal-ventas': { n: 'Liquidación Semanal de Ventas', d: 'Comisión de cada vendedor por semana, con detalle por día: anticipos, vale de consumo y remanente.' },
        'reportes-ventas': { n: 'Reportes de Ventas', d: 'Análisis de ventas por vendedor, producto y tipo de pago.' },
        'reportes-cobros': { n: 'Reportes de Cobros', d: 'Cobranza por cobrador: cobrado, clientes atendidos, efectividad y morosidad.' },
        'reportes-cartera': { n: 'Cartera y Morosidad', d: 'Cuánto hay por cobrar y su antigüedad. Siempre es "a hoy".' },
        'reportes-inventario': { n: 'Reportes de Inventario', d: 'Valor del inventario, stock bajo y productos sin movimiento.' },
        'reportes-compras': { n: 'Reportes de Compras', d: 'Compras y saldos por proveedor en el período.' },
        'reportes-comisiones': { n: 'Comisiones y Nómina', d: 'Estimación de nómina por período según la modalidad de pago de cada empleado.' },
        'asistencia-empleados': { n: 'Asistencia de Empleados', d: 'Entradas y salidas del reloj biométrico, horas y tardanzas. Puedes registrar una asistencia manual.' },
        'personalizacion-sistema': { n: 'Personalización del Sistema', d: 'Datos de la empresa, colores, contacto y datos legales que se usan en documentos.' },
        'backups': { n: 'Respaldos', d: 'Copias de seguridad de la base de datos. Descárgalas y guárdalas fuera del servidor.' },
        'clientes-ruta': { n: 'Clientes por Ruta', d: 'Ordena a los clientes dentro de cada ruta y mantén sus datos al día.' },
        'perfil-cliente': { n: 'Perfil del cliente', d: 'Ventas, pagos, recibos, visitas y datos de un cliente.' }
    };

    function el(sel) { try { return document.querySelector(sel); } catch (e) { return null; } }
    function porTexto(tag, texto) {
        return Array.prototype.find.call(document.querySelectorAll(tag), function (e) { return (e.textContent || '').trim().indexOf(texto) === 0; });
    }

    // Identifica la pantalla actual: { clave, tipo: index|create|edit|ver|pagina }
    function pantalla() {
        var p = location.pathname;
        var m = p.match(/\/administrativo\/\d+\/([^\/]+)(?:\/(create|\d+\/edit|\d+))?\/?$/);
        if (m) {
            var tipo = !m[2] ? 'index' : (m[2] === 'create' ? 'create' : (/edit/.test(m[2]) ? 'edit' : 'ver'));
            return { clave: m[1], tipo: tipo };
        }
        if (/\/clientes-ruta\/\d+\/clientes\/\d+\/perfil/.test(p)) return { clave: 'perfil-cliente', tipo: 'pagina' };
        if (/\/clientes-ruta\/\d+\/?$/.test(p)) return { clave: 'clientes-ruta', tipo: 'pagina' };
        return null;
    }

    function pasosPanel(pant, mod) {
        var pasos = [];
        var nombre = mod.n || 'esta pantalla';
        pasos.push({ element: '.fi-header-heading', popover: { title: nombre, description: mod.d || 'Tutorial de esta pantalla.' } });

        if (pant.tipo === 'index' && !el('.fi-ta-table')) {
            pasos.push({ element: '.fi-page-content', popover: { title: 'Contenido', description: 'Ajusta los filtros de la parte de arriba (fecha, período, persona, etc.) para cambiar lo que se muestra. Debajo están los totales y el detalle.' } });
        } else if (pant.tipo === 'index') {
            pasos.push({ element: '.fi-header-actions-ctn', popover: { title: 'Acciones', description: 'Aquí están los botones principales del módulo, como crear un registro nuevo o descargar reportes.' } });
            pasos.push({ element: '.fi-ta-search-field', popover: { title: 'Buscar', description: 'Escribe para encontrar registros rápidamente.' } });
            pasos.push({ element: '.fi-ta-filters-dropdown', popover: { title: 'Filtros', description: 'Filtra la lista (por estado, fechas, etc.). El número indica cuántos filtros están activos; algunos módulos traen uno aplicado por defecto.' } });
            pasos.push({ element: '.fi-ta-col-manager-dropdown', popover: { title: 'Columnas', description: 'Muestra u oculta columnas de la tabla.' } });
            pasos.push({ element: '.fi-ta-table', popover: { title: 'Listado', description: 'Pulsa el título de una columna para ordenar. Pulsa una fila o sus acciones para ver, editar o eliminar. Marca varias filas para usar acciones masivas.' } });
            pasos.push({ element: '.fi-pagination', popover: { title: 'Paginación', description: 'Cambia de página y elige cuántos registros ver.' } });
        } else if (pant.tipo === 'create' || pant.tipo === 'edit') {
            if (mod.crear) pasos.push({ element: '.fi-sc-form', popover: { title: pant.tipo === 'create' ? 'Formulario de creación' : 'Formulario de edición', description: mod.crear } });
            pasos.push({ element: '.fi-sc-tabs, .fi-sc-wizard', popover: { title: 'Secciones del formulario', description: 'Navega entre las pestañas o pasos. Los campos con asterisco (*) son obligatorios.' } });
            pasos.push({ element: '.fi-sc-form .fi-section', popover: { title: 'Campos', description: 'Completa los datos. Los mensajes en rojo indican qué falta o está mal.' } });
            pasos.push({ element: function () { return porTexto('button', 'Crear') || porTexto('button', 'Guardar'); }, popover: { title: 'Guardar', description: 'Guarda el registro. "Crear y crear otro" te deja listo para el siguiente.' } });
        } else if (pant.tipo === 'ver') {
            pasos.push({ element: '.fi-header-actions-ctn', popover: { title: 'Acciones', description: 'Edita el registro o usa las acciones disponibles.' } });
        }

        (mod.pasos || []).forEach(function (x) {
            pasos.push({ element: x.s, popover: { title: x.t, description: x.d } });
        });
        pasos.push({ element: '.fi-sidebar-nav', popover: { title: 'Menú', description: 'Desde aquí cambias de módulo. Pulsa el nombre de un grupo para abrirlo o cerrarlo. Puedes repetir este tutorial con el botón ? de abajo.', side: 'right' } });
        return pasos;
    }

    function pasosClientesRuta() {
        return [
            { element: '#cr-ruta-filter', popover: { title: 'Ruta de cobro', description: 'Elige la ruta a trabajar. También hay "Sin ruta asignada", "Cuentas cerradas" y "Todos".' } },
            { element: '#cr-recordatorios-btn', popover: { title: 'Recordatorios de pago', description: 'Abre una lista de los clientes con saldo de la ruta; cada botón "Enviar" abre WhatsApp Web con el mensaje ya escrito (nombre, saldo y próxima visita).' } },
            { element: '#cr-revision-marcar-todos', popover: { title: 'Revisión', description: 'Marca o limpia la revisión de toda la ruta. La casilla ✓ de cada fila sirve para ir marcando mientras comparas con las tarjetas físicas.' } },
            { element: '.pm-table', popover: { title: 'Listado de la ruta', description: 'Arrastra el ícono ⠿ para cambiar el orden de visita. El lápiz edita cada dato. El ojo abre el perfil del cliente y el selector de la derecha cambia su ruta.' } },
            { element: '#cr-fusionar-abrir', popover: { title: 'Fusionar rutas', description: 'Mueve todos los clientes de una ruta a otra de forma permanente. Úsalo con cuidado.' } },
            { element: '#cr-exportar-excel', popover: { title: 'Exportar', description: 'Descarga el código y nombre de esta lista en Excel o Word.' } }
        ];
    }

    function pasosPerfil() {
        return [
            { element: '.cr-venta-card', popover: { title: 'Ventas del cliente', description: 'Cada tarjeta es una venta: productos, total, pagado, saldo, cuotas y los pagos recibidos.' } },
            { element: '.cp-venta-vendedor-fecha-edit', popover: { title: 'Corregir vendedor y fecha', description: 'Solo super administrador. Cambia el vendedor o la fecha de la venta.' } },
            { element: '.cp-venta-precios-edit', popover: { title: 'Corregir precios y descuento', description: 'Solo super administrador. Cambia el precio de cada producto y el descuento de la venta; pide motivo y recalcula total y cuotas. Si hay abonos, hay que marcar "Forzar".' } },
            { element: '.cp-anular-recibo', popover: { title: 'Recibos', description: 'Descarga, corrige la fecha o anula un recibo. Anular no borra el registro, pero deja de contar en el saldo.' } }
        ];
    }

    var driverObj = null;

    // Pantallas que pintan su contenido con JS tardan en tener los elementos: se espera hasta 8 s.
    function iniciar(pant, intento) {
        intento = intento || 0;
        if (!window.driver || !window.driver.js) return;
        var jsPantalla = pant.clave === 'clientes-ruta' || pant.clave === 'perfil-cliente';
        if (jsPantalla && intento < 20 && !el(pant.clave === 'clientes-ruta' ? '.pm-table .cr-row' : '.cr-venta-card')) {
            return setTimeout(function () { iniciar(pant, intento + 1); }, 400);
        }
        var mod = MODULOS[pant.clave] || { n: pant.clave, d: '' };
        var pasos = pant.clave === 'clientes-ruta' ? pasosClientesRuta()
            : (pant.clave === 'perfil-cliente' ? pasosPerfil() : pasosPanel(pant, mod));

        // Se omiten los pasos cuyo elemento no está en esta pantalla.
        pasos = pasos.filter(function (p) {
            if (!p.element) return true;
            var e = typeof p.element === 'function' ? p.element() : el(p.element);
            if (!e) return false;
            if (typeof p.element === 'function') p.element = e;
            return true;
        });
        if (pasos.length === 0) return;

        driverObj = window.driver.js.driver({
            showProgress: true,
            nextBtnText: 'Siguiente',
            prevBtnText: 'Anterior',
            doneBtnText: 'Terminar',
            progressText: '{{current}} de {{total}}',
            allowClose: true,
            overlayOpacity: 0.55,
            steps: pasos
        });
        driverObj.drive();
    }

    function boton(pant) {
        if (document.getElementById('tutorial-btn')) return;
        var b = document.createElement('button');
        b.id = 'tutorial-btn';
        b.type = 'button';
        b.title = 'Tutorial de esta pantalla';
        b.textContent = '?';
        b.style.cssText = 'position:fixed;right:18px;bottom:18px;z-index:60;width:42px;height:42px;border-radius:50%;border:none;cursor:pointer;background:#c0394b;color:#fff;font:700 20px/1 system-ui,sans-serif;box-shadow:0 4px 14px rgba(0,0,0,.3)';
        b.addEventListener('click', function () { iniciar(pant); });
        document.body.appendChild(b);
    }

    function init() {
        var pant = pantalla();
        if (!pant) return;
        boton(pant);

        var clave = 'tutorial-visto:' + pant.clave + ':' + pant.tipo;
        var visto = false;
        try { visto = !!localStorage.getItem(clave); } catch (e) {}
        if (!visto) {
            try { localStorage.setItem(clave, '1'); } catch (e) {}
            // Espera a que Livewire/JS terminen de pintar la pantalla.
            setTimeout(function () { iniciar(pant); }, 2200);
        }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
    document.addEventListener('livewire:navigated', function () {
        var b = document.getElementById('tutorial-btn');
        if (b) b.remove();
        init();
    });
})();
