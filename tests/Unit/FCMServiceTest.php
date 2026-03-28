<?php

use Illuminate\Support\Facades\Http;
use NettSite\Messenger\Services\FCMService;

it('skips sending when project_id is not configured', function () {
    Http::fake();
    config()->set('messenger.fcm.project_id', null);

    app(FCMService::class)->send('fcm-token', 'Title', 'Body', null);

    Http::assertNothingSent();
});

it('skips sending when credentials file does not exist', function () {
    Http::fake();
    config()->set('messenger.fcm.project_id', 'test-project');
    config()->set('messenger.fcm.credentials', '/nonexistent/path/credentials.json');

    app(FCMService::class)->send('fcm-token', 'Title', 'Body', null);

    Http::assertNothingSent();
});
