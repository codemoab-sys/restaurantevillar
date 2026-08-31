@extends('layouts.app')

@section('content')
<div class="container-fluid">

    {{-- Cabecera --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">
                <i class="bi bi-receipt-cutoff me-2"></i>Comprobantes Electrónicos
            </h2>
            <p class="text-muted mb-0">Boletas y Facturas emitidas a SUNAT</p>
        </div>
        <div>
            <a href="{{ route('credit_notes.index') }}" class="btn btn-outline-warning me-2">
                <i class="bi bi-arrow-counterclockwise"></i> Notas de Crédito
            </a>
            <a href="{{ route('daily_summaries.index') }}" class="btn btn-outline-info">
                <i class="bi bi-calendar-week"></i> Resumen Diario
            </a>
        </div>
    </div>

    {{-- Mensajes flash --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats por estado --}}
    <div class="row g-3 mb-4">
        @foreach([
            'ACCEPTED' => ['Aceptados',  'success',   'check-circle-fill'],
            'OBSERVED' => ['Observados', 'warning',   'exclamation-triangle-fill'],
            'PENDING'  => ['Pendientes', 'secondary', 'hourglass-split'],
            'ERROR'    => ['Con error',  'danger',    'x-circle-fill'],
            'REJECTED' => ['Rechazados', 'danger',    'slash-circle-fill'],
        ] as $key => [$label, $color, $icon])
            <div class="col-6 col-md">
                <div class="card border-0 shadow-sm border-start border-{{ $color }} border-3">
                    <div class="card-body p-2 d-flex align-items-center">
                        <i class="bi bi-{{ $icon }} fs-3 text-{{ $color }} me-2"></i>
                        <div>
                            <div class="small text-muted">{{ $label }}</div>
                            <div class="fs-5 fw-bold">{{ $stats[$key] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Tipo</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="Boleta"  {{ request('type') === 'Boleta'  ? 'selected' : '' }}>Boleta</option>
                        <option value="Factura" {{ request('type') === 'Factura' ? 'selected' : '' }}>Factura</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Estado</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach(['PENDING','ACCEPTED','OBSERVED','REJECTED','ERROR','VOIDED'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Desde</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Hasta</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Buscar</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Serie, número, cliente, RUC/DNI"
                           class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Comprobante</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th class="text-end">Total</th>
                        <th>Estado SUNAT</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                        <tr>
                            <td>
                                <span class="badge bg-{{ $o->document_type === 'Factura' ? 'primary' : 'info' }}">
                                    {{ $o->document_type }}
                                </span>
                                <span class="fw-bold ms-1">{{ $o->full_number }}</span>
                            </td>
                            <td class="small">{{ $o->created_at->format('d/m/Y H:i') }}</td>
                            <td class="small">
                                <div>{{ $o->client_name ?: 'Cliente' }}</div>
                                <div class="text-muted">{{ $o->client_document }}</div>
                            </td>
                            <td class="text-end fw-bold">S/ {{ number_format($o->total, 2) }}</td>
                            <td>
                                @php
                                    $color = match($o->sunat_status) {
                                        'ACCEPTED' => 'success',
                                        'OBSERVED' => 'warning',
                                        'PENDING'  => 'secondary',
                                        'VOIDED'   => 'dark',
                                        default    => 'danger',
                                    };
                                @endphp
                                <span class="badge bg-{{ $color }}">{{ $o->sunat_status }}</span>
                                @if($o->sunat_code)
                                    <small class="text-muted d-block">[{{ $o->sunat_code }}]</small>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('billing.show', $o) }}" class="btn btn-outline-secondary" title="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('billing.pdf.ticket', $o) }}" target="_blank" class="btn btn-outline-danger" title="Ticket 80mm">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                    @if($o->xml_path || $o->sunat_status === 'ACCEPTED')
                                        <a href="{{ route('billing.xml', $o) }}" class="btn btn-outline-info" title="XML">
                                            <i class="bi bi-filetype-xml"></i>
                                        </a>
                                    @endif
                                    @if($o->cdr_path || $o->sunat_status === 'ACCEPTED')
                                        <a href="{{ route('billing.cdr', $o) }}" class="btn btn-outline-success" title="CDR">
                                            <i class="bi bi-archive"></i>
                                        </a>
                                    @endif
                                    @if(in_array($o->sunat_status, ['PENDING','ERROR','REJECTED']))
                                        <form method="POST" action="{{ route('billing.retry', $o) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-outline-warning" title="Reintentar envío"
                                                    onclick="return confirm('¿Reintentar envío a SUNAT?')">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Sin comprobantes que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
