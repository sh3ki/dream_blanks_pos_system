<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Notification;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        // Check and queue deadline reminders before returning the list
        NotificationService::lineupDeadlineReminders();

        [$page, $perPage] = $this->paginate($request);
        $unreadOnly = filter_var($request->query('unread_only', false), FILTER_VALIDATE_BOOLEAN);
        $result = Notification::forUser($this->currentUserId(), $unreadOnly, $page, $perPage);
        return $this->success(['notifications' => $result['data'], 'pagination' => $result['pagination'], 'unread_count' => Notification::unreadCount($this->currentUserId())]);
    }

    public function markRead(Request $request): Response
    {
        $id = (int)$request->param('notification_id');
        Notification::markRead($id, $this->currentUserId());
        return $this->success(null, 'Notification marked as read');
    }

    public function markAllRead(Request $request): Response
    {
        Notification::markAllRead($this->currentUserId());
        return $this->success(null, 'All notifications marked as read');
    }

    public function destroy(Request $request): Response
    {
        $id = (int)$request->param('notification_id');
        Notification::markDeleted($id, $this->currentUserId());
        return $this->success(null, 'Notification deleted');
    }
}
