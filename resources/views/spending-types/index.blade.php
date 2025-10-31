@extends('layouts.app')

@section('title', 'Spending Types Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">
                    <i class="fas fa-tags"></i> Spending Types Management
                </h1>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list"></i> All Spending Types
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Manage Keywords:</strong> Keywords are used to automatically categorize transactions during CSV import. 
                        Add relevant words or phrases that commonly appear in transaction descriptions for each category.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 15%">Type</th>
                                    <th style="width: 20%">Description</th>
                                    <th style="width: 40%">Keywords</th>
                                    <th style="width: 10%">Status</th>
                                    <th style="width: 10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($spendingTypes as $type)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="badge {{ str_replace('badge-', 'bg-', $type->badge_class) }} badge-lg">
                                                @if($type->icon)
                                                    <i class="fas fa-{{ $type->icon }}"></i>
                                                @endif
                                                {{ $type->name }}
                                            </span>
                                        </td>
                                        <td>{{ $type->description ?? '-' }}</td>
                                        <td>
                                            @if($type->keywords && count($type->keywords) > 0)
                                                <div class="keywords-container">
                                                    @foreach($type->keywords as $keyword)
                                                        <span class="badge bg-dark text-white mr-1 mb-1">{{ $keyword }}</span>
                                                    @endforeach
                                                </div>
                                                <small class="text-muted d-block mt-1">
                                                    ({{ count($type->keywords) }} keywords)
                                                </small>
                                            @else
                                                <span class="text-muted">No keywords defined</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($type->is_active)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Active
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-times"></i> Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('spending-types.edit', $type) }}" 
                                               class="btn btn-sm btn-primary" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No spending types found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-lightbulb"></i> Tips for Managing Keywords
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><strong>Be specific:</strong> Use specific brand names, service providers, or common terms (e.g., "petronas", "shell" for fuel)</li>
                        <li><strong>Use lowercase:</strong> All keywords are automatically converted to lowercase for matching</li>
                        <li><strong>Multiple words:</strong> Separate keywords with commas (e.g., "grocery, supermarket, food")</li>
                        <li><strong>Avoid duplicates:</strong> The system will automatically remove duplicate keywords</li>
                        <li><strong>Test import:</strong> After adding keywords, test with CSV import to see if categorization works correctly</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.keywords-container {
    max-height: 100px;
    overflow-y: auto;
}
.keywords-container .badge {
    font-size: 0.85rem;
    font-weight: 500;
    padding: 0.35rem 0.6rem;
    margin-right: 0.25rem;
    margin-bottom: 0.25rem;
}
.badge-lg {
    font-size: 0.9rem;
    padding: 0.4rem 0.6rem;
}
/* Bootstrap 5 badge fixes */
.badge {
    color: #fff !important;
}
.bg-warning {
    color: #000 !important;
}
.bg-light {
    color: #000 !important;
}
.bg-dark {
    background-color: #343a40 !important;
    color: #ffffff !important;
}
</style>
@endpush
@endsection
