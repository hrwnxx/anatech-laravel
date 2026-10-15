import { useEffect, useState } from 'react';
import api, { errorMessage } from '../../api';
import StarRating from '../../components/StarRating';
import StatusBadge from '../../components/StatusBadge';

export default function ReviewsPage() {
    const [tab, setTab] = useState('pending'); // 'pending' or 'archived'
    const [reviews, setReviews] = useState([]);
    const [selectedIds, setSelectedIds] = useState([]);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    function load() {
        api.get('/admin/reviews', { params: { status: tab } }).then((response) => {
            setReviews(response.data);
            setSelectedIds([]);
        });
    }

    useEffect(() => {
        load();
    }, [tab]);

    const allSelected = reviews.length > 0 && selectedIds.length === reviews.length;

    function toggleOne(id) {
        setSelectedIds(selectedIds.includes(id) ? selectedIds.filter((selected) => selected !== id) : [...selectedIds, id]);
    }

    function toggleAll() {
        setSelectedIds(allSelected ? [] : reviews.map((review) => review.id));
    }

    // action is "approve" or "reject"
    async function moderate(review, action) {
        try {
            await api.patch(`/admin/reviews/${review.id}/${action}`);
            setMessage(`Review by ${review.reviewer} ${action === 'approve' ? 'approved' : 'rejected'}.`);
            load();
        } catch (err) {
            setError(errorMessage(err));
        }
    }

    async function moderateSelected(action) {
        try {
            const response = await api.post('/admin/reviews/bulk', { action, ids: selectedIds });
            setMessage(`${response.data.updated} review(s) ${action === 'approve' ? 'approved' : 'rejected'}.`);
            load();
        } catch (err) {
            setError(errorMessage(err));
        }
    }

    return (
        <>
            <div className="mb-4">
                <h2 className="fw-bold mb-1">Reviews</h2>
                <p className="text-muted mb-0">Approve reviews to make them public, or reject them. Rejected reviews are kept in the archive.</p>
            </div>

            {message && <div className="alert alert-success">{message}</div>}
            {error && <div className="alert alert-danger">{error}</div>}

            <div className="d-flex justify-content-between align-items-center mb-3">
                <div className="btn-group">
                    <button className={`btn ${tab === 'pending' ? 'btn-dark' : 'btn-outline-dark'}`} onClick={() => setTab('pending')}>
                        Pending
                    </button>
                    <button className={`btn ${tab === 'archived' ? 'btn-dark' : 'btn-outline-dark'}`} onClick={() => setTab('archived')}>
                        Archive (Approved / Rejected)
                    </button>
                </div>

                {tab === 'pending' && (
                    <div>
                        <button className="btn btn-success me-1" disabled={selectedIds.length === 0} onClick={() => moderateSelected('approve')}>
                            Approve Selected ({selectedIds.length})
                        </button>
                        <button className="btn btn-outline-danger" disabled={selectedIds.length === 0} onClick={() => moderateSelected('reject')}>
                            Reject Selected
                        </button>
                    </div>
                )}
            </div>

            <div className="card dashboard-card">
                <div className="table-responsive">
                    <table className="table align-middle mb-0">
                        <thead className="table-light">
                            <tr>
                                {tab === 'pending' && (
                                    <th style={{ width: 30 }}>
                                        <input type="checkbox" className="form-check-input" checked={allSelected} onChange={toggleAll} />
                                    </th>
                                )}
                                <th>Customer</th>
                                <th>Gadget</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th className="text-end">{tab === 'pending' ? 'Actions' : 'Status'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {reviews.length === 0 && (
                                <tr>
                                    <td colSpan="6" className="text-center text-muted py-4">
                                        {tab === 'pending' ? 'No reviews waiting for approval.' : 'No archived reviews.'}
                                    </td>
                                </tr>
                            )}
                            {reviews.map((review) => (
                                <tr key={review.id}>
                                    {tab === 'pending' && (
                                        <td>
                                            <input
                                                type="checkbox"
                                                className="form-check-input"
                                                checked={selectedIds.includes(review.id)}
                                                onChange={() => toggleOne(review.id)}
                                            />
                                        </td>
                                    )}
                                    <td>{review.reviewer}</td>
                                    <td>{review.gadget.brand} {review.gadget.gadget_name}</td>
                                    <td className="text-nowrap"><StarRating rating={review.rating} /></td>
                                    <td>{review.comment}</td>
                                    <td className="text-end text-nowrap">
                                        {tab === 'pending' ? (
                                            <>
                                                <button className="btn btn-sm btn-success me-1" onClick={() => moderate(review, 'approve')}>Approve</button>
                                                <button className="btn btn-sm btn-outline-danger" onClick={() => moderate(review, 'reject')}>Reject</button>
                                            </>
                                        ) : (
                                            <StatusBadge status={review.status} />
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
