@extends('layouts.app')

@section('title', 'Masuk - Mental Health Prediction')

@section('content')
<div style="min-height:calc(100vh - 64px - 100px); display:flex; align-items:center; padding: 48px 16px;">
    <div style="width:100%; max-width:440px; margin:0 auto;">

        <div class="text-center mb-5">
            <div style="width:56px; height:56px; background:var(--primary-soft); border-radius:var(--r-xl); display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                <i class="bi bi-heart-pulse" style="font-size:26px; color:var(--primary);"></i>
            </div>
            <h1 style="font-size:28px; font-weight:500; color:var(--ink-deep); margin-bottom:6px;">Masuk ke akun</h1>
            <p style="font-size:14px; color:var(--slate); margin:0;">Mental Health Risk Prediction</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mb-4">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email"
                       value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="contoh@email.com"
                       required autofocus>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Masukkan password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password',this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-5 d-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" style="margin:0;">
                <label class="form-check-label" for="remember" style="font-size:14px; color:var(--charcoal);">Ingat saya</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg">Masuk</button>
        </form>

        <div style="border-top:1px solid var(--hairline-soft); margin-top:32px; padding-top:24px; text-align:center;">
            <p style="font-size:14px; color:var(--slate); margin:0;">
                Belum punya akun?
                <a href="{{ route('register') }}" style="color:var(--primary); font-weight:700;">Daftar sekarang</a>
            </p>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    const ic  = btn.querySelector('i');
    if (inp.type === 'password') { inp.type = 'text';     ic.className = 'bi bi-eye-slash'; }
    else                         { inp.type = 'password'; ic.className = 'bi bi-eye'; }
}
</script>
@endpush
