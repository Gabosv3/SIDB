<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

/*  Forzar que toda la cadena de contenedores tome pantalla completa  */
/* Solo cuando existe el layout de login (.sidb-shell) */
html:has(.sidb-shell),
html:has(.sidb-shell) body {
    height: 100%;
    margin: 0;
    padding: 0;
    overflow: hidden;
}

/*
   Este login es un diseño de marca fijo (panel oscuro + panel blanco),
   no debe adaptarse al modo oscuro de Filament/SO: si el dark mode está
   activo, los textos oscuros (#111111, #1a1a1a) quedan casi invisibles
   sobre el fondo oscuro que pinta Filament. Forzamos esquema claro siempre.
*/
html:has(.sidb-shell) {
    color-scheme: light !important;
    background-color: #ffffff !important;
}

.fi-body:has(.sidb-shell) {
    font-family: 'Inter', system-ui, sans-serif !important;
    min-height: 100vh !important;
    height: 100vh !important;
    overflow: hidden !important;
    display: block !important;
    background-color: #ffffff !important;
}
.fi-body:has(.sidb-shell) > * { height: 100%; }

.sidb-right .fi-simple-page,
.sidb-right .fi-simple-page-content,
.sidb-right .fi-simple-main,
.sidb-right .fi-section,
.sidb-right .fi-section-content,
.sidb-right .fi-section-content-ctn {
    background-color: transparent !important;
    box-shadow: none !important;
    border: none !important;
}

/* 
   SHELL: dos columnas que llenan la pantalla completa
 */
.sidb-shell {
    display: grid !important;
    grid-template-columns: 45% 55% !important;
    width: 100vw !important;
    height: 100vh !important;
    overflow: hidden !important;
}

/* 
   PANEL IZQUIERDO  oscuro, identidad de marca
 */
.sidb-left {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background: #0d0d0d !important;
    padding: 3rem 3.5rem;
    overflow: hidden;
}

/* Gradiente sutil en la esquina superior */
.sidb-left-noise {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 70% 45% at 0% 0%,   rgba(255,255,255,.03) 0%, transparent 70%),
        radial-gradient(ellipse 50% 50% at 100% 100%, rgba(255,255,255,.015) 0%, transparent 70%);
    pointer-events: none;
}

/* Línea lateral derecha muy sutil */
.sidb-left::after {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 1px;
    height: 100%;
    background: linear-gradient(to bottom,
        transparent 0%,
        rgba(255,255,255,.06) 30%,
        rgba(255,255,255,.06) 70%,
        transparent 100%);
}

/* Cuerpo central del panel izquierdo */
.sidb-left-body {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 2.25rem;
    margin-top: auto;
    margin-bottom: auto;
}

