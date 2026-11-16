@include('errors.page', ['code' => 401, 'icon' => 'key', 'title' => 'Please sign in', 'message' => 'You need to be signed in to view this page.', 'actions' => ['login', 'home']])
