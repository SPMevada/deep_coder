{{-- <x-guest-layout title="Sign in"> --}}
@extends('layouts.app')
@push('styles')
    <style>
        .brand-panel {
            background: linear-gradient(135deg, #3730a3 0%, #4f46e5 55%, #7c3aed 100%);
            position: relative;
            overflow: hidden;
        }
        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
        }
        .brand-panel::before { width: 380px; height: 380px; top: -120px; right: -120px; }
        .brand-panel::after  { width: 380px; height: 380px; bottom: -150px; left: -100px; }
        .brand-logo {
            width: 42px; height: 42px; border-radius: 12px;
            background: rgba(255, 255, 255, .2);
            display: inline-flex; align-items: center; justify-content: center;
        }
        .feature-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(255, 255, 255, .15);
            display: inline-flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .login-card { max-width: 420px; }
        .btn-primary { --bs-btn-bg: #4f46e5; --bs-btn-border-color: #4f46e5;
                    --bs-btn-hover-bg: #4338ca; --bs-btn-hover-border-color: #4338ca;
                    --bs-btn-active-bg: #3730a3; --bs-btn-active-border-color: #3730a3; }
        .form-control:focus { border-color: #818cf8; box-shadow: 0 0 0 .2rem rgba(79, 70, 229, .15); }
        .input-group-text { background: #fff; color: #94a3b8; }
    </style>
@endpush

<div class="container-fluid p-0">
    <div class="row g-0 min-vh-100">
        <div class="col-lg-12 d-flex align-items-center justify-content-center p-4">
            <div class="w-100 login-card">

                <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
                    <span class="brand-logo bg-primary text-white" style="background:#4f46e5 !important">
                        <i class="fa fa-folder-open"></i>
                    </span>
                    <span class="fs-5 fw-bold">{{ config('app.name', 'Laravel') }}</span>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h3 fw-bold mb-1">Welcome back</h2>
                        <p class="text-muted mb-4">Sign in to your account to continue.</p>

                        @if (session('status'))
                            <div class="alert alert-success py-2 small">{{ session('status') }}</div>
                        @endif


                        <form method="POST" id="loginForm" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label fw-medium">Email address</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text"><i class="fa fa-envelope-o"></i></span>
                                    <input type="email" id="email" name="email"
                                           value="{{ old('email') }}"
                                           class="form-control @error('email') is-invalid @enderror"
                                           placeholder="you@company.com"
                                           autocomplete="username" required autofocus>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-medium">Password</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                                    <input type="password" id="password" name="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Enter your password"
                                           autocomplete="current-password" required>
                                    <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1" aria-label="Show password">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="loginBtn">
                                Sign in
                            </button>
                        </form>
                    </div>
                </div>

                <p class="text-center text-muted small mt-4 mb-0">
                    Don't have an account? Contact your admin.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Show / hide password
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon  = this.querySelector('i');
        const show  = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
    });

    // Prevent double submit + simple client-side check
    // document.getElementById('loginForm').addEventListener('submit', function (e) {
    //     const email = document.getElementById('email');
    //     const pass  = document.getElementById('password');
    //     let ok = true;
    //     [email, pass].forEach(function (el) {
    //         const bad = !el.value.trim();
    //         el.classList.toggle('is-invalid', bad);
    //         if (bad) ok = false;
    //     });
    //     if (!ok) { e.preventDefault(); return; }

    //     const btn = document.getElementById('loginBtn');
    //     btn.disabled = true;
    //     btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';
    // });

    $(document).ready(function() {
        $(document).on('submit', '#loginForm', function(e) {
            e.preventDefault();

            const URL = "{{ route('api.login') }}";
            const self = $(this);
            const data = self.serialize();

            $.ajax({
                url: URL,
                type: 'POST',
                data: data,
                dataType: 'JSON',
                success: function (res) {
                    window.location.href = res.redirect;
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        $.each(xhr.responseJSON.errors, function (field, messages) {
                            errorToast(messages[0])
                        });
                    } else if (xhr.status === 429) {
                        errorToast('Too many attempts. Try again in a minute.');
                    } else if (xhr.status === 419) {
                        errorToast('Session expired. Refresh the page.')
                    } else {
                        errorToast('Something went wrong.');
                    }
                },
                complete: function (xhr) {
                    if (xhr.status !== 200) {
                        $('#loginBtn').prop('disabled', false).text('Sign in');
                    }
                }
            });
        })
    });
</script>
@endpush

{{-- </x-guest-layout> --}}