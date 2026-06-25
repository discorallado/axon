<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class ProjectMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('projects.members.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('user_id')
                ->label(__('projects.members.fields.user'))
                ->options(
                    User::withoutGlobalScopes()
                        ->where('organization_id', $this->getOwnerRecord()->organization_id)
                        ->where('is_active', true)
                        ->pluck('name', 'id')
                )
                ->searchable()
                ->preload()
                ->required(),

            Select::make('role')
                ->label(__('projects.members.fields.role'))
                ->options(Role::pluck('name', 'name'))
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('projects.members.fields.user'))
                    ->searchable(),

                TextColumn::make('user.position')
                    ->label('Cargo')
                    ->placeholder('—'),

                TextColumn::make('role')
                    ->label(__('projects.members.fields.role'))
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
