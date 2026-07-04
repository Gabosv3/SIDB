<?php

namespace App\Filament\Pages;

use App\Models\ConfiguracionSistema;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Validation\Rules\File;

class PersonalizacionSistema extends Page
{
    public ?array $data = [];

    protected Width | string | null $maxContentWidth = Width::FourExtraLarge;

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-paint-brush';
    }

    public static function getNavigationLabel(): string
    {
        return 'Personalización';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Sistema';
    }

    public static function getNavigationSort(): ?int
    {
        return 92;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }

    // ── Ciclo de vida ─────────────────────────────────────────────────────────

    public function mount(): void
    {
        $config = ConfiguracionSistema::instance();
        $this->form->fill($config->toArray());
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $config = ConfiguracionSistema::firstOrCreate(['id' => 1]);

        // Si el campo de imagen viene vacío, preservar el valor actual
        foreach (['logo', 'logo_dark', 'favicon'] as $field) {
            if (empty($data[$field])) {
                unset($data[$field]);
            }
        }

        $config->update($data);
        ConfiguracionSistema::clearCache();

        Notification::make()
            ->title('Configuración guardada correctamente')
            ->success()
            ->send();
    }

    // ── Contenido de la página ────────────────────────────────────────────────

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('guardar')
                            ->label('Guardar configuración')
                            ->submit('save')
                            ->color('primary')
                            ->icon('heroicon-m-check'),
                    ])
                    ->alignment(Alignment::End),
                ]),
        ]);
    }

    // ── Formulario ────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([

                // ── Identidad ─────────────────────────────────────────────────
                Section::make('Identidad del sistema')
                    ->description('Nombre, descripción y marca visual del sistema.')
                    ->icon('heroicon-m-identification')
                    ->columns(2)
                    ->components([
                        Forms\Components\TextInput::make('app_name')
                            ->label('Nombre del sistema')
                            ->placeholder('Ej: SIDB Sistema')
                            ->required()
                            ->string()
                            ->minLength(2)
                            ->maxLength(100)
                            ->validationMessages([
                                'required' => 'El nombre del sistema es obligatorio.',
                                'min'      => 'Debe tener al menos 2 caracteres.',
                                'max'      => 'No puede superar los 100 caracteres.',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('app_description')
                            ->label('Descripción corta')
                            ->placeholder('Ej: Sistema Integrado de Distribución y Bodega')
                            ->string()
                            ->maxLength(255)
                            ->validationMessages([
                                'max' => 'No puede superar los 255 caracteres.',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo principal (fondo claro)')
                            ->helperText('PNG, JPG, WEBP o SVG. Máx 5 MB. Recomendado: 300×80 px.')
                            ->disk('public')
                            ->directory('configuracion')
                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'])
                            ->maxSize(5120)
                            ->imagePreviewHeight('80')
                            ->validationMessages([
                                'max_size' => 'El logo no puede superar los 5 MB.',
                                'mimes'    => 'Solo se aceptan PNG, JPG, WEBP o SVG.',
                            ])
                            ->columnSpan(1),

                        Forms\Components\FileUpload::make('logo_dark')
                            ->label('Logo versión oscura (fondo oscuro)')
                            ->helperText('PNG, JPG, WEBP o SVG. Máx 5 MB. Para modo oscuro.')
                            ->disk('public')
                            ->directory('configuracion')
                            ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'])
                            ->maxSize(5120)
                            ->imagePreviewHeight('80')
                            ->validationMessages([
                                'max_size' => 'El logo oscuro no puede superar los 5 MB.',
                                'mimes'    => 'Solo se aceptan PNG, JPG, WEBP o SVG.',
                            ])
                            ->columnSpan(1),

                        Forms\Components\FileUpload::make('favicon')
                            ->label('Favicon / Ícono')
                            ->helperText('ICO, PNG, SVG o JPG. Máx 2 MB. Recomendado: 64×64 px.')
                            ->disk('public')
                            ->directory('configuracion')
                            ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/svg+xml', 'image/jpeg', 'image/jpg', 'image/webp'])
                            ->maxSize(2048)
                            ->imagePreviewHeight('64')
                            ->validationMessages([
                                'max_size' => 'El favicon no puede superar los 2 MB.',
                                'mimes'    => 'Solo se aceptan ICO, PNG, SVG o JPG.',
                            ])
                            ->columnSpan(1),
                    ]),

                // ── Apariencia ────────────────────────────────────────────────
                Section::make('Color principal')
                    ->description('Define el color primario del panel de administración.')
                    ->icon('heroicon-m-swatch')
                    ->columns(2)
                    ->components([
                        Forms\Components\ColorPicker::make('primary_color')
                            ->label('Color principal (hex)')
                            ->helperText('Este color se aplica a botones, badges y elementos de acento. Guarda y recarga la página para ver el cambio.')
                            ->regex('/^#[0-9a-fA-F]{6}$/')
                            ->validationMessages([
                                'regex' => 'Debe ser un color hexadecimal válido (Ej: #ff5500).',
                            ])
                            ->columnSpan(1),

                        Forms\Components\Placeholder::make('color_preview')
                            ->label('Vista previa')
                            ->content('El color se aplica automáticamente al guardar, sin necesidad de compilar assets.')
                            ->columnSpan(1),
                    ]),

                // ── Contacto ──────────────────────────────────────────────────
                Section::make('Información de contacto')
                    ->description('Datos que aparecen en el pie de página y correos del sistema.')
                    ->icon('heroicon-m-phone')
                    ->columns(2)
                    ->components([
                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono principal')
                            ->tel()
                            ->placeholder('+503 7777-8888')
                            ->regex('/^[\+\d\s\-\(\)]{7,20}$/')
                            ->maxLength(30)
                            ->validationMessages([
                                'regex' => 'Ingresa un número de teléfono válido.',
                                'max'   => 'No puede superar los 30 caracteres.',
                            ]),

                        Forms\Components\TextInput::make('correo_contacto')
                            ->label('Correo de contacto')
                            ->email()
                            ->placeholder('contacto@empresa.com')
                            ->maxLength(150)
                            ->validationMessages([
                                'email' => 'Ingresa un correo electrónico válido.',
                                'max'   => 'No puede superar los 150 caracteres.',
                            ]),

                        Forms\Components\Textarea::make('direccion')
                            ->label('Dirección física')
                            ->placeholder('Calle Principal, Ciudad, País')
                            ->rows(2)
                            ->maxLength(500)
                            ->validationMessages([
                                'max' => 'La dirección no puede superar los 500 caracteres.',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('horario')
                            ->label('Horario de atención')
                            ->placeholder('Lun–Vie 8:00 – 17:00')
                            ->maxLength(100)
                            ->validationMessages([
                                'max' => 'No puede superar los 100 caracteres.',
                            ])
                            ->columnSpanFull(),
                    ]),

                // ── Redes Sociales ────────────────────────────────────────────
                Section::make('Redes sociales')
                    ->description('URLs completas a los perfiles oficiales. Dejar en blanco para no mostrar.')
                    ->icon('heroicon-m-globe-alt')
                    ->columns(2)
                    ->components([
                        Forms\Components\TextInput::make('facebook_url')
                            ->label('Facebook')
                            ->url()
                            ->placeholder('https://facebook.com/empresa')
                            ->maxLength(255)
                            ->regex('/^https?:\/\/(www\.)?facebook\.com\/.+/i')
                            ->validationMessages([
                                'url'   => 'Ingresa una URL válida.',
                                'regex' => 'Debe ser una URL de Facebook (https://facebook.com/...).',
                                'max'   => 'No puede superar los 255 caracteres.',
                            ])
                            ->prefixIcon('heroicon-m-link'),

                        Forms\Components\TextInput::make('instagram_url')
                            ->label('Instagram')
                            ->url()
                            ->placeholder('https://instagram.com/empresa')
                            ->maxLength(255)
                            ->regex('/^https?:\/\/(www\.)?instagram\.com\/.+/i')
                            ->validationMessages([
                                'url'   => 'Ingresa una URL válida.',
                                'regex' => 'Debe ser una URL de Instagram (https://instagram.com/...).',
                                'max'   => 'No puede superar los 255 caracteres.',
                            ])
                            ->prefixIcon('heroicon-m-link'),

                        Forms\Components\TextInput::make('whatsapp_number')
                            ->label('WhatsApp (número con código de país)')
                            ->tel()
                            ->placeholder('+50377778888')
                            ->maxLength(20)
                            ->regex('/^\+\d{7,15}$/')
                            ->helperText('Sin espacios ni guiones. Ej: +50377778888')
                            ->validationMessages([
                                'regex' => 'Debe iniciar con + seguido del código de país y número. Ej: +50377778888.',
                                'max'   => 'No puede superar los 20 caracteres.',
                            ])
                            ->prefixIcon('heroicon-m-chat-bubble-left'),

                        Forms\Components\TextInput::make('twitter_url')
                            ->label('X / Twitter')
                            ->url()
                            ->placeholder('https://x.com/empresa')
                            ->maxLength(255)
                            ->regex('/^https?:\/\/(www\.)?(x|twitter)\.com\/.+/i')
                            ->validationMessages([
                                'url'   => 'Ingresa una URL válida.',
                                'regex' => 'Debe ser una URL de X/Twitter (https://x.com/... o https://twitter.com/...).',
                                'max'   => 'No puede superar los 255 caracteres.',
                            ])
                            ->prefixIcon('heroicon-m-link'),

                        Forms\Components\TextInput::make('tiktok_url')
                            ->label('TikTok')
                            ->url()
                            ->placeholder('https://tiktok.com/@empresa')
                            ->maxLength(255)
                            ->regex('/^https?:\/\/(www\.)?tiktok\.com\/.+/i')
                            ->validationMessages([
                                'url'   => 'Ingresa una URL válida.',
                                'regex' => 'Debe ser una URL de TikTok (https://tiktok.com/@...).',
                                'max'   => 'No puede superar los 255 caracteres.',
                            ])
                            ->prefixIcon('heroicon-m-link'),

                        Forms\Components\TextInput::make('youtube_url')
                            ->label('YouTube')
                            ->url()
                            ->placeholder('https://youtube.com/@empresa')
                            ->maxLength(255)
                            ->regex('/^https?:\/\/(www\.)?youtube\.com\/.+/i')
                            ->validationMessages([
                                'url'   => 'Ingresa una URL válida.',
                                'regex' => 'Debe ser una URL de YouTube (https://youtube.com/...).',
                                'max'   => 'No puede superar los 255 caracteres.',
                            ])
                            ->prefixIcon('heroicon-m-link'),
                    ]),

                // ── Pie de página ─────────────────────────────────────────────
                Section::make('Pie de página')
                    ->description('Texto que aparece al final de las páginas y en los correos.')
                    ->icon('heroicon-m-document-text')
                    ->components([
                        Forms\Components\TextInput::make('footer_texto')
                            ->label('Texto del pie de página')
                            ->placeholder('Todos los derechos reservados.')
                            ->maxLength(255)
                            ->validationMessages([
                                'max' => 'No puede superar los 255 caracteres.',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('copyright_texto')
                            ->label('Texto de copyright')
                            ->placeholder('© 2026 Mi Empresa S.A. de C.V.')
                            ->maxLength(255)
                            ->validationMessages([
                                'max' => 'No puede superar los 255 caracteres.',
                            ])
                            ->columnSpanFull(),
                    ]),

                // ── App móvil (POS) ────────────────────────────────────────────
                Section::make('Aplicación móvil (POS)')
                    ->description('Datos de la última versión publicada del APK. La app los consulta al abrir para avisar si hay una actualización disponible.')
                    ->icon('heroicon-m-device-phone-mobile')
                    ->components([
                        Forms\Components\TextInput::make('apk_version')
                            ->label('Versión')
                            ->placeholder('1.0.1')
                            ->maxLength(20)
                            ->validationMessages([
                                'max' => 'No puede superar los 20 caracteres.',
                            ]),

                        Forms\Components\TextInput::make('apk_url')
                            ->label('URL de descarga')
                            ->placeholder('https://panel.midominio.com/update/sidb.apk')
                            ->url()
                            ->maxLength(500)
                            ->validationMessages([
                                'url' => 'Debe ser una URL válida.',
                                'max' => 'No puede superar los 500 caracteres.',
                            ])
                            ->prefixIcon('heroicon-m-link'),

                        Forms\Components\Textarea::make('apk_notas')
                            ->label('Notas de la versión')
                            ->placeholder('Correcciones de errores y nuevo sistema de orden de clientes')
                            ->rows(2)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

            ]);
    }
}
