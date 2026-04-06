<?php

namespace NettSite\Messenger\Filament\Resources\MessageResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use NettSite\Messenger\Jobs\SendConversationMessageJob;
use NettSite\Messenger\Models\Conversation;
use NettSite\Messenger\Models\ConversationMessage;

class ConversationsRelationManager extends RelationManager
{
    protected static string $relationship = 'conversations';

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('user_name')
                ->label('User')
                ->state(fn (Conversation $record): string => $record->user?->name ?? '—'),
            TextEntry::make('messages_count')
                ->label('Messages')
                ->state(fn (Conversation $record): int => $record->messages()->count()),
            RepeatableEntry::make('messages')
                ->label('Thread')
                ->schema([
                    TextEntry::make('author_name')
                        ->label('From')
                        ->state(fn (ConversationMessage $record): string => $record->author?->name ?? '—'),
                    TextEntry::make('created_at')
                        ->label('Sent')
                        ->dateTime(),
                    TextEntry::make('read_at')
                        ->label('Read')
                        ->dateTime()
                        ->placeholder('Unread'),
                    TextEntry::make('body')
                        ->columnSpanFull(),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_name')
                    ->label('User')
                    ->state(fn (Conversation $record): string => $record->user?->name ?? '—'),
                TextColumn::make('last_message')
                    ->label('Last message')
                    ->state(fn (Conversation $record): string => $record->messages()->latest()->value('body') ?? '—')
                    ->limit(60),
                TextColumn::make('unread_count')
                    ->label('Unread')
                    ->state(fn (Conversation $record): int => $record->messages()
                        ->where('author_id', $record->user_id)
                        ->whereNull('read_at')
                        ->count()
                    )
                    ->badge()
                    ->color('warning'),
                TextColumn::make('updated_at')
                    ->label('Last activity')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('reply')
                    ->label('Reply')
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Textarea::make('body')
                            ->label('Message')
                            ->required()
                            ->rows(4)
                            ->maxLength(5000),
                    ])
                    ->action(function (Conversation $record, array $data): void {
                        $admin = auth()->user();

                        $message = $record->messages()->create([
                            'author_type' => get_class($admin),
                            'author_id' => $admin->getAuthIdentifier(),
                            'body' => $data['body'],
                        ]);

                        SendConversationMessageJob::dispatch($message);
                    })
                    ->successNotificationTitle('Reply sent'),
            ])
            ->recordAction('view')
            ->defaultSort('updated_at', 'desc')
            ->paginated(false);
    }
}
