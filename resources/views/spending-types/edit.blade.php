@extends('layouts.app')

@section('title', 'Edit Spending Type')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">
                    <i class="fas fa-edit"></i> Edit Spending Type
                </h1>
                <a href="{{ route('spending-types.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Spending Type Details
                    </h6>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('spending-types.update', $spendingType) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="code" class="form-label">Code</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="code" 
                                   value="{{ $spendingType->code }}" 
                                   disabled>
                            <small class="form-text text-muted">Code cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $spendingType->name) }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" 
                                      id="description" 
                                      class="form-control @error('description') is-invalid @enderror" 
                                      rows="2">{{ old('description', $spendingType->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="keywords" class="form-label">
                                Keywords <i class="fas fa-info-circle text-info" title="Enter keywords separated by commas"></i>
                            </label>
                            <textarea name="keywords" 
                                      id="keywords" 
                                      class="form-control @error('keywords') is-invalid @enderror" 
                                      rows="4" 
                                      placeholder="Enter keywords separated by commas (e.g., grocery, supermarket, food)">{{ old('keywords', is_array($spendingType->keywords) ? implode(', ', $spendingType->keywords) : '') }}</textarea>
                            @error('keywords')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                These keywords will be used to automatically categorize transactions during import. 
                                Separate multiple keywords with commas.
                            </small>
                            
                            @if($spendingType->keywords && count($spendingType->keywords) > 0)
                                <div class="mt-2">
                                    <strong>Current keywords ({{ count($spendingType->keywords) }}):</strong>
                                    <div class="mt-1">
                                        @foreach($spendingType->keywords as $keyword)
                                            <span class="badge badge-secondary mr-1 mb-1">{{ $keyword }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="badge_class" class="form-label">Badge Class <span class="text-danger">*</span></label>
                                    <select name="badge_class" 
                                            id="badge_class" 
                                            class="form-control @error('badge_class') is-invalid @enderror" 
                                            required>
                                        <option value="badge-primary" {{ old('badge_class', $spendingType->badge_class) == 'badge-primary' ? 'selected' : '' }}>Primary (Blue)</option>
                                        <option value="badge-success" {{ old('badge_class', $spendingType->badge_class) == 'badge-success' ? 'selected' : '' }}>Success (Green)</option>
                                        <option value="badge-info" {{ old('badge_class', $spendingType->badge_class) == 'badge-info' ? 'selected' : '' }}>Info (Cyan)</option>
                                        <option value="badge-warning" {{ old('badge_class', $spendingType->badge_class) == 'badge-warning' ? 'selected' : '' }}>Warning (Yellow)</option>
                                        <option value="badge-danger" {{ old('badge_class', $spendingType->badge_class) == 'badge-danger' ? 'selected' : '' }}>Danger (Red)</option>
                                        <option value="badge-secondary" {{ old('badge_class', $spendingType->badge_class) == 'badge-secondary' ? 'selected' : '' }}>Secondary (Gray)</option>
                                        <option value="badge-dark" {{ old('badge_class', $spendingType->badge_class) == 'badge-dark' ? 'selected' : '' }}>Dark</option>
                                    </select>
                                    @error('badge_class')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="mt-2">
                                        <strong>Preview:</strong>
                                        <span id="badge-preview" class="badge {{ old('badge_class', $spendingType->badge_class) }}">
                                            Sample Badge
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="icon" class="form-label">
                                        Icon (FontAwesome) 
                                        <a href="https://fontawesome.com/icons" target="_blank" class="text-info">
                                            <i class="fas fa-external-link-alt"></i> Browse icons
                                        </a>
                                    </label>
                                    <input type="text" 
                                           name="icon" 
                                           id="icon" 
                                           class="form-control @error('icon') is-invalid @enderror" 
                                           value="{{ old('icon', $spendingType->icon) }}" 
                                           placeholder="e.g., shopping-cart">
                                    @error('icon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Enter FontAwesome icon name without 'fa-' prefix
                                    </small>
                                    @if($spendingType->icon)
                                        <div class="mt-2">
                                            <strong>Current icon:</strong>
                                            <i class="fas fa-{{ $spendingType->icon }} fa-2x text-primary"></i>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" 
                                           name="sort_order" 
                                           id="sort_order" 
                                           class="form-control @error('sort_order') is-invalid @enderror" 
                                           value="{{ old('sort_order', $spendingType->sort_order) }}" 
                                           min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Lower numbers appear first</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <div class="custom-control custom-switch">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" 
                                               class="custom-control-input" 
                                               id="is_active" 
                                               name="is_active" 
                                               value="1" 
                                               {{ old('is_active', $spendingType->is_active) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Inactive types won't appear in dropdowns
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="alert alert-warning">
                            <div class="custom-control custom-checkbox">
                                <input type="hidden" name="recategorize" value="0">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       id="recategorize" 
                                       name="recategorize" 
                                       value="1"
                                       {{ old('recategorize', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="recategorize">
                                    <strong><i class="fas fa-sync-alt"></i> Re-categorize existing transactions</strong>
                                </label>
                            </div>
                            <small class="form-text text-muted mt-2">
                                <i class="fas fa-info-circle"></i> 
                                When checked, the system will automatically re-categorize existing transactions 
                                that match the new keywords or were previously categorized as this type. 
                                This is useful when you add new keywords (like "PSS" for Petronas) and want 
                                existing transactions to be updated automatically.
                            </small>
                        </div>

                        <hr>

                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Update Spending Type
                            </button>
                            <a href="{{ route('spending-types.index') }}" class="btn btn-secondary btn-lg ml-2">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Update badge preview when selection changes
document.getElementById('badge_class').addEventListener('change', function() {
    const badge = document.getElementById('badge-preview');
    badge.className = 'badge ' + this.value;
});
</script>
@endpush
@endsection
