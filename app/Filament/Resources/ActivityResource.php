<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\Activity;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'activities';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('activity_id')
                    ->required()
                    ->integer(),

                TextInput::make('name')
                    ->required(),

                TextInput::make('type')
                    ->required(),

                TextInput::make('distance')
                    ->numeric(),

                TextInput::make('moving_time')
                    ->integer(),

                TextInput::make('elapsed_time')
                    ->integer(),

                TextInput::make('total_elevation_gain')
                    ->numeric(),

                DatePicker::make('started_at')
                    ->label('Started Date'),

                TextInput::make('path')
                    ->required(),

                TextInput::make('max_elevation')
                    ->numeric(),

                TextInput::make('min_elevation')
                    ->numeric(),

                TextInput::make('description'),

                TextEntry::make('created_at')
                    ->label('Created Date')
                    ->state(fn(?Activity $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                TextEntry::make('updated_at')
                    ->label('Last Modified Date')
                    ->state(fn(?Activity $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('activity_id'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type'),

                TextColumn::make('distance'),

                TextColumn::make('moving_time'),

                TextColumn::make('elapsed_time'),

                TextColumn::make('total_elevation_gain'),

                TextColumn::make('started_at')
                    ->label('Started Date')
                    ->date(),

                TextColumn::make('path'),

                TextColumn::make('max_elevation'),

                TextColumn::make('min_elevation'),

                TextColumn::make('description'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
