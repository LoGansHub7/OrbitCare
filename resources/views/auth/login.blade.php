<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · OrbitCare</title>
    @vite(['resources/js/app.js'])
</head>
<body>
<div class="oc-login">
    <div class="oc-login-card">
        <div class="text-center text-white mb-4">
            <div class="oc-brand justify-content-center pb-1"><i class="bi bi-globe-americas" aria-hidden="true"></i> OrbitCare</div>
            <div class="text-secondary">Astronaut health tracking prototype</div>
        </div>

        <div class="oc-card">
            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="username" required autofocus>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" name="password" class="form-control" autocomplete="current-password" required>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Keep me signed in</label>
                </div>
                <button class="btn btn-primary w-100" type="submit">Sign in</button>
            </form>
        </div>

        @if (app()->environment('local'))
            <div class="oc-card mt-3 small">
                <div class="fw-semibold mb-2">Demo accounts (fictional · local only)</div>
                <table class="table table-sm mb-2">
                    <tbody>
                    <tr><td>astro1@orbitcare.test</td><td>OC-01 · stable</td></tr>
                    <tr><td>astro2@orbitcare.test</td><td>OC-02 · sleep ↓ fatigue ↑</td></tr>
                    <tr><td>astro3@orbitcare.test</td><td>OC-03 · stale data</td></tr>
                    <tr><td>medic@orbitcare.test</td><td>Medical officer</td></tr>
                    <tr><td>admin@orbitcare.test</td><td>Administrator</td></tr>
                    </tbody>
                </table>
                <div>Password for all: <code>orbitcare-demo</code></div>
            </div>
        @endif
    </div>
</div>
</body>
</html>
