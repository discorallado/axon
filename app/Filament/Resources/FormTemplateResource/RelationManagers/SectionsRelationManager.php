<?php

namespace App\Filament\Resources\FormTemplateResource\RelationManagers;

use App\Enums\FormQuestionType;
use App\Models\FormSection;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'currentSections';

    protected static ?string $title = 'Secciones';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('title')
                ->label(__('forms.section.fields.title'))
                ->required()
                ->maxLength(150),

            Textarea::make('description')
                ->label(__('forms.section.fields.description'))
                ->rows(2),

            TextInput::make('sort_order')
                ->label(__('forms.section.fields.sort_order'))
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),

                TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),

                TextColumn::make('template_version')
                    ->label('Versión')
                    ->prefix('v'),

                TextColumn::make('questions_count')
                    ->label('Preguntas')
                    ->counts('questions'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['organization_id'] = $this->getOwnerRecord()->organization_id;
                        $data['form_template_id'] = $this->getOwnerRecord()->id;
                        $data['template_version'] = $this->getOwnerRecord()->current_version;

                        return $data;
                    }),
            ])
            ->actions([
                Action::make('manage_questions')
                    ->label('Preguntas')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->modalHeading(fn (FormSection $record) => "Preguntas · {$record->title}")
                    ->modalWidth('4xl')
                    ->fillForm(fn (FormSection $record): array => [
                        'questions' => $record->questions()
                            ->orderBy('sort_order')
                            ->get()
                            ->map(fn ($q) => [
                                'id'          => $q->id,
                                'label'       => $q->label,
                                'key'         => $q->key,
                                'type'        => $q->type->value,
                                'placeholder' => $q->placeholder,
                                'help_text'   => $q->help_text,
                                'is_required' => $q->is_required,
                                'options'     => $q->options ?? [],
                            ])
                            ->all(),
                    ])
                    ->form([
                        Repeater::make('questions')
                            ->label(false)
                            ->schema([
                                Hidden::make('id'),

                                TextInput::make('label')
                                    ->label('Etiqueta')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if (blank($get('key'))) {
                                            $set('key', Str::snake(Str::ascii($state)));
                                        }
                                    })
                                    ->columnSpan(2),

                                TextInput::make('key')
                                    ->label('Key interno')
                                    ->required()
                                    ->maxLength(80)
                                    ->helperText('Solo letras, números y guión bajo.'),

                                Select::make('type')
                                    ->label('Tipo')
                                    ->options(FormQuestionType::selectOptions())
                                    ->required()
                                    ->default(FormQuestionType::Text->value)
                                    ->live(),

                                Toggle::make('is_required')
                                    ->label('Obligatoria')
                                    ->columnSpan(2),

                                TextInput::make('placeholder')
                                    ->label('Placeholder')
                                    ->maxLength(255),

                                TextInput::make('help_text')
                                    ->label('Texto de ayuda')
                                    ->maxLength(500),

                                Repeater::make('options')
                                    ->label('Opciones de selección')
                                    ->schema([
                                        TextInput::make('value')
                                            ->label('Valor')
                                            ->required()
                                            ->maxLength(100),
                                        TextInput::make('label')
                                            ->label('Etiqueta')
                                            ->required()
                                            ->maxLength(200),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel('Agregar opción')
                                    ->visible(fn (callable $get) => in_array($get('type'), [
                                        FormQuestionType::Select->value,
                                        FormQuestionType::Multiselect->value,
                                    ]))
                                    ->columnSpan(2),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Nueva pregunta')
                            ->collapsible()
                            ->collapsed()
                            ->addActionLabel('Agregar pregunta')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->action(function (FormSection $record, array $data): void {
                        $keptIds = [];

                        foreach ($data['questions'] as $index => $item) {
                            $payload = [
                                'organization_id'  => $record->organization_id,
                                'form_template_id' => $record->form_template_id,
                                'template_version' => $record->template_version,
                                'form_section_id'  => $record->id,
                                'label'            => $item['label'],
                                'key'              => $item['key'],
                                'type'             => $item['type'],
                                'placeholder'      => $item['placeholder'] ?? null,
                                'help_text'        => $item['help_text'] ?? null,
                                'is_required'      => $item['is_required'] ?? false,
                                'sort_order'       => $index,
                                'options'          => $item['options'] ?? null,
                            ];

                            if (! empty($item['id'])) {
                                $record->questions()->where('id', $item['id'])->update($payload);
                                $keptIds[] = $item['id'];
                            } else {
                                $q = $record->questions()->create($payload);
                                $keptIds[] = $q->id;
                            }
                        }

                        $record->questions()->whereNotIn('id', $keptIds)->delete();

                        Notification::make()
                            ->title('Preguntas guardadas.')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),

                DeleteAction::make()
                    ->visible(fn ($record) => $record->questions()->doesntExist()),
            ]);
    }
}
