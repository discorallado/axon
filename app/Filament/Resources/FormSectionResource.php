<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormSectionResource\Pages;
use App\Filament\Resources\FormSectionResource\RelationManagers\QuestionsRelationManager;
use App\Models\FormSection;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class FormSectionResource extends Resource
{
    protected static ?string $model = FormSection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static bool $shouldRegisterNavigation = false;

    public static function getModelLabel(): string
    {
        return 'Sección';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('title')
                ->label(__('forms.section.fields.title'))
                ->required()
                ->maxLength(150)
                ->columnSpanFull(),

            Textarea::make('description')
                ->label(__('forms.section.fields.description'))
                ->rows(2)
                ->columnSpanFull(),

            TextInput::make('sort_order')
                ->label(__('forms.section.fields.sort_order'))
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'edit' => Pages\EditFormSection::route('/{record}/edit'),
        ];
    }
}
