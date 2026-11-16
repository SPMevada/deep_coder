@props(['sidebar' => true])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet"/>

        {{-- Vite is optional now (Bootstrap comes from the CDN). Loads only if it has been built / is running. --}}
        @if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        <style>
            body { font-family: 'Figtree', sans-serif; }
            @media (min-width: 992px) {
                .sidebar { position: fixed; top: 0; bottom: 0; left: 0; width: 240px; }
                .content { margin-left: 240px; }
            }
            .sidebar .nav-link { display: flex; align-items: center; gap: .6rem; }
            .sidebar .nav-link i { width: 18px; text-align: center; }
            .sidebar .nav-link:not(.active):hover { background: rgba(255,255,255,.1); }
        </style>
        @stack('styles')
    </head>
    <body class="bg-light">
        @php
            $authUser = Auth::user();

            // [label, route name, active pattern, icon (Font Awesome 4.7), allowed roles (null = everyone)]
            $menu = [
                ['Dashboard',   'dashboard',          'dashboard',     'fa-tachometer',  null],
                ['Departments', 'departments.index',  'departments.*', 'fa-building',    ['admin']],
                // ['Tasks',       'tasks.index',        'tasks.*',       'fa-tasks',       null],
                // ['Products',    'products.index',     'products.*',    'fa-cube',        ['admin', 'manager']],
                ['Employees',   'employees.index',    'employees.*',   'fa-users',       ['admin', 'manager']],
                ['Projects',    'projects.index',     'projects.*',    'fa-folder-open', null],
                ['Tasks',       'tasks.index',        'tasks.*',        'fa-tasks',        null],
                ['Project Allocation',    'projectallocations.index',     'projectallocations.*',    'fa-folder-open', null],
                ['Reports',     'reports.index',      'reports.*',      'fa-file-text-o',  ['admin', 'manager']],
                // ['Workload',    'allocations.index',  'allocations.*', 'fa-line-chart',  ['admin', 'manager']],
                // ['Reports',     'reports.index',      'reports.*',     'fa-file-text-o', ['admin', 'manager']],
            ];
        @endphp

        @if ($authUser)
            @if (!request()->routeIs('departments.create', 'departments.edit'))
                <aside class="sidebar offcanvas-lg offcanvas-start bg-dark text-white" id="sidebar" tabindex="-1">
                    <div class="offcanvas-body d-flex flex-column p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <a href="#" class="fs-5 fw-semibold text-white text-decoration-none">
                                {{ config('app.name', 'Laravel') }}
                            </a>
                            <button type="button" class="btn-close btn-close-white d-lg-none"
                                    data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button>
                        </div>

                        <ul class="nav nav-pills flex-column gap-1 mb-auto">
                            @foreach ($menu as [$label, $routeName, $pattern, $icon, $roles])
                                @if (!empty($roles) && in_array($authUser->role->slug, $roles))
                                    <li class="nav-item">
                                        <a href="{{ route($routeName) }}"
                                        class="nav-link text-white {{ request()->routeIs($pattern) ? 'active' : '' }}">
                                            <i class="fa {{ $icon }}"></i> {{ $label }}
                                        </a>
                                    </li>
                                @elseif(empty($roles))
                                    <li class="nav-item">
                                        <a href="{{ route($routeName) }}"
                                        class="nav-link text-white {{ request()->routeIs($pattern) ? 'active' : '' }}">
                                            <i class="fa {{ $icon }}"></i> {{ $label }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>

                        <hr>
                        <div class="small mb-2">
                            {{ $authUser->name }}
                            <span class="badge text-bg-secondary ms-1 text-capitalize">{{ $authUser->role->name }}</span>
                        </div>
                        <a class="btn btn-sm btn-outline-light mb-2" href="{{ route('notifications.index') }}">Notifications @if($authUser->unreadNotifications()->count())<span class="badge text-bg-danger">{{ $authUser->unreadNotifications()->count() }}</span>@endif</a>
                        <form method="POST" action="{{ route('api.logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm w-100">Log out</button>
                        </form>
                    </div>
                </aside>
            @endif
        @endif

        <div class="{{ $authUser ? 'content' : '' }} min-vh-100">
            @if ($authUser)
                <div class="d-lg-none bg-white border-bottom p-2">
                    <button class="btn btn-dark btn-sm" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#sidebar">Menu</button>
                </div>
            @endif

            @isset($header)
                <header class="bg-white shadow-sm">
                    <div class="container-fluid px-4 py-3">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            {{-- <main>
                {{ $slot }}
            </main> --}}
        </div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.22.1/jquery.validate.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
        <script>
            // Usage: successToast('Project created'); errorToast('Something went wrong');
            function showToast(message, background) {
                Toastify({
                    text: message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    stopOnFocus: true,
                    style: { background: background },
                }).showToast();
            }
            function successToast(message) { showToast(message, "linear-gradient(to right, #00b09b, #96c93d)"); }
            function errorToast(message)   { showToast(message, "#dc3545"); }

            // keep your old function names working
            var successToastMessge = successToast;
            var errorToastMessge   = errorToast;

            $(document).ready(function () {
                $.ajaxSetup({
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                });

                // Flash messages from controllers: ->with('success', '...') / ->with('error', '...')
                @if (session('success')) successToast(@json(session('success'))); @endif
                @if (session('error'))   errorToast(@json(session('error')));     @endif
                @if ($errors->any())     errorToast(@json($errors->first()));      @endif
            });
        </script>
        @stack('scripts')
    </body>
</html>
