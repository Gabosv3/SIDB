<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ViewCliente extends ViewRecord
{
    protected static string $resource = ClienteResource::class;

    protected static ?string $title = 'Perfil del Cliente';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Cliente')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Datos Personales')
                        ->icon('heroicon-m-user-circle')
                        ->components([
                            Section::make('Identificación')
                                ->columns(3)
                                ->components([
                                    TextEntry::make('nombre')
                                        ->label('Nombre(s)'),

                                    TextEntry::make('apellido')
                                        ->label('Apellido(s)'),

                                    TextEntry::make('nombre_conyuge')
                                        ->label('Cónyuge')
                                        ->placeholder('—'),

                                    TextEntry::make('dui')
                                        ->label('DUI')
                                        ->placeholder('—')
                                        ->copyable(),

                                    TextEntry::make('nit')
                                        ->label('NIT')
                                        ->placeholder('—'),

                                    IconEntry::make('activo')
                                        ->label('Estado')
                                        ->boolean(),
                                ]),

                            Section::make('Fotografías del DUI')
                                ->description('Fotos de ambos lados del DUI')
                                ->icon('heroicon-m-camera')
                                ->columns(2)
                                ->components([
                                    ImageEntry::make('dui_foto_frente')
                                        ->label('DUI — Frente')
                                        ->disk('public')
                                        ->visibility('public')
                                        ->height(200)
                                        ->placeholder('Sin foto'),

                                    ImageEntry::make('dui_foto_reverso')
                                        ->label('DUI — Reverso')
                                        ->disk('public')
                                        ->visibility('public')
                                        ->height(200)
                                        ->placeholder('Sin foto'),
                                ]),

                            Section::make('Fotografía de la Casa')
                                ->description('Foto de la vivienda del cliente')
                                ->icon('heroicon-m-home')
                                ->columns(1)
                                ->components([
                                    ImageEntry::make('foto_casa')
                                        ->label('Casa del Cliente')
                                        ->disk('public')
                                        ->visibility('public')
                                        ->height(250)
                                        ->placeholder('Sin foto'),
                                ]),

                            Section::make('Contacto')
                                ->columns(3)
                                ->components([
                                    TextEntry::make('telefono_normal')
                                        ->label('Teléfono')
                                        ->placeholder('—'),

                                    TextEntry::make('telefono_whatsapp')
                                        ->label('WhatsApp')
                                        ->placeholder('—'),

                                    TextEntry::make('email')
                                        ->label('Email')
                                        ->placeholder('—'),
                                ]),

                            Section::make('Ubicación')
                                ->columns(3)
                                ->components([
                                    TextEntry::make('departamento')
                                        ->label('Departamento')
                                        ->placeholder('—'),

                                    TextEntry::make('municipio')
                                        ->label('Municipio')
                                        ->placeholder('—'),

                                    TextEntry::make('distrito')
                                        ->label('Distrito')
                                        ->placeholder('—'),

                                    TextEntry::make('direccion')
                                        ->label('Dirección')
                                        ->placeholder('—')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tabs\Tab::make('Crédito')
                        ->icon('heroicon-m-banknotes')
                        ->components([
                            Section::make('Estado de Cuenta')
                                ->columns(2)
                                ->components([
                                    TextEntry::make('limite_credito')
                                        ->label('Límite de Crédito')
                                        ->money('USD'),

                                    TextEntry::make('saldo')
                                        ->label('Saldo Pendiente Total')
                                        ->money('USD')
                                        ->color(fn ($state): string => (float)$state > 0 ? 'danger' : 'success')
                                        ->weight('bold'),

                                    TextEntry::make('rutaCobro.nombre')
                                        ->label('Ruta de Cobro')
                                        ->placeholder('Sin ruta asignada')
                                        ->badge()
                                        ->color('info'),

                                    TextEntry::make('sucursal.nombre')
                                        ->label('Sucursal')
                                        ->placeholder('—'),
                                ]),
                        ]),

                    Tabs\Tab::make('Referencias')
                        ->icon('heroicon-m-user-group')
                        ->components([
                            Section::make('Referencias Familiares')
                                ->columns(2)
                                ->components([
                                    TextEntry::make('ref_fam1_nombre')
                                        ->label('Familiar 1')
                                        ->placeholder('—'),

                                    TextEntry::make('ref_fam1_telefono')
                                        ->label('Teléfono')
                                        ->placeholder('—'),

                                    TextEntry::make('ref_fam1_parentesco')
                                        ->label('Parentesco')
                                        ->placeholder('—'),

                                    TextEntry::make('ref_fam2_nombre')
                                        ->label('Familiar 2')
                                        ->placeholder('—'),

                                    TextEntry::make('ref_fam2_telefono')
                                        ->label('Teléfono')
                                        ->placeholder('—'),

                                    TextEntry::make('ref_fam2_parentesco')
                                        ->label('Parentesco')
                                        ->placeholder('—'),
                                ]),

                            Section::make('Referencias Conocidas')
                                ->columns(2)
                                ->components([
                                    TextEntry::make('ref_con1_nombre')
                                        ->label('Conocido 1')
                                        ->placeholder('—'),

                                    TextEntry::make('ref_con1_telefono')
                                        ->label('Teléfono')
                                        ->placeholder('—'),

                                    TextEntry::make('ref_con1_trabajo')
                                        ->label('Trabajo')
                                        ->placeholder('—'),

                                    TextEntry::make('ref_con2_nombre')
                                        ->label('Conocido 2')
                                        ->placeholder('—'),

                                    TextEntry::make('ref_con2_telefono')
                                        ->label('Teléfono')
                                        ->placeholder('—'),

                                    TextEntry::make('ref_con2_trabajo')
                                        ->label('Trabajo')
                                        ->placeholder('—'),
                                ]),
                        ]),
                ]),
        ]);
    }
}