import { useEffect, useState } from 'react';
import api, { errorMessage } from '../../api';
import { peso, shortDate, today } from '../../format';

const emptyForm = { user_id: '', name: '', email: '', password: '', gadget_id: '', quantity: 1, purchase_date: today() };

export default function PurchasesPage() {
    const [customers, setCustomers] = useState([]);
    const [gadgets, setGadgets] = useState([]);
    const [newCustomer, setNewCustomer] = useState(false);
    const [values, setValues] = useState(emptyForm);
    const [formError, setFormError] = useState('');
    const [message, setMessage] = useState('');
    const [saving, setSaving] = useState(false);

    const [tab, setTab] = useState('active'); // purchase history: 'active' or 'archived'
    const [purchases, setPurchases] = useState([]);

    // The two dropdowns: active customers, and gadgets with their current stock.
    function loadChoices() {
        api.get('/admin/users', { params: { role: 'customer', status: 'active' } }).then((response) => setCustomers(response.data));
        api.get('/admin/gadgets').then((response) => setGadgets(response.data));
    }

    function loadPurchases() {
        api.get('/admin/purchases', { params: { archived: tab === 'archived' ? 1 : 0 } })
            .then((response) => setPurchases(response.data));
    }

    useEffect(() => {
        loadChoices();
    }, []);

    useEffect(() => {
        loadPurchases();
    }, [tab]);

    function handleChange(event) {
        setValues({ ...values, [event.target.name]: event.target.value });
    }

    const selectedGadget = gadgets.find((gadget) => gadget.id === Number(values.gadget_id));

    async function recordPurchase(event) {
        event.preventDefault();
        setSaving(true);
        setFormError('');

        const sale = { gadget_id: values.gadget_id, quantity: values.quantity, purchase_date: values.purchase_date };
        // The temporary password is only sent when staff typed one (customers without Gmail).
        const customer = newCustomer
            ? { new_customer: true, name: values.name, email: values.email, password: values.password || null }
            : { user_id: values.user_id };
        const usesPassword = newCustomer && values.password !== '';

        try {
            const response = await api.post('/admin/purchases', { ...sale, ...customer });
            const purchase = response.data;
            setMessage(
                `Recorded: ${purchase.customer.name} bought ${purchase.quantity} × ${purchase.gadget.brand} ${purchase.gadget.gadget_name} (${peso(purchase.total)}).`
                + (newCustomer
                    ? ` An account was created for ${purchase.customer.email}. `
                        + (usesPassword
                            ? 'The customer logs in with that email and the temporary password.'
                            : 'The customer logs in with "Continue with Google" using that Gmail address.')
                    : '')
            );
            setValues(emptyForm);
            setNewCustomer(false);
            loadChoices(); // stock changed, and there may be a new customer
            setTab('active');
            loadPurchases();
        } catch (err) {
            setFormError(errorMessage(err));
        } finally {
            setSaving(false);
        }
    }

    async function toggleArchive(purchase) {
        await api.patch(`/admin/purchases/${purchase.id}/${purchase.archived ? 'unarchive' : 'archive'}`);
        loadPurchases();
    }

    return (
        <>
            <div className="mb-4">
                <h2 className="fw-bold mb-1">Purchases</h2>
                <p className="text-muted mb-0">
                    Record in-store sales. A recorded purchase is what lets a customer review that gadget.
                </p>
            </div>

            {message && <div className="alert alert-success">{message}</div>}

            <div className="card dashboard-card mb-4">
                <div className="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 className="mb-0">Record a Purchase</h5>
                    <div className="btn-group btn-group-sm">
                        <button type="button" className={`btn ${!newCustomer ? 'btn-primary' : 'btn-outline-primary'}`} onClick={() => setNewCustomer(false)}>
                            Existing Customer
                        </button>
                        <button type="button" className={`btn ${newCustomer ? 'btn-primary' : 'btn-outline-primary'}`} onClick={() => setNewCustomer(true)}>
                            New Customer
                        </button>
                    </div>
                </div>
                <div className="card-body">
                    <form onSubmit={recordPurchase}>
                        {formError && <div className="alert alert-danger">{formError}</div>}

                        <div className="row g-3">
                            {newCustomer ? (
                                <>
                                    <div className="col-md-4">
                                        <label className="form-label">New Customer Name</label>
                                        <input name="name" className="form-control" value={values.name} onChange={handleChange} required />
                                    </div>
                                    <div className="col-md-4">
                                        <label className="form-label">Customer's Gmail address</label>
                                        <input name="email" type="email" className="form-control" value={values.email} onChange={handleChange} required />
                                        <div className="form-text">Read it back to the customer. It's how they log in with Google.</div>
                                    </div>
                                    <div className="col-md-4">
                                        <label className="form-label">Temporary Password <span className="text-muted">(optional)</span></label>
                                        <input name="password" className="form-control" minLength={6} value={values.password} onChange={handleChange} />
                                        <div className="form-text">Only for customers without Gmail. Leave empty otherwise.</div>
                                    </div>
                                </>
                            ) : (
                                <div className="col-md-12">
                                    <label className="form-label">Customer</label>
                                    <select name="user_id" className="form-select" value={values.user_id} onChange={handleChange} required>
                                        <option value="">Choose a customer...</option>
                                        {customers.map((customer) => (
                                            <option key={customer.id} value={customer.id}>{customer.name} ({customer.email})</option>
                                        ))}
                                    </select>
                                </div>
                            )}

                            <div className="col-md-6">
                                <label className="form-label">Gadget</label>
                                <select name="gadget_id" className="form-select" value={values.gadget_id} onChange={handleChange} required>
                                    <option value="">Choose a gadget...</option>
                                    {gadgets.map((gadget) => (
                                        <option key={gadget.id} value={gadget.id} disabled={gadget.stock === 0}>
                                            {gadget.brand} {gadget.gadget_name} - {peso(gadget.price)} ({gadget.stock} in stock)
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="col-md-2">
                                <label className="form-label">Quantity</label>
                                <input
                                    name="quantity"
                                    type="number"
                                    min="1"
                                    max={selectedGadget?.stock}
                                    className="form-control"
                                    value={values.quantity}
                                    onChange={handleChange}
                                    required
                                />
                            </div>
                            <div className="col-md-4">
                                <label className="form-label">Date</label>
                                <input name="purchase_date" type="date" max={today()} className="form-control" value={values.purchase_date} onChange={handleChange} required />
                            </div>
                        </div>

                        <div className="d-flex justify-content-between align-items-center mt-3">
                            <span className="text-muted">
                                {selectedGadget && <>Total: <strong>{peso(selectedGadget.price * values.quantity)}</strong></>}
                            </span>
                            <button className="btn btn-primary" disabled={saving}>
                                {saving ? 'Saving...' : 'Record Purchase'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div className="card dashboard-card">
                <div className="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 className="mb-0">Purchase History</h5>
                    <div className="btn-group btn-group-sm">
                        <button className={`btn ${tab === 'active' ? 'btn-dark' : 'btn-outline-dark'}`} onClick={() => setTab('active')}>Active</button>
                        <button className={`btn ${tab === 'archived' ? 'btn-dark' : 'btn-outline-dark'}`} onClick={() => setTab('archived')}>Archived</button>
                    </div>
                </div>
                <div className="table-responsive">
                    <table className="table align-middle mb-0">
                        <thead className="table-light">
                            <tr>
                                <th>Customer</th>
                                <th>Gadget</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th className="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {purchases.length === 0 && (
                                <tr><td colSpan="6" className="text-center text-muted py-4">No purchases here.</td></tr>
                            )}
                            {purchases.map((purchase) => (
                                <tr key={purchase.id}>
                                    <td>
                                        {purchase.customer.name}
                                        <div className="text-muted small">{purchase.customer.email}</div>
                                    </td>
                                    <td>{purchase.gadget.brand} {purchase.gadget.gadget_name}</td>
                                    <td>{purchase.quantity}</td>
                                    <td>{peso(purchase.total)}</td>
                                    <td>{shortDate(purchase.purchase_date)}</td>
                                    <td className="text-end">
                                        <button
                                            className={`btn btn-sm ${purchase.archived ? 'btn-outline-success' : 'btn-outline-secondary'}`}
                                            onClick={() => toggleArchive(purchase)}
                                        >
                                            {purchase.archived ? 'Restore' : 'Archive'}
                                        </button>
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
