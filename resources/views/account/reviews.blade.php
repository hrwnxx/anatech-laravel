@extends('layouts.storefront')

@section('title', 'My Reviews | Anatech')

@section('content')
    <h2>My Reviews</h2>
    <p class="text-muted">New and edited reviews show on the gadget page once an admin approves them.</p>

    @if ($reviews->isEmpty())
        <div class="alert alert-secondary">
            You haven't written any reviews yet. Go to <a href="{{ route('account.purchases') }}">My Purchases</a> to review what you bought.
        </div>
    @else
        @foreach ($reviews as $review)
            <div class="card p-3 mb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <a href="{{ route('catalog.show', $review->gadget_id) }}" class="fw-bold text-decoration-none">
                            {{ $review->gadget->brand }} {{ $review->gadget->gadget_name }}
                        </a>
                        <div class="star-rating">{{ str_repeat('★', $review->rating) }}</div>
                    </div>
                    <div class="text-end">
                        <span class="badge text-capitalize {{ ['pending' => 'bg-warning text-dark', 'approved' => 'bg-success', 'rejected' => 'bg-danger'][$review->status] ?? 'bg-secondary' }}">{{ $review->status }}</span>
                        <div class="text-muted small">{{ $review->created_at->format('M j, Y') }}</div>
                    </div>
                </div>
                <p class="my-2">{{ $review->comment }}</p>
                <form method="POST" action="{{ route('reviews.update', $review) }}" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <label class="form-label" for="rating-{{ $review->id }}">Update rating</label>
                    <select id="rating-{{ $review->id }}" name="rating" class="form-select mb-2" required>
                        @foreach (range(5, 1) as $star)
                            <option value="{{ $star }}" @selected(old('rating', $review->rating) == $star)>{{ $star }} star{{ $star === 1 ? '' : 's' }}</option>
                        @endforeach
                    </select>
                    @error('rating')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    <label class="form-label" for="comment-{{ $review->id }}">Update comment</label>
                    <textarea id="comment-{{ $review->id }}" name="comment" class="form-control mb-2" rows="2" maxlength="1000" required>{{ old('comment', $review->comment) }}</textarea>
                    @error('comment')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    <button class="btn btn-sm btn-outline-secondary" type="submit">Save Changes</button>
                </form>
                <form method="POST" action="{{ route('reviews.destroy', $review) }}" onsubmit="return confirm('Delete your review?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                </form>
            </div>
        @endforeach
    @endif
@endsection
