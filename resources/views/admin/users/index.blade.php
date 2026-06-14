@extends('layouts.app')

@section('title', 'Admin · Pengguna')

@section('content')
<div class="container-lg" style="padding-top:48px; padding-bottom:80px;">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-5 flex-wrap gap-3">
        <div>
            <p class="section-label mb-1">Admin</p>
            <h1 style="font-size:32px; font-weight:500; color:var(--ink-deep); margin:0;">Kelola Pengguna</h1>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    {{-- Table --}}
    <div class="table-container">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th class="text-center">Prediksi</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td style="font-weight:500; color:var(--ink-deep);">{{ $user->name }}</td>
                        <td style="color:var(--charcoal); font-size:14px;">{{ $user->email }}</td>
                        <td class="text-center" style="font-weight:700; color:var(--ink-deep);">
                            {{ number_format($user->predictions_count ?? 0) }}
                        </td>
                        <td>
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-ghost btn-sm" style="padding:6px 14px; font-size:13px;">
                                    <i class="bi bi-eye me-1"></i>Detail
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center; padding:64px 0; color:var(--slate);">
                            Belum ada pengguna.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="mt-4 d-flex justify-content-center">{{ $users->links() }}</div>
    @endif

</div>
@endsection
