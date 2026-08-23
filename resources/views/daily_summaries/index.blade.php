@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('billing.index') }}" class="text-decoration-none small">
                <i class="bi bi-arrow-left"></i> Comprobantes
            </a>
            <h2 class="fw-bold mb-0 mt-1">
                <i class="bi bi-calendar-week me-2"></i>Resumen Diario de Boletas
            </h2>
            <p class="text-muted mb-0 small">
                Comunicación a SUNAT en bloque de las boletas (tipo 03) emitidas un día.
            </p>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{-- Generar nuevo resumen --}}
    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body p-3">
            <form action="{{ route('daily_summaries.store') }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small mb-1 fw-bold">Generar resumen para el día:</label>
                    <input type="date" name="reference_date" class="form-control"
                           value="{{ now()->subDay()->toDateString() }}" required>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-info text-white fw-bold"
                            onclick="return confirm('¿Generar y enviar resumen a SUNAT? Esta operación no se puede deshacer.')">
                        <i class="bi bi-send"></i> Generar y enviar
                    </button>
                </div>
                <div class="col-md-5 small text-muted">
                    <i class="bi bi-info-circle"></i> El envío es asíncrono: SUNAT devuelve un ticket
                    y luego hay que consultar su estado.
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Identificador</th>
                        <th>Fecha boletas</th>
                        <th>Generación</th>
                        <th class="text-center">Boletas</th>
                        <th class="text-end">Total</th>
                        <th>Ticket SUNAT</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaries as $s)
                        <tr>
                            <td class="fw-bold small">{{ $s->identifier }}</td>
                            <td>{{ \Carbon\Carbon::parse($s->reference_date)->format('d/m/Y') }}</td>
                            <td class="small">{{ \Carbon\Carbon::parse($s->generation_date)->format('d/m/Y') }}</td>
                            <td class="text-center">{{ $s->total_documents }}</td>
                            <td class="text-end fw-bold">S/ {{ number_format($s->total_amount, 2) }}</td>
                            <td class="small"><code>{{ $s->ticket ?? '—' }}</code></td>
                            <td>
                                @php
                                    $color = match($s->sunat_status) {
                                        'ACCEPTED' => 'success',
                                        'OBSERVED' => 'warning',
                                        'TICKET'   => 'info',
                                        'PENDING'  => 'secondary',
                                        default    => 'danger',
                                    };
                                @endphp
                                <span class="badge bg-{{ $color }}">{{ $s->sunat_status }}</span>
                                @if($s->sunat_code)
                                    <small class="text-muted d-block">[{{ $s->sunat_code }}]</small>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @if($s->ticket && $s->sunat_status !== 'ACCEPTED')
                                        <form method="POST" action="{{ route('daily_summaries.check', $s) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-outline-info" title="Consultar ticket">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($s->xml_path)
                                        <a href="{{ route('daily_summaries.xml', $s) }}" class="btn btn-outline-info" title="XML">
                                            <i class="bi bi-filetype-xml"></i>
                                        </a>
                                    @endif
                                    @if($s->cdr_path)
                                        <a href="{{ route('daily_summaries.cdr', $s) }}" class="btn btn-outline-success" title="CDR">
                                            <i class="bi bi-archive"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Sin resúmenes generados todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $summaries->links() }}</div>
    </div>
</div>
@endsection
