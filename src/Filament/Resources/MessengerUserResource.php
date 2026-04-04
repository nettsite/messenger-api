<?php

namespace NettSite\Messenger\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use NettSite\Messenger\Enums\UserStatus;
use NettSite\Messenger\Filament\Resources\MessengerUserResource\Pages;
use NettSite\Messenger\Models\MessengerUser;

class MessengerUserResource extends Resource
{
    protected static ?string $model = MessengerUser::class;

    protected static ?string $navigationLabel = 'Users';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-users';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required(),
            TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('password')
                ->password()
                ->required(fn (string $context) => $context === 'create')
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->dehydrated(fn ($state) => filled($state)),
            Select::make('status')
                ->options(UserStatus::class)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (UserStatus $state): string => $state->color()),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (MessengerUser $record): bool => $record->isPending())
                    ->action(fn (MessengerUser $record) => $record->update(['status' => UserStatus::Active])),
                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (MessengerUser $record): bool => $record->isActive())
                    ->action(fn (MessengerUser $record) => $record->update(['status' => UserStatus::Suspended])),
                Action::make('reactivate')
                    ->label('Reactivate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (MessengerUser $record): bool => $record->isSuspended())
                    ->action(fn (MessengerUser $record) => $record->update(['status' => UserStatus::Active])),
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
            'index' => Pages\ListMessengerUsers::route('/'),
            'create' => Pages\CreateMessengerUser::route('/create'),
            'edit' => Pages\EditMessengerUser::route('/{record}/edit'),
        ];
    }
}
