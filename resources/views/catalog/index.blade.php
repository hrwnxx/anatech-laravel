@extends('layouts.storefront')

@section('title', 'Gadget Catalog | Anatech')

@section('content')
    <div class="catalog-heading mb-4">
        <h2>Gadget Catalog</h2>
        <p class="text-muted mb-0">Browse gadgets below. Select one to view its details and customer reviews.</p>
    </div>

    <form method="GET" action="{{ route('catalog.index') }}" class="row mb-4 g-2 filter-bar">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input name="keyword" class="form-control" placeholder="Search phone, laptop, brand or model..." value="{{ request('keyword') }}">
            </div>
        </div>
        <div class="col-md-3">
            <select name="category_id" class="form-select">
                <option value="">All Categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="brand" class="form-select">
                <option value="">All Brands</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand }}" @selected(request('brand') === $brand)>{{ $brand }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="sort" class="form-select">
                <option value="">Newest first</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: Low to High</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: High to Low</option>
                <option value="rating" @selected(request('sort') === 'rating')>Top Rated</option>
                <option value="popular" @selected(request('sort') === 'popular')>Best Selling</option>
                <option value="name" @selected(request('sort') === 'name')>Name: A to Z</option>
            </select>
        </div>
        <div class="col-12">
            <button class="btn btn-dark btn-sm" type="submit">Apply filters</button>
            <a href="{{ route('catalog.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
    </form>

    @if ($gadgets->isEmpty())
        <div class="alert alert-secondary text-center">No gadgets match your filters.</div>
    @else
        <div class="row">
            @foreach ($gadgets as $gadget)
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card gadget-card">
                        <div class="gadget-card-img-wrap">
                            <span class="gadget-card-category">{{ $gadget->category?->name }}</span>
                            @if ($gadget->image_url)
                                <img src="{{ $gadget->image_url }}" alt="{{ $gadget->gadget_name }}">
                            @else
                                <i class="bi bi-image fs-1 text-muted"></i>
                            @endif
                        </div>
                        <div class="card-body">
                            <h6 class="card-title">{{ $gadget->brand }} {{ $gadget->gadget_name }}</h6>
                            <p class="card-model mb-0">{{ $gadget->model }}</p>
                            <p class="card-rating">
                                @if ($gadget->review_count > 0)
                                    <span class="star-rating">{{ str_repeat('★', (int) round($gadget->avg_rating)) }}</span>
                                    ({{ $gadget->review_count }})
                                @else
                                    No reviews yet
                                @endif
                            </p>
                            <p class="card-price">₱{{ number_format((float) $gadget->price, 2) }}</p>
                            @include('components.stock-badge', ['stock' => $gadget->stock ?? 0])
                            <a href="{{ route('catalog.show', $gadget->id) }}" class="btn btn-dark btn-sm w-100 mt-2">View Details</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
