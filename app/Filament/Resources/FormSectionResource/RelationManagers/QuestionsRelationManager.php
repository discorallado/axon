<?php

namespace App\Filament\Resources\FormSectionResource\RelationManagers;

use App\Enums\FormQuestionType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Preguntas';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('label')
                ->label(__('forms.question.fields.label'))
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, $record) {
                    if (! $record) {
                        $set('key', Str::snake(Str::ascii($state)));
                    }
                })
                ->columnSpanFull(),

            TextInput::make('key')
                ->label(__('forms.question.fields.key'))
                ->required()
                ->maxLength(80)
                ->helperText('Identificador interno. No cambia al renombrar la pregunta.'),

            Select::make('type')
                ->label(__('forms.question.fields.type'))
                ->options(FormQuestionType::selectOptions())
                ->required()
                ->default(FormQuestionType::Text->value)
                ->live(),

            TextInput::make('placeholder')
                ->label(__('forms.question.fields.placeholder'))
                ->maxLength(255),

            TextInput::make('help_text')
                ->label(__('forms.question.fields.help_text'))
                ->maxLength(500)
                ->columnSpanFull(),

            Toggle::make('is_required')
                ->label(__('forms.question.fields.is_required')),

            TextInput::make('sort_order')
                ->label(__('forms.question.fields.sort_order'))
                ->numeric()
                ->default(0),

            // Opciones para select/multiselect
            Repeater::make('options')
                ->label(__('forms.question.fields.options'))
                ->schema([
                    TextInput::make('value')->label(__('forms.question.fields.option_value'))->required(),
                    TextInput::make('label')->label(__('forms.question.fields.option_label'))->required(),
                ])
                ->visible(fn ($get) => in_array($get('type'), [
                    FormQuestionType::Select->value,
                    FormQuestionType::Multiselect->value,
                ]))
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),

                TextColumn::make('label')
                    ->label('Pregunta')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => __("forms.question.types.{$state}"))
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_required')
                    ->label('Oblig.')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $section = $this->getOwnerRecord();
                        $data['organization_id'] = $section->organization_id;
                        $data['form_template_id'] = $section->form_template_id;
                        $data['template_version'] = $section->template_version;

                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
