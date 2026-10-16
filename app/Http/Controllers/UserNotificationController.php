<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\ChatConversation;
use App\Models\NotificationInbox;
use App\Models\Order;
use App\Http\Requests\ReadAllNotificationsRequest;
use App\Http\Requests\ReadOrderNotificationRequest;
use App\Http\Resources\NotificationInboxResource;
use App\Http\Resources\NotificationReadResource;
use App\Http\Resources\CustomerOrderResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): NotificationInboxResource
    {
        $this->authorize('index', [AppNotification::class, 'system']);

        return new NotificationInboxResource([
            'counts' => NotificationInbox::counts($request->user()),
            'items' => NotificationInbox::feed($request->user()),
        ]);
    }

    public function read(Request $request, AppNotification $notification): JsonResponse
    {
        $this->authorize('read', $notification);

        return (new NotificationReadResource(
            NotificationInbox::markSystemRead($request->user(), $notification)
        ))->response();
    }

    public function showCustomerOrder(Request $request, Order $order): CustomerOrderResource|\Illuminate\Contracts\View\View
    {
        $this->authorize('customerView', $order);

        if (! $request->expectsJson()) {
            return view('notifications.customer-order', ['order' => $order->load('order_items')]);
        }

        return new CustomerOrderResource($order->load('order_items'));
    }

    public function readOrder(ReadOrderNotificationRequest $request, Order $order): JsonResponse
    {
        $this->authorize('notificationRead', $order);

        try {
            $data = NotificationInbox::markOrderRead(
                $request->user(), $order, (int) $request->validated('notification_version')
            );
        } catch (HttpResponseException $exception) {
            if ($exception->getResponse()->getStatusCode() !== 409) {
                throw $exception;
            }

            return (new NotificationReadResource(
                json_decode($exception->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)
            ))->response()->setStatusCode(409);
        }

        return (new NotificationReadResource($data))->response();
    }

    public function readCustomerChat(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorize('customerNotificationRead', $conversation);

        return (new NotificationReadResource(
            NotificationInbox::markCustomerChatRead($request->user(), $conversation)
        ))->response();
    }

    public function readAll(ReadAllNotificationsRequest $request): JsonResponse
    {
        $scope = $request->validated('scope', 'system');
        $this->authorize('readAll', [AppNotification::class, $scope]);

        return (new NotificationReadResource(
            NotificationInbox::markAllRead($request->user(), $scope)
        ))->response();
    }
}