/* Logo */
.sidb-logo-wrap {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.sidb-logo-icon {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
}
.sidb-logo-tag {
    font-size: 0.5625rem;
    font-weight: 700;
    letter-spacing: 0.35em;
    text-transform: uppercase;
    color: rgba(255,255,255,.25);
}

/* Titular principal */
.sidb-headline {
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
}
.sidb-h1 {
    margin: 0;
    font-size: clamp(2rem, 2.8vw, 3rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.045em;
    color: #ffffff;
}
.sidb-sub {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 400;
    line-height: 1.55;
    color: rgba(255,255,255,.4);
    letter-spacing: -0.01em;
}

/* Divisor */
.sidb-sep {
    width: 24px;
    height: 1px;
    background: rgba(255,255,255,.12);
}

/* Lista de capacidades */
.sidb-feat {
    list-style: none;
    margin: 0; padding: 0;
    display: flex;
    flex-direction: column;
    gap: 1.125rem;
}
.sidb-feat li {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    color: rgba(255,255,255,.35);
    letter-spacing: -0.005em;
}
.sidb-feat-icon {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
    color: rgba(255,255,255,.3);
}

/* Copyright abajo */
.sidb-copy {
    position: relative;
    z-index: 1;
    margin: 0;
    font-size: 0.625rem;
    font-weight: 400;
    color: rgba(255,255,255,.12);
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

/*
   PANEL DERECHO  blanco puro, formulario amplio
 */
.sidb-right {
    display: flex;
    flex-direction: column;
    background: #ffffff !important;
    overflow-y: auto;
    height: 100%;
}

/* Topbar institucional */
.sidb-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 3.5rem;
    height: 60px;
    border-bottom: 1px solid #f0f0f0;
    flex-shrink: 0;
}
.sidb-topbar-label {
    font-size: 0.6875rem;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #555555;
}

/* Área del formulario */
.sidb-form-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 3.5rem;
    min-height: 0;
}

/* Formulario: amplio, sin tarjeta */
.sidb-form {
    width: 100% !important;
    max-width: 480px !important;
}

/* Reset de contenedores Filament — solo dentro del panel derecho del login */
.sidb-right .fi-simple-page {
    padding: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
}
.sidb-right .fi-simple-page-content {
    padding: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
}

/*  Header del formulario  */
.sidb-right .fi-simple-header {
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 0.375rem !important;
    padding: 0 0 2rem !important;
    margin: 0 !important;
    border-bottom: 1px solid #f0f0f0 !important;
}

/* Ocultar logo de Filament */
.sidb-form .fi-logo,
.sidb-right .fi-simple-header .fi-logo { display: none !important; }

.sidb-right .fi-simple-header-heading {
    font-family: 'Inter', sans-serif !important;
    font-size: 1.875rem !important;
    font-weight: 800 !important;
    color: #111111 !important;
    letter-spacing: -0.045em !important;
    line-height: 1.15 !important;
}
.sidb-right .fi-simple-header-subheading {
    font-size: 0.9375rem !important;
    color: #666666 !important;
    font-weight: 400 !important;
    line-height: 1.5 !important;
    letter-spacing: -0.01em !important;
}

/* Espacio entre header y campos */
.sidb-right .fi-simple-page-content { padding-top: 2rem !important; }

/*  Labels  */
.sidb-right .fi-fo-field-label,
.sidb-right .fi-fo-field-label-content {
    font-family: 'Inter', sans-serif !important;
    font-size: 0.8125rem !important;
    font-weight: 600 !important;
    color: #333333 !important;
    letter-spacing: -0.005em !important;
}
.sidb-right .fi-checkbox-field .fi-fo-field-label-content {
    font-size: 0.875rem !important;
    color: #333333 !important;
    font-weight: 500 !important;
}
.sidb-right .fi-fo-field-label-required-mark {
    color: #ef4444 !important;
}

/*  Inputs  */
.sidb-right .fi-input-wrp {
    border-radius: 0.5rem !important;
    background: #fafafa !important;
    border-color: #e8e8e8 !important;
    transition: border-color .15s ease, box-shadow .15s ease, background .15s !important;
}
.sidb-right .fi-input-wrp:focus-within {
    border-color: #111111 !important;
    background: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(17,17,17,.06) !important;
}
.sidb-right .fi-input {
    font-family: 'Inter', sans-serif !important;
    font-size: 0.9375rem !important;
    color: #111111 !important;
    background: transparent !important;
    height: 2.75rem !important;
    padding-left: 0.875rem !important;
    padding-right: 0.875rem !important;
}
.sidb-right .fi-input::placeholder { color: #c2c2c2 !important; }

/*  Botón  */
.sidb-right .fi-btn-color-primary,
.sidb-right .fi-btn-primary {
    font-family: 'Inter', sans-serif !important;
    background: #111111 !important;
    border-color: #111111 !important;
    border-radius: 0.5rem !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    letter-spacing: 0.01em !important;
    height: 2.875rem !important;
    color: #ffffff !important;
    transition: background .15s, box-shadow .15s, transform .1s !important;
}
.sidb-right .fi-btn-color-primary:hover,
.sidb-right .fi-btn-primary:hover {
    background: #2a2a2a !important;
    border-color: #2a2a2a !important;
    box-shadow: 0 4px 20px rgba(17,17,17,.2) !important;
    transform: translateY(-1px) !important;
}
.sidb-right .fi-btn-color-primary:active,
.sidb-right .fi-btn-primary:active { transform: translateY(0) !important; }

/*  Links  */
.sidb-right .fi-link,
.sidb-right .fi-simple-page-content a {
    font-family: 'Inter', sans-serif !important;
    color: #333333 !important;
    font-weight: 500 !important;
    text-decoration: none !important;
    transition: color .12s !important;
}
.sidb-right .fi-link:hover,
.sidb-right .fi-simple-page-content a:hover {
    color: #111111 !important;
}

/*  Checkbox  */
.sidb-right input[type="checkbox"] {
    appearance: auto !important;
    -webkit-appearance: checkbox !important;
    accent-color: #111111 !important;
    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    min-height: 18px !important;
    cursor: pointer !important;
    display: inline-block !important;
    margin: 0 0.5rem 0 0 !important;
}
.sidb-right .fi-checkbox {
    accent-color: #111111 !important;
    width: 18px !important;
    height: 18px !important;
    cursor: pointer !important;
}
.sidb-right .fi-fo-checkbox {
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}
.sidb-right .fi-fo-checkbox-label {
    color: #333333 !important;
    font-weight: 500 !important;
}

/*  Espaciado de campos  */
.sidb-right .fi-fo-component-ctn { gap: 1.25rem !important; }

/*  Errores  */
.sidb-right .fi-fo-field-wrp-error-message {
    font-size: 0.8125rem !important;
    margin-top: 0.25rem !important;
}

/*  Footer del panel derecho  */
.sidb-footer {
    padding: 0 3.5rem 1.5rem;
    font-size: 0.625rem;
    font-weight: 400;
    color: #555555;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    flex-shrink: 0;
}

/* 
   RESPONSIVE
 */

/* Tablet */
@media (max-width: 1100px) {
    .sidb-shell { grid-template-columns: 48% 52% !important; }
    .sidb-left, .sidb-topbar, .sidb-form-wrap, .sidb-footer { padding-left: 2.5rem; padding-right: 2.5rem; }
    .sidb-left { padding-top: 2.5rem; padding-bottom: 2.5rem; }
    .sidb-form-wrap { padding-top: 2.5rem; padding-bottom: 2.5rem; }
}

/* Apilado vertical */
@media (max-width: 860px) {
    html:has(.sidb-shell),
    html:has(.sidb-shell) body { overflow: auto; }
    .fi-body:has(.sidb-shell) { height: auto !important; overflow: auto !important; }
    .sidb-shell {
        grid-template-columns: 1fr !important;
        height: auto !important;
        min-height: 100vh !important;
    }
    .sidb-left {
        padding: 2.5rem 2rem;
        min-height: auto;
    }
    .sidb-left::after { display: none; }
    .sidb-h1 { font-size: 1.875rem; }
    .sidb-right { overflow-y: visible; }
    .sidb-form-wrap { padding: 2.5rem 2rem; }
    .sidb-topbar { padding: 0 2rem; }
    .sidb-footer { padding: 0 2rem 1.25rem; }
}

/* Móvil */
@media (max-width: 520px) {
    .sidb-left { padding: 2rem 1.5rem; }
    .sidb-feat { display: none; }
    .sidb-form-wrap { padding: 2rem 1.5rem; }
    .sidb-topbar { padding: 0 1.5rem; }
    .sidb-footer { padding: 0 1.5rem 1rem; }
    .fi-simple-header-heading { font-size: 1.625rem !important; }
    .sidb-form { max-width: 100% !important; }
}
</style>