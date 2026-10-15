import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import api, { errorMessage } from '../api';
import ReviewForm from '../components/ReviewForm';
import StarRating from '../components/StarRating';
import StatusBadge from '../components/StatusBadge';
import StockBadge from '../components/StockBadge';
import { peso, shortDate } from '../format';

export default function GadgetDetailsPage() {
    const { id } = useParams();
    const [page, setPage] = useState(null); // { gadget, reviews, rating_counts, viewer }
    const [notFound, setNotFound] = useState(false);
    const [starFilter, setStarFilter] = useState(0); // 0 = show all
    const [editing, setEditing] = useState(false);
    const [message, setMessage] = useState('');

    function load() {
        api.get(`/gadgets/${id}`)
            .then((response) => setPage(response.data))
            .catch(() => setNotFound(true));
    }

    useEffect(() => {
        load();
    }, [id]);

    if (notFound) {
        return (
            <div className="alert alert-warning">
                This gadget doesn't exist or is no longer sold. <Link to="/">Back to Catalog</Link>
            </div>
        );
    }
    if (!page) {
        return <p className="text-muted">Loading...</p>;
    }

    const { gadget, reviews, rating_counts: ratingCounts, viewer } = page;
    const shownReviews = starFilter ? reviews.filter((review) => review.rating === starFilter) : reviews;

    async function submitReview({ rating, comment }) {
        await api.post('/reviews', { gadget_id: gadget.id, rating, comment });
        setMessage('Thanks! Your review was sent and will appear here once an admin approves it.');
        load();
    }

    async function updateReview({ rating, comment }) {
        await api.put(`/reviews/${viewer.my_review.id}`, { rating, comment });
        setEditing(false);
        setMessage('Your review was updated and is waiting for approval again.');
        load();
    }

    async function deleteReview() {
        if (!confirm('Delete your review?')) {
            return;
        }
        try {
            await api.delete(`/reviews/${viewer.my_review.id}`);
            setMessage('Your review was deleted.');
            load();
        } catch (err) {
            setMessage(errorMessage(err));
        }
    }

    return (
        <>
            <Link to="/" className="btn btn-outline-secondary btn-sm mb-3">&larr; Back to Catalog</Link>

            <div className="card p-4">
                <div className="row">
                    <div className="col-md-4 text-center">
                        {gadget.image_url ? (
                            <img src={gadget.image_url} alt={gadget.gadget_name} className="img-fluid rounded gadget-details-img" />
                        ) : (
                            <i className="bi bi-image display-1 text-muted"></i>
                        )}
                    </div>
                    <div className="col-md-8">
                        <span className="badge bg-secondary mb-2">{gadget.category}</span>
                        <h2>{gadget.brand} {gadget.gadget_name}</h2>
                        {gadget.model && <h5 className="text-muted">Model: {gadget.model}</h5>}
                        <h4 className="mt-3">{peso(gadget.price)}</h4>
                        <p><StockBadge stock={gadget.stock} /></p>
                        <p className="mb-2">
                            {gadget.review_count > 0 ? (
                                <>
                                    <StarRating rating={gadget.avg_rating} />{' '}
                                    <strong>{gadget.avg_rating}</strong> out of 5 ({gadget.review_count}{' '}
                                    review{gadget.review_count === 1 ? '' : 's'})
                                </>
                            ) : (
                                <span className="text-muted">No reviews yet</span>
                            )}
                        </p>
                        <p className="mt-3" style={{ whiteSpace: 'pre-line' }}>{gadget.description}</p>
                    </div>
                </div>
            </div>

            <hr />

            {message && <div className="alert alert-info">{message}</div>}

            {/* Who can write a review: only a logged-in customer with a recorded purchase. */}
            {viewer.my_review ? (
                <div className="card p-3 mb-3">
                    <div className="d-flex justify-content-between align-items-center mb-2">
                        <h5 className="mb-0">Your review</h5>
                        <StatusBadge status={viewer.my_review.status} />
                    </div>
                    {editing ? (
                        <ReviewForm
                            initialRating={viewer.my_review.rating}
                            initialComment={viewer.my_review.comment}
                            submitLabel="Save Changes"
                            onSubmit={updateReview}
                        />
                    ) : (
                        <>
                            <StarRating rating={viewer.my_review.rating} />
                            <p className="mb-2">{viewer.my_review.comment}</p>
                            {viewer.my_review.status === 'pending' && (
                                <small className="text-muted d-block mb-2">Waiting for an admin to approve it.</small>
                            )}
                            <div>
                                <button className="btn btn-sm btn-outline-secondary me-1" onClick={() => setEditing(true)}>Edit</button>
                                <button className="btn btn-sm btn-outline-danger" onClick={deleteReview}>Delete</button>
                            </div>
                        </>
                    )}
                </div>
            ) : viewer.has_purchased ? (
                <div className="mb-3">
                    <h3>Write a Review</h3>
                    <ReviewForm submitLabel="Submit Review" onSubmit={submitReview} />
                </div>
            ) : viewer.logged_in ? (
                <div className="alert alert-warning">
                    <strong>You haven't purchased this gadget yet.</strong><br />
                    Only customers with a recorded purchase can review it. Check{' '}
                    <Link to="/my/purchases">My Purchases</Link>, or ask an admin to record your purchase.
                </div>
            ) : (
                <div className="alert alert-info">
                    <strong>Want to leave a review?</strong><br />
                    Please <Link to="/login">log in</Link> first. Customer accounts are set up by our staff
                    when you make a purchase in-store.
                </div>
            )}

            <hr />

            <h3>Customer Reviews</h3>

            {reviews.length > 0 && (
                <div className="mb-3">
                    <button
                        className={`btn btn-sm me-1 ${starFilter === 0 ? 'btn-dark' : 'btn-outline-dark'}`}
                        onClick={() => setStarFilter(0)}
                    >
                        All ({reviews.length})
                    </button>
                    {[5, 4, 3, 2, 1].map((star) => (
                        <button
                            key={star}
                            className={`btn btn-sm me-1 ${starFilter === star ? 'btn-dark' : 'btn-outline-dark'}`}
                            onClick={() => setStarFilter(star)}
                        >
                            {star}★ ({ratingCounts[star]})
                        </button>
                    ))}
                </div>
            )}

            {reviews.length === 0 && (
                <div className="alert alert-secondary">No reviews yet. Be the first to review this gadget!</div>
            )}

            {shownReviews.map((review) => (
                <div key={review.id} className="card p-3 mb-2">
                    <div>
                        <strong>{review.reviewer}</strong>
                        {review.is_mine && <span className="text-muted small"> (you)</span>}
                        <span className="text-muted small float-end">{shortDate(review.created_at)}</span>
                    </div>
                    <StarRating rating={review.rating} />
                    <p className="mb-0">{review.comment}</p>
                </div>
            ))}
        </>
    );
}
