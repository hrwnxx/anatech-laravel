import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../api';
import { peso, shortDate } from '../format';

export default function MyPurchasesPage() {
    const [purchases, setPurchases] = useState([]);
    const [reviewedGadgetIds, setReviewedGadgetIds] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Load both lists at the same time; the reviews tell us which purchases still need one.
        Promise.all([api.get('/my/purchases'), api.get('/my/reviews')])
            .then(([purchasesResponse, reviewsResponse]) => {
                setPurchases(purchasesResponse.data);
                setReviewedGadgetIds(reviewsResponse.data.map((review) => review.gadget_id));
            })
            .finally(() => setLoading(false));
    }, []);

    return (
        <>
            <h2>My Purchases</h2>
            <p className="text-muted">Gadgets you bought at the store. You can review anything on this list.</p>

            {loading && <p className="text-muted">Loading...</p>}

            {!loading && purchases.length === 0 && (
                <div className="alert alert-secondary">
                    No purchases recorded yet. Purchases are added by our staff when you buy in-store.
                </div>
            )}

            {purchases.length > 0 && (
                <div className="card p-3">
                    <table className="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Gadget</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {purchases.map((purchase) => (
                                <tr key={purchase.id}>
                                    <td style={{ width: 70 }}>
                                        {purchase.gadget.image_url && (
                                            <img src={purchase.gadget.image_url} alt="" style={{ height: 50 }} />
                                        )}
                                    </td>
                                    <td>{purchase.gadget.brand} {purchase.gadget.gadget_name}</td>
                                    <td>{purchase.quantity}</td>
                                    <td>{peso(purchase.unit_price)}</td>
                                    <td>{peso(purchase.total)}</td>
                                    <td>{shortDate(purchase.purchase_date)}</td>
                                    <td className="text-end text-nowrap">
                                        {reviewedGadgetIds.includes(purchase.gadget.id) ? (
                                            <Link to={`/gadgets/${purchase.gadget.id}`} className="btn btn-sm btn-outline-secondary">
                                                <i className="bi bi-check2"></i> Reviewed
                                            </Link>
                                        ) : (
                                            <Link to={`/gadgets/${purchase.gadget.id}`} className="btn btn-sm btn-dark">
                                                Write a Review
                                            </Link>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </>
    );
}
