@php
    $reason = isset($exception) ? trim((string) $exception->getMessage()) : '';
    if ($reason === '' || $reason === 'This action is unauthorized.' || $reason === 'Forbidden') {
        $reason = "You don't have permission to open this page. If you think you should, ask your administrator to update your role.";
    }
@endphp
@include('errors.page', ['code' => 403, 'icon' => 'lock', 'title' => 'Access denied', 'message' => $reason, 'actions' => ['back', 'home']])
