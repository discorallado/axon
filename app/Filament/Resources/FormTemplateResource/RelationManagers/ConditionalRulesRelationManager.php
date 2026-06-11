<?php

namespace App\Filament\Resources\FormTemplateResource\RelationManagers;

use App\Enums\ConditionalOperator;
use App\Models\FormConditionalRule;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConditionalRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'currentConditionalRules';

    protected static ?string $title = 'Reglas condicionales';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([

            Select::make('trigger_question_id')
                ->label(__('forms.rule.fields.trigger_question'))
                ->options(fn () => $this->getOwnerRecord()
                    ->currentQuestions()
                    ->orderBy('sort_order')
                    ->pluck('label', 'id')
                )
                ->searchable()
                ->required()
                ->columnSpanFull(),

            Select::make('operator')
                ->label(__('forms.rule.fields.operator'))
                ->options(ConditionalOperator::selectOptions())
                ->required()
                ->live()
                ->default(ConditionalOperator::Eq->value),

            TextInput::make('trigger_value')
                ->label(__('forms.rule.fields.trigger_value'))
                ->maxLength(255)
                ->visible(fn (callable $get): bool => ConditionalOperator::tryFrom($get('operator'))?->needsValue() ?? true),

            Select::make('action')
                ->label(__('forms.rule.fields.action'))
                ->options(fn () => (array) __('forms.rule.actions'))
                ->required()
                ->default('show'),

            Select::make('target_type')
                ->label(__('forms.rule.fields.target_type'))
                ->options(fn () => (array) __('forms.rule.target_types'))
                ->required()
                ->live()
                ->default('question'),

            Select::make('target_question_id')
                ->label(__('forms.rule.fields.target_question'))
                ->options(fn () => $this->getOwnerRecord()
                    ->currentQuestions()
                    ->orderBy('sort_order')
                    ->pluck('label', 'id')
                )
                ->searchable()
                ->visible(fn (callable $get): bool => $get('target_type') === 'question')
                ->required(fn (callable $get): bool => $get('target_type') === 'question'),

            Select::make('target_section_id')
                ->label(__('forms.rule.fields.target_section'))
                ->options(fn () => $this->getOwnerRecord()
                    ->currentSections()
                    ->orderBy('sort_order')
                    ->pluck('title', 'id')
                )
                ->searchable()
                ->visible(fn (callable $get): bool => $get('target_type') === 'section')
                ->required(fn (callable $get): bool => $get('target_type') === 'section'),

        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('triggerQuestion.label')
                    ->label('Si la pregunta')
                    ->limit(40)
                    ->searchable(),

                TextColumn::make('operator')
                    ->label('Condicion')
                    ->formatStateUsing(function (FormConditionalRule $record): string {
                        $op = $record->operator->label();

                        return $record->trigger_value !== null
                            ? "{$op} \"{$record->trigger_value}\""
                            : $op;
                    }),

                TextColumn::make('action')
                    ->label('Accion')
                    ->formatStateUsing(fn ($state) => __("forms.rule.actions.{$state}"))
                    ->badge()
                    ->color(fn ($state) => $state === 'show' ? 'success' : 'danger'),

                TextColumn::make('target_type')
                    ->label('Objetivo')
                    ->formatStateUsing(function (FormConditionalRule $record): string {
                        $type = __("forms.rule.target_types.{$record->target_type}");
                        $label = $record->target_type === 'question'
                            ? $record->targetQuestion?->label
                            : $record->targetSection?->title;

                        return "{$type}: {$label}";
                    })
                    ->limit(50),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $owner = $this->getOwnerRecord();
                        $data['organization_id']  = $owner->organization_id;
                        $data['form_template_id'] = $owner->id;
                        $data['template_version'] = $owner->current_version;

                        if ($data['target_type'] === 'question') {
                            $data['target_section_id'] = null;
                        } else {
                            $data['target_question_id'] = null;
                        }

                        if (! ConditionalOperator::tryFrom($data['operator'])?->needsValue()) {
                            $data['trigger_value'] = null;
                        }

                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if ($data['target_type'] === 'question') {
                            $data['target_section_id'] = null;
                        } else {
                            $data['target_question_id'] = null;
                        }

                        if (! ConditionalOperator::tryFrom($data['operator'])?->needsValue()) {
                            $data['trigger_value'] = null;
                        }

                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Sin reglas condicionales')
            ->emptyStateDescription('Agrega una regla para mostrar u ocultar preguntas o secciones segun las respuestas.');
    }
}
