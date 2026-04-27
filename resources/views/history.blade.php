@extends('layouts.app')

@section('title', 'Riwayat Prediksi')

@section('content')
<div class="container-lg">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Prediksi</h2>
        <a href="{{ route('predict.form') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Prediksi Baru</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Umur</th>
                            <th>Gender</th>
                            <th class="text-center">KNN</th>
                            <th class="text-center">SVM</th>
                            <th class="text-center">Decision Tree</th>
                            <th class="text-center">Final</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $badge = function ($value) {
                                $cls  = ['success', 'warning', 'danger'][$value] ?? 'secondary';
                                $text = ['Rendah', 'Sedang', 'Tinggi'][$value] ?? '?';
                                return "<span class='badge bg-{$cls}'>{$text}</span>";
                            };
                        @endphp

                        @forelse ($predictions as $pred)
                            <tr>
                                <td>{{ $loop->iteration + ($predictions->currentPage() - 1) * $predictions->perPage() }}</td>
                                <td>{{ $pred->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $pred->input_features['age'] ?? '-' }}</td>
                                <td>{{ $pred->input_features['gender'] ?? '-' }}</td>
                                <td class="text-center">{!! $badge($pred->knn_prediction) !!}</td>
                                <td class="text-center">{!! $badge($pred->svm_prediction) !!}</td>
                                <td class="text-center">{!! $badge($pred->dt_prediction) !!}</td>
                                <td class="text-center"><strong>{!! $badge($pred->final_prediction) !!}</strong></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#detailModal"
                                            data-id="{{ $pred->id }}">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-5">Belum ada prediksi. <a href="{{ route('predict.form') }}">Buat prediksi pertama Anda &rarr;</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($predictions->hasPages())
                <div class="mt-3">{{ $predictions->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard-data"></i> Detail Prediksi</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center py-3"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const detailModal = document.getElementById('detailModal');
detailModal && detailModal.addEventListener('show.bs.modal', async (event) => {
    const id = event.relatedTarget?.getAttribute('data-id');
    const body = document.getElementById('detailContent');
    body.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    try {
        const res = await fetch(`/predict/${id}`, { headers: {'Accept': 'application/json'} });
        const data = await res.json();
        const labels = {0:'Rendah', 1:'Sedang', 2:'Tinggi'};
        const cls = {0:'success', 1:'warning', 2:'danger'};
        const row = (k, v) => `<tr><td><small class="text-muted">${k}</small></td><td>${v}</td></tr>`;
        const inputRows = Object.entries(data.input_features || {}).map(([k,v]) => row(k, v)).join('');
        body.innerHTML = `
            <h6>Hasil Prediksi</h6>
            <div class="row g-2 mb-3">
                <div class="col-3"><div class="card text-center border-${cls[data.knn.pred]||'secondary'}"><div class="card-body py-2"><small>KNN</small><br><strong class="text-${cls[data.knn.pred]||'secondary'}">${labels[data.knn.pred]||'?'}</strong><br><small>${(data.knn.conf*100).toFixed(1)}%</small></div></div></div>
                <div class="col-3"><div class="card text-center border-${cls[data.svm.pred]||'secondary'}"><div class="card-body py-2"><small>SVM</small><br><strong class="text-${cls[data.svm.pred]||'secondary'}">${labels[data.svm.pred]||'?'}</strong><br><small>${(data.svm.conf*100).toFixed(1)}%</small></div></div></div>
                <div class="col-3"><div class="card text-center border-${cls[data.dt.pred]||'secondary'}"><div class="card-body py-2"><small>DT</small><br><strong class="text-${cls[data.dt.pred]||'secondary'}">${labels[data.dt.pred]||'?'}</strong><br><small>${(data.dt.conf*100).toFixed(1)}%</small></div></div></div>
                <div class="col-3"><div class="card text-center bg-${cls[data.final.pred]||'secondary'} text-white"><div class="card-body py-2"><small>FINAL</small><br><strong>${labels[data.final.pred]||'?'}</strong></div></div></div>
            </div>
            <h6>Input Features</h6>
            <table class="table table-sm"><tbody>${inputRows}</tbody></table>
            <small class="text-muted">Diprediksi pada: ${data.created_at}</small>
        `;
    } catch (e) {
        body.innerHTML = `<div class="alert alert-danger">Gagal memuat detail: ${e.message}</div>`;
    }
});
</script>
@endpush
