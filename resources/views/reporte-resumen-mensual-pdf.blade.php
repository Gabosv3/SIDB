<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen Mensual</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 10px; color: #333; line-height: 1.3; }
        .container { padding: 15px; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 3px solid #c0394b; padding-bottom: 8px; }
        .header h1 { font-size: 20px; color: #1f2937; margin-bottom: 3px; }
        .header p { font-size: 11px; color: #6b7280; }
        .rm-h { font-size: 13px; font-weight: bold; margin: 14px 0 4px; padding-bottom: 2px; border-bottom: 2px solid #c0394b; color: #111827; }
        .rm-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .rm-table th { text-align: left; padding: 4px 6px; font-size: 8.5px; text-transform: uppercase; color: #6b7280; background: #f3f4f6; border-bottom: 1px solid #d1d5db; }
        .rm-table td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; font-size: 9.5px; }
        .rm-table .n { text-align: right; }
        .rm-table .vacio { color: #9ca3af; text-align: center; }
        .rm-table tr.total td { font-weight: bold; background: #f3f4f6; }
        .rm-kpis td { text-align: center; padding: 6px 3px; border: 1px solid #e5e7eb; }
        .rm-kpis td span { display: block; font-size: 8px; text-transform: uppercase; color: #6b7280; }
        .rm-kpis td b { display: block; font-size: 12px; color: #16a34a; margin-top: 2px; }
        .rm-nota { font-size: 8.5px; color: #6b7280; margin-top: 4px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Resumen Mensual</h1>
        <p>{{ ucfirst($r['mes']->locale('es')->translatedFormat('F Y')) }} — generado el {{ now()->format('d/m/Y') }}</p>
    </div>
    @include('partials.resumen-mensual-tablas', ['r' => $r])
</div>
</body>
</html>
