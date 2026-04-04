<?php

namespace NettSite\Messenger\Filament\Resources\MessageResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use NettSite\Messenger\Messenger;
use NettSite\Messenger\Models\Reply;

class RepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'replies';

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('author_name')
                ->label('From')
                ->state(fn (Reply $record): string => $record->author?->name ?? '—'),
            TextEntry::make('created_at')
                ->label('Sent')
                ->dateTime(),
            TextEntry::make('body')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author_name')
                    ->label('From')
                    ->state(fn (Reply $record): string => $record->author?->name ?? '—'),
                TextColumn::make('body')
                    ->limit(80),
                TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('respond')
                    ->label('Respond')
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Textarea::make('body')
                            ->label('Message')
                            ->required()
                            ->rows(4)
                            ->maxLength(5000),
                    ])
                    ->action(function (Reply $record, array $data): void {
                        $messenger = app(Messenger::class);
                        $message = $messenger->broadcast(
                            body: $data['body'],
                            url: null,
                            recipientType: 'user',
                            recipientId: $record->user_id,
                        );
                        $messenger->send($message);
                    })
                    ->successNotificationTitle('Message sent'),
            ])
            ->recordAction('view')
            ->defaultSort('created_at', 'asc')
            ->paginated(false);
    }
}
