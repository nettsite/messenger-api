<?php

namespace NettSite\Messenger\Filament\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use NettSite\Messenger\Enums\UserStatus;
use NettSite\Messenger\Filament\Resources\MessageResource\Pages;
use NettSite\Messenger\Filament\Resources\MessageResource\RelationManagers\ConversationsRelationManager;
use NettSite\Messenger\Models\Group;
use NettSite\Messenger\Models\Message;
use NettSite\Messenger\Models\MessengerEnrollment;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chat-bubble-left-ellipsis';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')
                ->required()
                ->rows(4),
            TextInput::make('url')
                ->url()
                ->nullable(),
            Select::make('recipient_type')
                ->options([
                    'user' => 'Individual User',
                    'group' => 'Group',
                    'all' => 'Everyone',
                ])
                ->default('user')
                ->required()
                ->live(),
            Select::make('recipient_id')
                ->label('Recipient')
                ->options(function (Get $get) {
                    /** @var class-string $userModel */
                    $userModel = config('messenger.user_model');

                    if ($get('recipient_type') === 'group') {
                        return Group::pluck('name', 'id');
                    }

                    if ($get('recipient_type') === 'user') {
                        $enrolledIds = MessengerEnrollment::where('status', UserStatus::Active)
                            ->pluck('user_id');

                        return $userModel::whereIn('id', $enrolledIds)->pluck('name', 'id');
                    }

                    return [];
                })
                ->visible(fn (Get $get) => in_array($get('recipient_type'), ['user', 'group']))
                ->required(fn (Get $get) => in_array($get('recipient_type'), ['user', 'group']))
                ->searchable(),
            DateTimePicker::make('scheduled_at')
                ->nullable(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('body'),
            TextEntry::make('url')
                ->url(fn (Message $record): ?string => $record->url)
                ->placeholder('—'),
            TextEntry::make('recipients.recipient_type')
                ->label('To')
                ->badge(),
            TextEntry::make('scheduled_at')
                ->dateTime()
                ->placeholder('—'),
            TextEntry::make('sent_at')
                ->dateTime()
                ->placeholder('Pending'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('body')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('recipients.recipient_type')
                    ->label('To')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (Message $record): string => match (true) {
                        $record->failed_at !== null => 'Failed',
                        $record->sent_at !== null => 'Sent',
                        default => 'Pending',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Sent' => 'success',
                        'Failed' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('read_stats')
                    ->label('Read')
                    ->state(fn (Message $record) => $record->readCount().'/'.$record->recipientCount()),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Message $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [
            ConversationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
            'create' => Pages\CreateMessage::route('/create'),
            'view' => Pages\ViewMessage::route('/{record}'),
        ];
    }
}
