@extends('layouts.app')

@section('title', 'Riwayat Prediksi')

@section('content')
<div class="container-lg" style="padding-top:48px; padding-bottom:80px;">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-5 flex-wrap gap-3">
        <div>
            <p class="section-label mb-1">Dashboard</p>
            <h1 style="font-size:32px; font-weight:500; color:var(--ink-deep); margin:0;">Riwayat Prediksi</h1>
        </div>
        <a href="{{ route('predict.form') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Prediksi Baru
        </a>
    </div>

    {{-- Table --}}
    <div class="table-container">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>Umur</th>
                    <th>Gender</th>
                    <th>Model</th>
                    <th class="text-center">Hasil Risiko</th>
                    <th class="text-center">Confidence</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php
                    $riskLabel = ['Rendah', 'Sedang', 'Tinggi'];
                    $riskBg    = ['var(--success)', 'var(--warning)', 'var(--critical)'];
                    $riskFg    = ['#fff', 'var(--ink-deep)', '#fff'];
                @endphp

                @forelse ($predictions as $pred)
                    <tr>
                        <td style="color:var(--slate); font-size:13px;">
                            {{ $loop->iteration + ($predictions->currentPage()-1) * $predictions->perPage() }}
                        </td>
                        <td style="font-size:13px; color:var(--charcoal);">{{ $pred->created_at->format('d/m/Y H:i') }}</td>
                        <td style="font-weight:500;">{{ $pred->input_features['age'] ?? '-' }}</td>
                        <td style="color:var(--charcoal);">{{ $pred->input_features['gender'] ?? '-' }}</td>
                        <td>
                            <span style="font-size:12px; font-weight:700; color:var(--primary); background:var(--primary-soft); padding:4px 10px; border-radius:var(--r-full);">
                                {{ $pred->model_label }}
                            </span>
                        </td>
                        <td class="text-center">
                            @php $rv = $pred->final_prediction; @endphp
                            <span style="padding:4px 12px; font-size:12px; font-weight:700; border-radius:var(--r-full); background:{{ $riskBg[$rv] ?? '#ccc' }}; color:{{ $riskFg[$rv] ?? '#000' }};">
                                {{ $riskLabel[$rv] ?? '?' }}
                            </span>
                        </td>
                        <td class="text-center" style="font-size:14px; font-weight:700; color:var(--ink-deep);">
                            {{ $pred->confidence !== null ? number_format($pred->confidence*100,1).'%' : '—' }}
                        </td>
                        <td>
                            <button type="button" class="btn btn-ghost btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#detailModal"
                                    data-id="{{ $pred->id }}"
                                    style="padding:6px 14px; font-size:13px;">
                                <i class="bi bi-eye me-1"></i>Detail
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:64px 0; color:var(--slate);">
                            Belum ada prediksi.
                            <a href="{{ route('predict.form') }}" style="color:var(--primary); font-weight:700;">Buat prediksi pertama Anda &rarr;</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($predictions->hasPages())
        <div class="mt-4 d-flex justify-content-center">{{ $predictions->links() }}</div>
    @endif

</div>

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Prediksi</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center py-4"><div class="spinner-border"></div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const riskBg    = {0:'var(--success)', 1:'var(--warning)', 2:'var(--critical)'};
const riskFg    = {0:'#fff', 1:'var(--ink-deep)', 2:'#fff'};
const riskText  = {0:'Rendah', 1:'Sedang', 2:'Tinggi'};
const modelLabels = {
    'knn':'K-Nearest Neighbors','knn_hpo':'KNN + HPO',
    'svm':'Support Vector Machine','svm_hpo':'SVM + HPO',
    'dt':'Decision Tree','dt_hpo':'Decision Tree + HPO',
};

// Escape agar data tersimpan tidak tersuntik sebagai HTML.
const esc = s => String(s ?? '').replace(/[&<>"']/g, c =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

document.getElementById('detailModal')?.addEventListener('show.bs.modal', async (event) => {
    const id   = event.relatedTarget?.getAttribute('data-id');
    const body = document.getElementById('detailContent');
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div></div>';

    try {
        const res  = await fetch(`/predict/${id}`, {headers:{'Accept':'application/json'}});
        const data = await res.json();

        const rv     = data.final_prediction;
        const bg     = riskBg[rv]   ?? '#ccc';
        const fg     = riskFg[rv]   ?? '#000';
        const label  = riskText[rv] ?? '?';
        const mLabel = modelLabels[data.selected_model] ?? (data.selected_model ?? '—');
        const conf   = data.confidence != null ? (data.confidence*100).toFixed(2)+'%' : '—';

        const rows = Object.entries(data.input_features||{}).map(([k,v])=>`
            <tr>
                <td style="font-size:13px; color:var(--slate); width:55%;">${esc(k)}</td>
                <td style="font-size:14px; font-weight:500; color:var(--ink-deep);">${esc(v)}</td>
            </tr>
        `).join('');

        body.innerHTML = `
            <div style="background:var(--surface-soft); border-radius:var(--r-xl); padding:20px 24px; margin-bottom:20px;">
                <p style="font-size:12px; color:var(--slate); margin-bottom:6px;">Model: <strong style="color:var(--ink-deep);">${mLabel}</strong></p>
                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                    <span style="padding:8px 20px; border-radius:var(--r-full); background:${bg}; color:${fg}; font-size:16px; font-weight:700;">${label}</span>
                    <span style="font-size:14px; color:var(--charcoal);">Confidence: <strong style="color:var(--ink-deep);">${conf}</strong></span>
                </div>
            </div>
            <p style="font-size:13px; font-weight:700; color:var(--slate); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:10px;">Input Features</p>
            <div style="border:1px solid var(--hairline-soft); border-radius:var(--r-xl); overflow:hidden;">
                <table class="table table-sm mb-0"><tbody>${rows}</tbody></table>
            </div>
            <p style="font-size:12px; color:var(--steel); margin-top:12px;">Diprediksi pada: ${data.created_at}</p>
        `;
    } catch (e) {
        body.innerHTML = `<div class="alert alert-danger">Gagal memuat detail: ${e.message}</div>`;
    }
});
</script>
@endpush
