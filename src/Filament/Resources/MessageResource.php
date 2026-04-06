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
use NettSite\Messenger\Filament\Resources\MessageResource\Pages;
use NettSite\Messenger\Filament\Resources\MessageResource\RelationManagers\ConversationsRelationManager;
use NettSite\Messenger\Models\Group;
use NettSite\Messenger\Models\Message;

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
                    'all' => 'All Users',
                    'group' => 'Group',
                    'user' => 'Individual User',
                ])
                ->required()
                ->live(),
            Select::make('recipient_id')
                ->label('Recipient')
                ->options(function (Get $get) {
                    /** @var class-string $userModel */
                    $userModel = config('messenger.user_model');

                    return match ($get('recipient_type')) {
                        'group' => Group::pluck('name', 'id'),
                        'user' => $userModel::pluck('name', 'id'),
                        default => [],
                    };
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
                TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Pending'),
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
