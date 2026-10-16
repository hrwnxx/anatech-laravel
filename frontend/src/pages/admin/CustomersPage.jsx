import { useEffect, useState } from 'react';
import api from '../../api';
import LoginMethodBadge from '../../components/LoginMethodBadge';
import Modal from '../../components/Modal';
import StatusBadge from '../../components/StatusBadge';
import { peso, shortDate } from '../../format';

export default function CustomersPage() {
    const [customers, setCustomers] = useState([]);
    const [keyword, setKeyword] = useState('');
    const [viewing, setViewing] = useState(null); // the customer whose purchases are shown
    const [history, setHistory] = useState([]);

    useEffect(() => {
        const timer = setTimeout(() => {
            api.get('/admin/customers', { params: { keyword } }).then((response) => setCustomers(response.data));
        }, 300);
        return () => clearTimeout(timer);
    }, [keyword]);

    function viewPurchases(customer) {
        setViewing(customer);
        setHistory([]);
        api.get(`/admin/customers/${customer.id}/purchases`).then((response) => setHistory(response.data));
    }

    return (
        <>
            <div className="mb-4">
                <h2 className="fw-bold mb-1">Customers</h2>
                <p className="text-muted mb-0">Everyone with a customer account, and how active they've been.</p>
            </div>

            <div className="card dashboard-card p-3 mb-3">
                <input
                    className="form-control"
                    placeholder="Search by name or email..."
                    value={keyword}
                    onChange={(e) => setKeyword(e.target.value)}
                />
            </div>

            <div className="card dashboard-card">
                <div className="table-responsive">
                    <table className="table align-middle mb-0">
                        <thead className="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Purchases</th>
                                <th>Reviews</th>
                                <th>Login</th>
                                <th>Last login</th>
                                <th>Joined</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {customers.length === 0 && (
                                <tr><td colSpan="9" className="text-center text-muted py-4">No customers found.</td></tr>
                            )}
                            {customers.map((customer) => (
                                <tr key={customer.id}>
                                    <td>{customer.name}</td>
                                    <td>{customer.email}</td>
                                    <td><StatusBadge status={customer.status} /></td>
                                    <td>{customer.purchases_count}</td>
                                    <td>{customer.reviews_count}</td>
                                    <td className="text-nowrap"><LoginMethodBadge user={customer} /></td>
                                    <td>{customer.last_login_at ? shortDate(customer.last_login_at) : <span className="text-muted">Never</span>}</td>
                                    <td>{shortDate(customer.created_at)}</td>
                                    <td className="text-end">
                                        <button className="btn btn-sm btn-outline-dark" onClick={() => viewPurchases(customer)}>
                                            View Purchases
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {viewing && (
                <Modal title={`Purchase History: ${viewing.name}`} onClose={() => setViewing(null)}>
                    {history.length === 0 ? (
                        <p className="text-muted mb-0">No purchases recorded.</p>
                    ) : (
                        <table className="table mb-0">
                            <thead>
                                <tr><th>Product</th><th>Qty</th><th>Total</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                {history.map((purchase) => (
                                    <tr key={purchase.id}>
                                        <td>{purchase.gadget.brand} {purchase.gadget.gadget_name}</td>
                                        <td>{purchase.quantity}</td>
                                        <td>{peso(purchase.total)}</td>
                                        <td>{shortDate(purchase.purchase_date)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </Modal>
            )}
        </>
    );
}
