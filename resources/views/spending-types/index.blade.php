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
                <div>
                    <button type="button" class="btn btn-primary mr-2" data-bs-toggle="modal" data-bs-target="#sortModal">
                        <i class="fas fa-sort"></i> Sort Order
                    </button>
                    <button type="button" class="btn btn-success mr-2" onclick="recategorizeAll()">
                        <i class="fas fa-sync-alt"></i> Re-categorize All
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
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

<!-- Sort Order Modal -->
<div class="modal fade" id="sortModal" tabindex="-1" aria-labelledby="sortModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sortModalLabel">
                    <i class="fas fa-sort"></i> Manage Sort Order
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Lower numbers appear first. Spending types are checked in this order when detecting categories.
                </div>
                
                <form id="sortForm" action="{{ route('spending-types.update-sort') }}" method="POST">
                    @csrf
                    <div class="list-group" id="sortableList">
                        @foreach($spendingTypes as $type)
                            <div class="list-group-item d-flex align-items-center" data-id="{{ $type->id }}">
                                <span class="handle me-3" style="cursor: move;">
                                    <i class="fas fa-grip-vertical"></i>
                                </span>
                                <span class="badge {{ str_replace('badge-', 'bg-', $type->badge_class) }} me-2">
                                    @if($type->icon)
                                        <i class="fas fa-{{ $type->icon }}"></i>
                                    @endif
                                    {{ $type->name }}
                                </span>
                                <span class="flex-grow-1"></span>
                                <div class="input-group" style="width: 120px;">
                                    <span class="input-group-text">Order:</span>
                                    <input type="number" 
                                           class="form-control sort-input" 
                                           name="sort_order[{{ $type->id }}]" 
                                           value="{{ $type->sort_order }}" 
                                           min="0" 
                                           style="width: 60px;">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSortOrder()">
                    <i class="fas fa-save"></i> Save Sort Order
                </button>
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
.list-group-item.dragging {
    opacity: 0.5;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// Initialize Sortable for drag and drop
let sortable;
document.addEventListener('DOMContentLoaded', function() {
    const sortableList = document.getElementById('sortableList');
    
    sortable = new Sortable(sortableList, {
        handle: '.handle',
        animation: 150,
        ghostClass: 'dragging',
        onEnd: function(evt) {
            // Update sort order values after drag
            updateSortOrderValues();
        }
    });
});

// Update sort order input values based on current position
function updateSortOrderValues() {
    const items = document.querySelectorAll('#sortableList .list-group-item');
    items.forEach((item, index) => {
        const input = item.querySelector('.sort-input');
        input.value = (index + 1) * 10; // Use increments of 10 for flexibility
    });
}

// Save sort order
function saveSortOrder() {
    const form = document.getElementById('sortForm');
    const formData = new FormData(form);
    
    // Show loading state
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and reload page
            bootstrap.Modal.getInstance(document.getElementById('sortModal')).hide();
            location.reload();
        } else {
            alert('Error saving sort order: ' + data.message);
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving sort order. Please try again.');
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// Re-categorize all transactions
function recategorizeAll() {
    if (!confirm('This will re-categorize ALL transactions based on the current keywords and sort order. This may take a few moments. Continue?')) {
        return;
    }
    
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;
    
    fetch('{{ route("spending-types.recategorize-all") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Successfully re-categorized ' + data.count + ' transactions!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error re-categorizing transactions. Please try again.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
</script>
@endpush
@endsection
