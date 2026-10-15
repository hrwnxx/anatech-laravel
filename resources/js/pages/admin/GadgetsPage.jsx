import { useEffect, useState } from 'react';
import api, { errorMessage } from '../../api';
import Modal from '../../components/Modal';
import StarRating from '../../components/StarRating';
import StockBadge from '../../components/StockBadge';
import { peso } from '../../format';

const emptyForm = { category_id: '', brand: '', gadget_name: '', model: '', price: '', description: '' };

export default function GadgetsPage() {
    const [tab, setTab] = useState('active'); // 'active' or 'archived'
    const [gadgets, setGadgets] = useState([]);
    const [categories, setCategories] = useState([]);
    const [keyword, setKeyword] = useState('');
    const [categoryId, setCategoryId] = useState('');
    const [message, setMessage] = useState('');

    // Add / Edit popup
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState(null); // the gadget being edited, or null when adding
    const [values, setValues] = useState(emptyForm);
    const [imageFile, setImageFile] = useState(null);
    const [formError, setFormError] = useState('');
    const [saving, setSaving] = useState(false);

    // Stock In / Stock Out popup
    const [stockGadget, setStockGadget] = useState(null);
    const [stockMode, setStockMode] = useState('in'); // 'in' or 'out'
    const [stockQuantity, setStockQuantity] = useState(1);
    const [stockError, setStockError] = useState('');

    function load() {
        const params = { archived: tab === 'archived' ? 1 : 0, keyword, category_id: categoryId };
        api.get('/admin/gadgets', { params }).then((response) => setGadgets(response.data));
    }

    useEffect(() => {
        api.get('/categories').then((response) => setCategories(response.data));
    }, []);

    // Reload when the tab or a filter changes (after a short pause while typing).
    useEffect(() => {
        const timer = setTimeout(load, 300);
        return () => clearTimeout(timer);
    }, [tab, keyword, categoryId]);

    function openAddForm() {
        setEditing(null);
        setValues(emptyForm);
        setImageFile(null);
        setFormError('');
        setFormOpen(true);
    }

    function openEditForm(gadget) {
        setEditing(gadget);
        setValues({
            category_id: gadget.category_id,
            brand: gadget.brand,
            gadget_name: gadget.gadget_name,
            model: gadget.model ?? '',
            price: gadget.price,
            description: gadget.description ?? '',
        });
        setImageFile(null);
        setFormError('');
        setFormOpen(true);
    }

    function handleChange(event) {
        setValues({ ...values, [event.target.name]: event.target.value });
    }

    async function saveGadget(event) {
        event.preventDefault();
        setSaving(true);
        setFormError('');

        // A photo can only be sent as FormData, not plain JSON.
        const form = new FormData();
        Object.entries(values).forEach(([field, value]) => form.append(field, value ?? ''));
        if (imageFile) {
            form.append('image', imageFile);
        }

        try {
            if (editing) {
                // POST, not PUT: PHP only reads uploaded files on POST requests.
                await api.post(`/admin/gadgets/${editing.id}`, form);
                setMessage('Gadget updated.');
            } else {
                await api.post('/admin/gadgets', form);
                setMessage('Gadget added with 0 stock. Use "Stock In" to add units.');
            }
            setFormOpen(false);
            load();
        } catch (err) {
            setFormError(errorMessage(err));
        } finally {
            setSaving(false);
        }
    }

    async function toggleArchive(gadget) {
        const name = `${gadget.brand} ${gadget.gadget_name}`;
        if (!gadget.archived && !confirm(`Archive ${name}? It will be hidden from the catalog.`)) {
            return;
        }
        await api.patch(`/admin/gadgets/${gadget.id}/${gadget.archived ? 'unarchive' : 'archive'}`);
        setMessage(gadget.archived ? `${name} is back in the catalog.` : `${name} was archived.`);
        load();
    }

    function openStock(gadget, mode) {
        setStockGadget(gadget);
        setStockMode(mode);
        setStockQuantity(1);
        setStockError('');
    }

    async function saveStock(event) {
        event.preventDefault();
        try {
            const response = await api.post(`/admin/gadgets/${stockGadget.id}/stock-${stockMode}`, { quantity: stockQuantity });
            setMessage(`${stockGadget.brand} ${stockGadget.gadget_name} now has ${response.data.stock} in stock.`);
            setStockGadget(null);
            load();
        } catch (err) {
            setStockError(errorMessage(err));
        }
    }

    return (
        <>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 className="fw-bold mb-1">Gadgets</h2>
                    <p className="text-muted mb-0">Manage products, photos and stock.</p>
                </div>
                <button className="btn btn-primary" onClick={openAddForm}>
                    <i className="bi bi-plus-lg"></i> Add Gadget
                </button>
            </div>

            {message && <div className="alert alert-success alert-dismissible">{message}
                <button type="button" className="btn-close" onClick={() => setMessage('')}></button>
            </div>}

            <div className="card dashboard-card p-3 mb-3">
                <div className="row g-2">
                    <div className="col-md-6">
                        <input
                            className="form-control"
                            placeholder="Search brand, name, model or category..."
                            value={keyword}
                            onChange={(e) => setKeyword(e.target.value)}
                        />
                    </div>
                    <div className="col-md-3">
                        <select className="form-select" value={categoryId} onChange={(e) => setCategoryId(e.target.value)}>
                            <option value="">All Categories</option>
                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>{category.name}</option>
                            ))}
                        </select>
                    </div>
                    <div className="col-md-3">
                        <div className="btn-group w-100">
                            <button className={`btn ${tab === 'active' ? 'btn-dark' : 'btn-outline-dark'}`} onClick={() => setTab('active')}>
                                Active
                            </button>
                            <button className={`btn ${tab === 'archived' ? 'btn-dark' : 'btn-outline-dark'}`} onClick={() => setTab('archived')}>
                                Archived
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="card dashboard-card">
                <div className="table-responsive">
                    <table className="table align-middle mb-0">
                        <thead className="table-light">
                            <tr>
                                <th style={{ width: 70 }}></th>
                                <th>Gadget</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Sold</th>
                                <th>Rating</th>
                                <th className="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {gadgets.length === 0 && (
                                <tr><td colSpan="8" className="text-center text-muted py-4">No gadgets found.</td></tr>
                            )}
                            {gadgets.map((gadget) => (
                                <tr key={gadget.id}>
                                    <td>
                                        {gadget.image_url
                                            ? <img src={gadget.image_url} alt="" style={{ height: 45 }} />
                                            : <i className="bi bi-image fs-3 text-muted"></i>}
                                    </td>
                                    <td>
                                        <strong>{gadget.brand} {gadget.gadget_name}</strong>
                                        <div className="text-muted small">{gadget.model}</div>
                                    </td>
                                    <td>{gadget.category}</td>
                                    <td>{peso(gadget.price)}</td>
                                    <td><StockBadge stock={gadget.stock} /></td>
                                    <td>{gadget.units_sold}</td>
                                    <td>
                                        {gadget.review_count > 0
                                            ? <><StarRating rating={gadget.avg_rating} /> <small>({gadget.review_count})</small></>
                                            : <small className="text-muted">None</small>}
                                    </td>
                                    <td className="text-end text-nowrap">
                                        {tab === 'active' && (
                                            <>
                                                <button className="btn btn-sm btn-outline-success me-1" title="Stock In" onClick={() => openStock(gadget, 'in')}>
                                                    <i className="bi bi-box-arrow-in-down"></i>
                                                </button>
                                                <button className="btn btn-sm btn-outline-warning me-1" title="Stock Out" onClick={() => openStock(gadget, 'out')}>
                                                    <i className="bi bi-box-arrow-up"></i>
                                                </button>
                                                <button className="btn btn-sm btn-outline-primary me-1" title="Edit" onClick={() => openEditForm(gadget)}>
                                                    <i className="bi bi-pencil"></i>
                                                </button>
                                            </>
                                        )}
                                        <button
                                            className={`btn btn-sm ${gadget.archived ? 'btn-outline-success' : 'btn-outline-danger'}`}
                                            title={gadget.archived ? 'Restore' : 'Archive'}
                                            onClick={() => toggleArchive(gadget)}
                                        >
                                            <i className={`bi ${gadget.archived ? 'bi-arrow-counterclockwise' : 'bi-archive'}`}></i>
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {formOpen && (
                <Modal title={editing ? 'Edit Gadget' : 'Add Gadget'} onClose={() => setFormOpen(false)}>
                    <form onSubmit={saveGadget}>
                        {formError && <div className="alert alert-danger">{formError}</div>}
                        <div className="row g-3">
                            <div className="col-md-6">
                                <label className="form-label">Category</label>
                                <select name="category_id" className="form-select" value={values.category_id} onChange={handleChange} required>
                                    <option value="">Choose...</option>
                                    {categories.map((category) => (
                                        <option key={category.id} value={category.id}>{category.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="col-md-6">
                                <label className="form-label">Brand</label>
                                <input name="brand" className="form-control" value={values.brand} onChange={handleChange} required />
                            </div>
                            <div className="col-md-6">
                                <label className="form-label">Gadget Name</label>
                                <input name="gadget_name" className="form-control" value={values.gadget_name} onChange={handleChange} required />
                            </div>
                            <div className="col-md-6">
                                <label className="form-label">Model</label>
                                <input name="model" className="form-control" value={values.model} onChange={handleChange} />
                            </div>
                            <div className="col-md-6">
                                <label className="form-label">Price (₱)</label>
                                <input name="price" type="number" min="0" step="0.01" className="form-control" value={values.price} onChange={handleChange} required />
                            </div>
                            <div className="col-md-6">
                                <label className="form-label">Photo {editing && <small className="text-muted">(leave empty to keep the current one)</small>}</label>
                                <input type="file" accept="image/*" className="form-control" onChange={(e) => setImageFile(e.target.files[0] || null)} />
                            </div>
                            <div className="col-12">
                                <label className="form-label">Description</label>
                                <textarea name="description" rows="3" className="form-control" value={values.description} onChange={handleChange} />
                            </div>
                            {editing?.image_url && !imageFile && (
                                <div className="col-12">
                                    <small className="text-muted d-block">Current photo:</small>
                                    <img src={editing.image_url} alt="" style={{ height: 80 }} />
                                </div>
                            )}
                        </div>
                        <div className="text-end mt-3">
                            <button type="button" className="btn btn-outline-secondary me-2" onClick={() => setFormOpen(false)}>Cancel</button>
                            <button className="btn btn-primary" disabled={saving}>{saving ? 'Saving...' : 'Save'}</button>
                        </div>
                    </form>
                </Modal>
            )}

            {stockGadget && (
                <Modal title={stockMode === 'in' ? 'Stock In' : 'Stock Out'} size="md" onClose={() => setStockGadget(null)}>
                    <form onSubmit={saveStock}>
                        {stockError && <div className="alert alert-danger">{stockError}</div>}
                        <p>
                            <strong>{stockGadget.brand} {stockGadget.gadget_name}</strong><br />
                            Current stock: {stockGadget.stock}
                        </p>
                        <label className="form-label">{stockMode === 'in' ? 'Quantity to add' : 'Quantity to remove'}</label>
                        <input
                            type="number"
                            min="1"
                            className="form-control mb-3"
                            value={stockQuantity}
                            onChange={(e) => setStockQuantity(e.target.value)}
                            required
                        />
                        <div className="text-end">
                            <button type="button" className="btn btn-outline-secondary me-2" onClick={() => setStockGadget(null)}>Cancel</button>
                            <button className={`btn ${stockMode === 'in' ? 'btn-success' : 'btn-warning'}`}>
                                {stockMode === 'in' ? 'Add Stock' : 'Remove Stock'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </>
    );
}
