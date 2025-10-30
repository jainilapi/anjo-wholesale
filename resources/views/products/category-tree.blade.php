@foreach($categories as $category)
    <div class="category-item">
        <div class="d-flex align-items-center">
            @if(isset($category->children) && count($category->children) > 0)
                <a href="#" class="category-toggle">
                    <i class="fas fa-chevron-right"></i>
                </a>
            @else
                <span style="width: 16px; display: inline-block;"></span>
            @endif

            @if($type === 'primary')
                <div class="form-check">
                    <input class="form-check-input" 
                           type="radio" 
                           name="primary_category" 
                           value="{{ $category->id }}" 
                           id="category_{{ $category->id }}"
                           {{ $selectedPrimary == $category->id ? 'checked' : '' }}>
                    <label class="form-check-label" for="category_{{ $category->id }}">
                        <i class="fas fa-folder me-1"></i>
                        {{ $category->name }}
                    </label>
                </div>
            @endif
        </div>

        @if(isset($category->children) && count($category->children) > 0)
            <div class="category-children" style="display: none;">
                @include('products.category-tree', [
                    'categories' => $category->children,
                    'selectedPrimary' => $selectedPrimary ?? null,
                    'type' => $type
                ])
            </div>
        @endif
    </div>
@endforeach