@extends('layouts.app')
@section('title', 'Vehicles')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-car text-primary me-2"></i>Vehicles</h1>
        <a href="{{ route('vehicles.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Vehicle</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card shadow-sm"><div class="card-body table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Vehicle</th><th>Plate</th><th>Details</th><th>Current Mileage</th><th>Services</th><th></th></tr></thead>
            <tbody>
            @forelse($vehicles as $vehicle)
                <tr>
                    <td><strong>{{ $vehicle->name }}</strong> @if($vehicle->is_default)<span class="badge bg-success ms-1">Default</span>@endif</td>
                    <td>{{ $vehicle->plate_number ?: '-' }}</td>
                    <td>{{ trim($vehicle->manufacturer . ' ' . $vehicle->model . ' ' . $vehicle->variant) ?: '-' }}{{ $vehicle->year ? ' (' . $vehicle->year . ')' : '' }}</td>
                    <td>{{ $vehicle->current_odometer !== null ? number_format($vehicle->current_odometer) . ' km' : '-' }}</td>
                    <td>{{ $vehicle->car_expenses_count }}</td>
                    <td class="text-end"><a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this vehicle?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
                    </td>
                </tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-4">No vehicles added yet.</td></tr>@endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $vehicles->links() }}</div>
    </div></div>
</div>
@endsection
