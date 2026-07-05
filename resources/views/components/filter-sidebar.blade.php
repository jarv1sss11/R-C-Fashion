@props([
    'categories' => null,
    'brands' => [],
    'colors' => [],
    'sizes' => [],
    'materials' => [],
    'seasons' => [],
    'styles' => [],
    'ageGroups' => [],
    'filters' => [],
])

<aside class="filter-sidebar">
    <form method="GET" action="{{ url()->current() }}" class="filter-form" data-filter-form>
        @if (request('q'))
            <input type="hidden" name="q" value="{{ request('q') }}">
        @endif

        @if ($categories)
            <div class="filter-group">
                <label for="filter-category" class="filter-group-title">Category</label>
                <select name="category_id" id="filter-category" class="input-field-input" data-filter-auto>
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if (count($brands))
            <div class="filter-group">
                <label for="filter-brand" class="filter-group-title">Brand</label>
                <select name="brand_id" id="filter-brand" class="input-field-input" data-filter-auto>
                    <option value="">All Brands</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((string) ($filters['brand_id'] ?? '') === (string) $brand->id)>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="filter-group">
            <label for="filter-gender" class="filter-group-title">Gender</label>
            <select name="gender" id="filter-gender" class="input-field-input" data-filter-auto>
                <option value="">All</option>
                <option value="Men" @selected(($filters['gender'] ?? '') === 'Men')>Men</option>
                <option value="Women" @selected(($filters['gender'] ?? '') === 'Women')>Women</option>
                <option value="Unisex" @selected(($filters['gender'] ?? '') === 'Unisex')>Unisex</option>
            </select>
        </div>

        @if (count($ageGroups))
            <div class="filter-group">
                <label for="filter-age-group" class="filter-group-title">Age Group</label>
                <select name="age_group" id="filter-age-group" class="input-field-input" data-filter-auto>
                    <option value="">All Ages</option>
                    @foreach ($ageGroups as $ageGroup)
                        <option value="{{ $ageGroup }}" @selected(($filters['age_group'] ?? '') === $ageGroup)>{{ $ageGroup }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="filter-group">
            <span class="filter-group-title">Price (KES)</span>
            <div class="filter-price-row">
                <input type="number" name="min_price" placeholder="Min" min="0" value="{{ $filters['min_price'] ?? '' }}" class="input-field-input">
                <input type="number" name="max_price" placeholder="Max" min="0" value="{{ $filters['max_price'] ?? '' }}" class="input-field-input">
            </div>
        </div>

        <div class="filter-group">
            <label for="filter-color" class="filter-group-title">Colour</label>
            <select name="color" id="filter-color" class="input-field-input" data-filter-auto>
                <option value="">All Colours</option>
                @foreach ($colors as $color)
                    <option value="{{ $color }}" @selected(($filters['color'] ?? '') === $color)>{{ $color }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="filter-size" class="filter-group-title">Size</label>
            <select name="size" id="filter-size" class="input-field-input" data-filter-auto>
                <option value="">All Sizes</option>
                @foreach ($sizes as $size)
                    <option value="{{ $size }}" @selected(($filters['size'] ?? '') === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>

        @if (count($materials))
            <div class="filter-group">
                <label for="filter-material" class="filter-group-title">Material</label>
                <select name="material" id="filter-material" class="input-field-input" data-filter-auto>
                    <option value="">All Materials</option>
                    @foreach ($materials as $material)
                        <option value="{{ $material }}" @selected(($filters['material'] ?? '') === $material)>{{ $material }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if (count($styles))
            <div class="filter-group">
                <label for="filter-style" class="filter-group-title">Style</label>
                <select name="style" id="filter-style" class="input-field-input" data-filter-auto>
                    <option value="">All Styles</option>
                    @foreach ($styles as $style)
                        <option value="{{ $style }}" @selected(($filters['style'] ?? '') === $style)>{{ $style }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if (count($seasons))
            <div class="filter-group">
                <label for="filter-season" class="filter-group-title">Season</label>
                <select name="season" id="filter-season" class="input-field-input" data-filter-auto>
                    <option value="">All Seasons</option>
                    @foreach ($seasons as $season)
                        <option value="{{ $season }}" @selected(($filters['season'] ?? '') === $season)>{{ $season }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="filter-group">
            <label for="filter-rating" class="filter-group-title">Minimum Rating</label>
            <select name="min_rating" id="filter-rating" class="input-field-input" data-filter-auto>
                <option value="">Any Rating</option>
                <option value="4" @selected(($filters['min_rating'] ?? '') == 4)>4★ & up</option>
                <option value="3" @selected(($filters['min_rating'] ?? '') == 3)>3★ & up</option>
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-group-title">
                <input type="checkbox" name="availability" value="in_stock" data-filter-auto @checked(($filters['availability'] ?? '') === 'in_stock')>
                In Stock Only
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
        <a href="{{ url()->current() }}{{ request('q') ? '?q=' . urlencode(request('q')) : '' }}" class="filter-clear-link">Clear Filters</a>
    </form>
</aside>
