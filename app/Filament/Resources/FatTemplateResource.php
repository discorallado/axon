<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FatTemplateResource\Pages;
use App\Models\FatTemplate;
use App\Models\FatTemplateSection;
use App\Models\TemplateRoleSignature;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FatTemplateResource extends Resource
{
    protected static ?string $model = FatTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Protocolos FAT';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->default(fn () => FatTemplate::generateCode()),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Generar código automático si está vacío
                            }),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('category')
                            ->required()
                            ->maxLength(100)
                            ->suggestions([
                                'Tableros Eléctricos',
                                'Instrumentación',
                                'Equipos Rotativos',
                                'Sistemas de Control',
                                'Transformadores',
                            ]),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Activo'),
                    ])->columns(2),

                Forms\Components\Section::make('Secciones')
                    ->description('Define las secciones principales del protocolo')
                    ->schema([
                        Forms\Components\Repeater::make('sections')
                            ->relationship('sections')
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(20)
                                    ->label('Código'),
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Título'),
                                Forms\Components\Textarea::make('description')
                                    ->rows(2)
                                    ->label('Descripción'),
                                Forms\Components\TextInput::make('order')
                                    ->numeric()
                                    ->default(0)
                                    ->label('Orden'),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                    ])->columnSpanFull(),

                Forms\Components\Section::make('Roles de Firma')
                    ->description('Define qué roles deben firmar este protocolo y en qué orden')
                    ->schema([
                        Forms\Components\Repeater::make('roleSignatures')
                            ->relationship('roleSignatures')
                            ->schema([
                                Forms\Components\TextInput::make('role_name')
                                    ->required()
                                    ->maxLength(100)
                                    ->label('Nombre Interno del Rol'),
                                Forms\Components\TextInput::make('role_display_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Nombre para Mostrar'),
                                Forms\Components\TextInput::make('approval_order')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->default(1)
                                    ->label('Orden de Aprobación'),
                                Forms\Components\Toggle::make('is_required')
                                    ->default(true)
                                    ->label('Obligatorio'),
                                Forms\Components\Select::make('signer_type')
                                    ->options([
                                        'internal' => 'Usuario Interno',
                                        'external' => 'Cliente Externo',
                                    ])
                                    ->default('internal')
                                    ->required()
                                    ->label('Tipo de Firmante'),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['role_display_name'] ?? null),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Tableros Eléctricos' => 'warning',
                        'Instrumentación' => 'info',
                        'Equipos Rotativos' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Activo'),
                Tables\Columns\TextColumn::make('sections_count')
                    ->counts('sections')
                    ->label('Secciones'),
                Tables\Columns\TextColumn::make('executions_count')
                    ->counts('executions')
                    ->label('Ejecuciones'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(fn () => FatTemplate::query()->distinct()->pluck('category', 'category')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->label('Vista Previa')
                    ->url(fn (FatTemplate $record): string => route('filament.admin.resources.fat-templates.preview', $record)),
                Tables\Actions\Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->label('Duplicar')
                    ->requiresConfirmation()
                    ->action(fn (FatTemplate $record) => static::duplicateTemplate($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFatTemplates::route('/'),
            'create' => Pages\CreateFatTemplate::route('/create'),
            'edit' => Pages\EditFatTemplate::route('/{record}/edit'),
            'preview' => Pages\PreviewFatTemplate::route('/{record}/preview'),
        ];
    }

    public static function duplicateTemplate(FatTemplate $template): FatTemplate
    {
        $newTemplate = $template->replicate();
        $newTemplate->code = FatTemplate::generateCode($template->category);
        $newTemplate->name = $template->name . ' (Copia)';
        $newTemplate->save();

        // Duplicar secciones e items
        $template->sections->each(function (FatTemplateSection $section) use ($newTemplate) {
            $newSection = $section->replicate();
            $newSection->template_id = $newTemplate->id;
            $newSection->save();

            $section->items->each(function ($item) use ($newSection) {
                $newItem = $item->replicate();
                $newItem->section_id = $newSection->id;
                $newItem->save();
            });
        });

        // Duplicar roles de firma
        $template->roleSignatures->each(function (TemplateRoleSignature $role) use ($newTemplate) {
            $newRole = $role->replicate();
            $newRole->template_id = $newTemplate->id;
            $newRole->save();
        });

        return $newTemplate;
    }
}
