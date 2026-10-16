import { useState } from 'react';
import { errorMessage } from '../api';

// Star picker + comment box. Used to write a new review (gadget page)
// and to edit an existing one (My Reviews).
export default function ReviewForm({ initialRating = 0, initialComment = '', submitLabel, onSubmit }) {
    const [rating, setRating] = useState(initialRating);
    const [comment, setComment] = useState(initialComment);
    const [error, setError] = useState('');
    const [saving, setSaving] = useState(false);

    async function handleSubmit(event) {
        event.preventDefault();
        if (rating === 0) {
            setError('Please choose a rating.');
            return;
        }

        setSaving(true);
        setError('');
        try {
            await onSubmit({ rating, comment });
        } catch (err) {
            setError(errorMessage(err));
        } finally {
            setSaving(false);
        }
    }

    return (
        <form onSubmit={handleSubmit}>
            {error && <div className="alert alert-danger">{error}</div>}

            <div className="mb-2 fs-3">
                {[1, 2, 3, 4, 5].map((star) => (
                    <button
                        key={star}
                        type="button"
                        className="btn btn-link p-0 me-1 text-warning text-decoration-none fs-3"
                        onClick={() => setRating(star)}
                        aria-label={`${star} star${star > 1 ? 's' : ''}`}
                    >
                        {star <= rating ? '★' : '☆'}
                    </button>
                ))}
            </div>

            <textarea
                className="form-control mb-2"
                rows="3"
                placeholder="What did you like or dislike?"
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                maxLength={1000}
                required
            />

            <button className="btn btn-dark" disabled={saving}>
                {saving ? 'Saving...' : submitLabel}
            </button>
        </form>
    );
}
