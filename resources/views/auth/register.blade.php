@extends('layouts.app')

@section('title', 'Daftar - Mental Health Prediction')

@section('content')
<div style="min-height:calc(100vh - 64px - 100px); display:flex; align-items:center; padding: 48px 16px;">
    <div style="width:100%; max-width:480px; margin:0 auto;">

        <div class="text-center mb-5">
            <div style="width:56px; height:56px; background:var(--primary-soft); border-radius:var(--r-xl); display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                <i class="bi bi-person-plus" style="font-size:26px; color:var(--primary);"></i>
            </div>
            <h1 style="font-size:28px; font-weight:500; color:var(--ink-deep); margin-bottom:6px;">Buat akun baru</h1>
            <p style="font-size:14px; color:var(--slate); margin:0;">Mental Health Risk Prediction</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-4">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" id="name" name="name"
                       value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror"
                       placeholder="Nama lengkap Anda" required autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email"
                       value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="contoh@email.com" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Minimal 8 karakter" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password',this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="mb-5">
                <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                <div class="input-group">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control"
                           placeholder="Ulangi password Anda" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password_confirmation',this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg">Buat Akun</button>
        </form>

        <div style="border-top:1px solid var(--hairline-soft); margin-top:32px; padding-top:24px; text-align:center;">
            <p style="font-size:14px; color:var(--slate); margin:0;">
                Sudah punya akun?
                <a href="{{ route('login') }}" style="color:var(--primary); font-weight:700;">Masuk di sini</a>
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
