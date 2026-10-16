@extends('layouts.storefront')

@section('title', $gadget->brand.' '.$gadget->gadget_name.' | Anatech')

@section('content')
    <a href="{{ route('catalog.index') }}" class="btn btn-outline-secondary btn-sm mb-3">&larr; Back to Catalog</a>

    <div class="card p-4">
        <div class="row">
            <div class="col-md-4 text-center">
                @if ($gadget->image_url)
                    <img src="{{ $gadget->image_url }}" alt="{{ $gadget->gadget_name }}" class="img-fluid rounded gadget-details-img">
                @else
                    <i class="bi bi-image display-1 text-muted"></i>
                @endif
            </div>
            <div class="col-md-8">
                <span class="badge bg-secondary mb-2">{{ $gadget->category?->name }}</span>
                <h2>{{ $gadget->brand }} {{ $gadget->gadget_name }}</h2>
                @if ($gadget->model)
                    <h5 class="text-muted">Model: {{ $gadget->model }}</h5>
                @endif
                <h4 class="mt-3">₱{{ number_format((float) $gadget->price, 2) }}</h4>
                <p>@include('components.stock-badge', ['stock' => $gadget->stock ?? 0])</p>
                <p class="mb-2">
                    @if ($gadget->review_count > 0)
                        <span class="star-rating">{{ str_repeat('★', (int) round($gadget->avg_rating)) }}</span>
                        <strong>{{ number_format((float) $gadget->avg_rating, 1) }}</strong> out of 5
                        ({{ $gadget->review_count }} {{ \Illuminate\Support\Str::plural('review', $gadget->review_count) }})
                    @else
                        <span class="text-muted">No reviews yet</span>
                    @endif
                </p>
                <p class="mt-3" style="white-space: pre-line">{{ $gadget->description }}</p>
            </div>
        </div>
    </div>

    <hr>

    @if ($myReview)
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Your review</h5>
                <span class="badge text-capitalize {{ ['pending' => 'bg-warning text-dark', 'approved' => 'bg-success', 'rejected' => 'bg-danger'][$myReview->status] ?? 'bg-secondary' }}">{{ $myReview->status }}</span>
            </div>
            <form method="POST" action="{{ route('reviews.update', $myReview) }}">
                @csrf
                @method('PATCH')
                <label for="rating" class="form-label">Rating</label>
                <select id="rating" name="rating" class="form-select mb-2" required>
                    @foreach (range(5, 1) as $star)
                        <option value="{{ $star }}" @selected(old('rating', $myReview->rating) == $star)>{{ $star }} star{{ $star === 1 ? '' : 's' }}</option>
                    @endforeach
                </select>
                @error('rating')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <label for="comment" class="form-label">Comment</label>
                <textarea id="comment" name="comment" class="form-control mb-2" rows="3" maxlength="1000" required>{{ old('comment', $myReview->comment) }}</textarea>
                @error('comment')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <button class="btn btn-sm btn-dark" type="submit">Save Changes</button>
            </form>
            <form method="POST" action="{{ route('reviews.destroy', $myReview) }}" class="mt-2" onsubmit="return confirm('Delete your review?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
            </form>
            @if ($myReview->status === 'pending')
                <small class="text-muted d-block mt-2">Waiting for an admin to approve it.</small>
            @endif
        </div>
    @elseif ($hasPurchased)
        <div class="mb-3">
            <h3>Write a Review</h3>
            <form method="POST" action="{{ route('reviews.store', $gadget->id) }}">
                @csrf
                <label for="new-rating" class="form-label">Rating</label>
                <select id="new-rating" name="rating" class="form-select mb-2" required>
                    @foreach (range(5, 1) as $star)
                        <option value="{{ $star }}" @selected(old('rating', 5) == $star)>{{ $star }} star{{ $star === 1 ? '' : 's' }}</option>
                    @endforeach
                </select>
                @error('rating')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <label for="new-comment" class="form-label">Comment</label>
                <textarea id="new-comment" name="comment" class="form-control mb-2" rows="3" maxlength="1000" required>{{ old('comment') }}</textarea>
                @error('comment')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <button class="btn btn-dark" type="submit">Submit Review</button>
            </form>
        </div>
    @elseif (auth()->check())
        <div class="alert alert-warning">
            <strong>You haven't purchased this gadget yet.</strong><br>
            Only customers with a recorded purchase can review it. Check
            <a href="{{ route('account.purchases') }}">My Purchases</a>, or ask an admin to record your purchase.
        </div>
    @else
        <div class="alert alert-info">
            <strong>Want to leave a review?</strong><br>
            Please <a href="{{ route('login') }}">log in</a> first. Customer accounts are set up by staff when you make a purchase in-store.
        </div>
    @endif

    <hr>
    <h3>Customer Reviews</h3>

    @if ($reviews->isNotEmpty())
        <div class="mb-3">
            <a href="{{ route('catalog.show', $gadget->id) }}" class="btn btn-sm me-1 {{ $filterRating === 0 ? 'btn-dark' : 'btn-outline-dark' }}">All ({{ $reviews->count() }})</a>
            @foreach ([5, 4, 3, 2, 1] as $star)
                <a href="{{ route('catalog.show', ['id' => $gadget->id, 'review_rating' => $star]) }}" class="btn btn-sm me-1 {{ $filterRating === $star ? 'btn-dark' : 'btn-outline-dark' }}">{{ $star }}★ ({{ $ratingCounts[$star] }})</a>
            @endforeach
        </div>
    @endif

    @forelse ($visibleReviews as $review)
        <div class="card p-3 mb-2">
            <strong>{{ $review->user->maskedName() }}</strong>
            <span class="text-muted small">{{ $review->created_at->format('M j, Y') }}</span>
            <div class="star-rating">{{ str_repeat('★', $review->rating) }}</div>
            <p class="mb-0">{{ $review->comment }}</p>
        </div>
    @empty
        <div class="alert alert-secondary">
            {{ $reviews->isEmpty() ? 'No reviews yet. Be the first to review this gadget!' : 'No reviews with that rating.' }}
        </div>
    @endforelse
@endsection
