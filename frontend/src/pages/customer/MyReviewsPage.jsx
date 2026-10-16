import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api, { errorMessage } from '../../api';
import Modal from '../../components/Modal';
import ReviewForm from '../../components/ReviewForm';
import StarRating from '../../components/StarRating';
import StatusBadge from '../../components/StatusBadge';
import { shortDate } from '../../format';

export default function MyReviewsPage() {
    const [reviews, setReviews] = useState([]);
    const [loading, setLoading] = useState(true);
    const [editingReview, setEditingReview] = useState(null);
    const [message, setMessage] = useState('');

    function load() {
        api.get('/my/reviews')
            .then((response) => setReviews(response.data))
            .finally(() => setLoading(false));
    }

    useEffect(() => {
        load();
    }, []);

    async function saveReview({ rating, comment }) {
        await api.put(`/reviews/${editingReview.id}`, { rating, comment });
        setEditingReview(null);
        setMessage('Review updated. It will be visible again once an admin approves it.');
        load();
    }

    async function deleteReview(review) {
        if (!confirm(`Delete your review of ${review.gadget.brand} ${review.gadget.gadget_name}?`)) {
            return;
        }
        try {
            await api.delete(`/reviews/${review.id}`);
            setMessage('Review deleted.');
            load();
        } catch (err) {
            setMessage(errorMessage(err));
        }
    }

    return (
        <>
            <h2>My Reviews</h2>
            <p className="text-muted">
                New and edited reviews show up on the gadget page once an admin approves them.
            </p>

            {message && <div className="alert alert-info">{message}</div>}
            {loading && <p className="text-muted">Loading...</p>}

            {!loading && reviews.length === 0 && (
                <div className="alert alert-secondary">
                    You haven't written any reviews yet. Go to <Link to="/my/purchases">My Purchases</Link> to review what you bought.
                </div>
            )}

            {reviews.map((review) => (
                <div key={review.id} className="card p-3 mb-2">
                    <div className="d-flex justify-content-between align-items-start">
                        <div>
                            <Link to={`/gadgets/${review.gadget.id}`} className="fw-bold text-decoration-none">
                                {review.gadget.brand} {review.gadget.gadget_name}
                            </Link>
                            <div><StarRating rating={review.rating} /></div>
                        </div>
                        <div className="text-end">
                            <StatusBadge status={review.status} />
                            <div className="text-muted small">{shortDate(review.created_at)}</div>
                        </div>
                    </div>
                    <p className="my-2">{review.comment}</p>
                    <div>
                        <button className="btn btn-sm btn-outline-secondary me-1" onClick={() => setEditingReview(review)}>
                            Edit
                        </button>
                        <button className="btn btn-sm btn-outline-danger" onClick={() => deleteReview(review)}>
                            Delete
                        </button>
                    </div>
                </div>
            ))}

            {editingReview && (
                <Modal title="Edit Review" size="md" onClose={() => setEditingReview(null)}>
                    <p className="text-muted small">
                        Editing sends the review back to an admin for approval.
                    </p>
                    <ReviewForm
                        initialRating={editingReview.rating}
                        initialComment={editingReview.comment}
                        submitLabel="Save Changes"
                        onSubmit={saveReview}
                    />
                </Modal>
            )}
        </>
    );
}
