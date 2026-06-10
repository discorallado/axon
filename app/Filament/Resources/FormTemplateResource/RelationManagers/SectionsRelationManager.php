<?php

namespace App\Filament\Resources\FormTemplateResource\RelationManagers;

use App\Filament\Resources\FormSectionResource;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                    ->label('Gestionar preguntas')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn ($record) => FormSectionResource::getUrl('edit', ['record' => $record])),

                EditAction::make(),

                DeleteAction::make()
                    ->visible(fn ($record) => $record->questions()->doesntExist()),
            ]);
    }
}
