<?php

use PHPUnit\Framework\TestCase;

final class TopbarNotificationContractTest extends TestCase
{
    private function source(string $path): string
    {
        return file_get_contents(dirname(__DIR__, 2).'/'.$path);
    }

    public function test_bell_loads_authoritative_inbox_without_marking_items_read(): void
    {
        $view = $this->source('resources/views/partials/topbar-notifications.blade.php');
        self::assertStringContainsString("fetch(root.dataset.inboxUrl", $view);
        self::assertStringContainsString('renderCounts(data.counts)', $view);
        self::assertStringNotContainsString("button.addEventListener('click',async", $view);
        self::assertStringContainsString("list.addEventListener('click',async", $view);
    }

    public function test_order_click_uses_versioned_read_contract_and_customer_order_view(): void
    {
        $view = $this->source('resources/views/partials/topbar-notifications.blade.php');
        $resource = $this->source('app/Http/Resources/NotificationInboxResource.php');
        self::assertStringContainsString('notification_version:Number(item.dataset.version)', $view);
        self::assertStringContainsString("route('my-notifications.orders.show'", $resource);
        self::assertStringContainsString("view('notifications.customer-order'", $this->source('app/Http/Controllers/UserNotificationController.php'));
    }

    public function test_shop_chat_conversation_storage_is_account_scoped_and_deep_linked(): void
    {
        $shop = $this->source('resources/views/shop/index.blade.php');
        self::assertStringContainsString("get('chat_conversation')", $shop);
        self::assertStringContainsString("'chat_conversation_id:{{ auth()->check() ? auth()->id() : \"guest\" }}'", $shop);
        self::assertStringNotContainsString("localStorage.getItem('chat_conversation_id')", $shop);
    }
}
