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
    <div class="container position-relative"><div class="row align-items-center justify-content-center g-5">
        <section class="col-lg-6 text-white">
            <div class="oc-brand fs-2 p-0 mb-4"><x-orbit-logo /> OrbitCare</div>
            <p class="display-6 fw-semibold mb-3">A calm, onboard view of mission health signals.</p>
            <p class="text-white-50 lead">Record observations, review trends, and document follow-up while keeping data provenance and uncertainty visible.</p>
            <p class="small border-start border-info border-3 ps-3 mb-0">Prototype / not a medical device. Experimental rules support review; they do not diagnose, treat, or clear a crew member.</p>
        </section>
        <div class="col-lg-5"><div class="oc-login-card mx-auto">
        @if (app()->environment('local') || config('orbitcare.judge_demo'))
            <section class="oc-card mb-3" aria-labelledby="judge-demo-heading">
                <h2 id="judge-demo-heading" class="h6 mb-2">Judge demo — one-click access</h2>
                <p class="small text-secondary mb-3">Fictional demonstration records. Choose a role to begin; no credentials need to be entered.</p>
                <div class="d-grid gap-2">
                    <form method="POST" action="{{ route('judge-demo.login', ['account' => 'astronaut']) }}">@csrf<button class="btn btn-primary w-100" type="submit"><i class="bi bi-person-badge" aria-hidden="true"></i> Sign in as demo astronaut</button></form>
                    <form method="POST" action="{{ route('judge-demo.login', ['account' => 'medical-officer']) }}">@csrf<button class="btn btn-outline-primary w-100" type="submit"><i class="bi bi-heart-pulse" aria-hidden="true"></i> Sign in as demo medical officer</button></form>
                    <form method="POST" action="{{ route('judge-demo.login', ['account' => 'admin']) }}">@csrf<button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-sliders" aria-hidden="true"></i> Sign in as demo administrator</button></form>
                </div>
                <p class="small text-secondary mt-3 mb-0"><strong>Try:</strong> Body Map, Alerts, and ORBIT assistant. The administrator workspace manages accounts but does not expose crew health records.</p>
            </section>
        @endif
        <div class="oc-card">
            @if (app()->environment('local') || config('orbitcare.judge_demo'))<h2 class="h6 mb-3">Standard sign in</h2>@endif
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

        @if (app()->environment('local') || config('orbitcare.judge_demo'))
            <div class="oc-card mt-3 small">
                <div class="text-secondary">One-click demo access is intentionally temporary. Turn off <code>ORBITCARE_JUDGE_DEMO</code> and deactivate or remove these accounts after judging.</div>
            </div>
        @endif
    </div></div></div></div>
</div>
</body>
</html>
