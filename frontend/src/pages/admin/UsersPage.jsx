import { useEffect, useState } from 'react';
import api, { errorMessage } from '../../api';
import { useAuth } from '../../auth/AuthContext';
import Modal from '../../components/Modal';
import StatusBadge from '../../components/StatusBadge';
import { shortDate } from '../../format';

const emptyForm = { name: '', email: '', password: '', role: 'customer', status: 'active' };

export default function UsersPage() {
    const { user: currentUser } = useAuth();
    const [users, setUsers] = useState([]);
    const [filters, setFilters] = useState({ keyword: '', role: '', status: '' });
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    // Add / Edit popup
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState(null); // the user being edited, or null when adding
    const [values, setValues] = useState(emptyForm);
    const [formError, setFormError] = useState('');

    // Reset Password popup
    const [passwordUser, setPasswordUser] = useState(null);
    const [newPassword, setNewPassword] = useState('');
    const [passwordError, setPasswordError] = useState('');

    function load() {
        api.get('/admin/users', { params: filters }).then((response) => setUsers(response.data));
    }

    useEffect(() => {
        const timer = setTimeout(load, 300);
        return () => clearTimeout(timer);
    }, [filters]);

    function handleFilterChange(event) {
        setFilters({ ...filters, [event.target.name]: event.target.value });
    }

    function handleChange(event) {
        setValues({ ...values, [event.target.name]: event.target.value });
    }

    function openAddForm() {
        setEditing(null);
        setValues(emptyForm);
        setFormError('');
        setFormOpen(true);
    }

    function openEditForm(user) {
        setEditing(user);
        setValues({ name: user.name, email: user.email, password: '', role: user.role, status: user.status });
        setFormError('');
        setFormOpen(true);
    }

    async function saveUser(event) {
        event.preventDefault();
        setFormError('');
        try {
            if (editing) {
                const { password, ...changes } = values; // the password is changed separately
                await api.put(`/admin/users/${editing.id}`, changes);
                setMessage(`${values.name} was updated.`);
            } else {
                await api.post('/admin/users', values);
                setMessage(`${values.name} was added.`);
            }
            setFormOpen(false);
            load();
        } catch (err) {
            setFormError(errorMessage(err));
        }
    }

    function openPasswordForm(user) {
        setPasswordUser(user);
        setNewPassword('');
        setPasswordError('');
    }

    async function savePassword(event) {
        event.preventDefault();
        try {
            await api.put(`/admin/users/${passwordUser.id}/password`, { password: newPassword });
            setMessage(`Password changed for ${passwordUser.name}.`);
            setPasswordUser(null);
        } catch (err) {
            setPasswordError(errorMessage(err));
        }
    }

    async function deleteUser(user) {
        if (!confirm(`Delete ${user.name}? Their purchases and reviews will be deleted too. Setting the account to Inactive is usually better.`)) {
            return;
        }
        setError('');
        try {
            await api.delete(`/admin/users/${user.id}`);
            setMessage(`${user.name} was deleted.`);
            load();
        } catch (err) {
            setError(errorMessage(err));
        }
    }

    return (
        <>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 className="fw-bold mb-1">Users</h2>
                    <p className="text-muted mb-0">Admin and customer accounts.</p>
                </div>
                <button className="btn btn-primary" onClick={openAddForm}>
                    <i className="bi bi-person-plus"></i> Add User
                </button>
            </div>

            {message && <div className="alert alert-success">{message}</div>}
            {error && <div className="alert alert-danger">{error}</div>}

            <div className="card dashboard-card p-3 mb-3">
                <div className="row g-2">
                    <div className="col-md-6">
                        <input name="keyword" className="form-control" placeholder="Search by name or email..." value={filters.keyword} onChange={handleFilterChange} />
                    </div>
                    <div className="col-md-3">
                        <select name="role" className="form-select" value={filters.role} onChange={handleFilterChange}>
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="customer">Customer</option>
                        </select>
                    </div>
                    <div className="col-md-3">
                        <select name="status" className="form-select" value={filters.status} onChange={handleFilterChange}>
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <div className="card dashboard-card">
                <div className="table-responsive">
                    <table className="table align-middle mb-0">
                        <thead className="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th className="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.length === 0 && (
                                <tr><td colSpan="6" className="text-center text-muted py-4">No users found.</td></tr>
                            )}
                            {users.map((user) => (
                                <tr key={user.id}>
                                    <td>
                                        {user.name}
                                        {user.id === currentUser.id && <span className="text-muted small"> (you)</span>}
                                    </td>
                                    <td>{user.email}</td>
                                    <td>
                                        <span className={`badge text-capitalize ${user.role === 'admin' ? 'bg-dark' : 'bg-light text-dark border'}`}>
                                            {user.role}
                                        </span>
                                    </td>
                                    <td><StatusBadge status={user.status} /></td>
                                    <td>{shortDate(user.created_at)}</td>
                                    <td className="text-end text-nowrap">
                                        <button className="btn btn-sm btn-outline-primary me-1" title="Edit" onClick={() => openEditForm(user)}>
                                            <i className="bi bi-pencil"></i>
                                        </button>
                                        <button className="btn btn-sm btn-outline-secondary me-1" title="Reset Password" onClick={() => openPasswordForm(user)}>
                                            <i className="bi bi-key"></i>
                                        </button>
                                        <button
                                            className="btn btn-sm btn-outline-danger"
                                            title="Delete"
                                            disabled={user.id === currentUser.id}
                                            onClick={() => deleteUser(user)}
                                        >
                                            <i className="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {formOpen && (
                <Modal title={editing ? 'Edit User' : 'Add User'} size="md" onClose={() => setFormOpen(false)}>
                    <form onSubmit={saveUser}>
                        {formError && <div className="alert alert-danger">{formError}</div>}
                        <label className="form-label">Name</label>
                        <input name="name" className="form-control mb-2" value={values.name} onChange={handleChange} required />
                        <label className="form-label">Email</label>
                        <input name="email" type="email" className="form-control mb-2" value={values.email} onChange={handleChange} required />
                        {!editing && (
                            <>
                                <label className="form-label">Password</label>
                                <input name="password" className="form-control mb-2" minLength={6} value={values.password} onChange={handleChange} required />
                            </>
                        )}
                        <div className="row g-2 mb-3">
                            <div className="col">
                                <label className="form-label">Role</label>
                                <select name="role" className="form-select" value={values.role} onChange={handleChange}>
                                    <option value="customer">Customer</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div className="col">
                                <label className="form-label">Status</label>
                                <select name="status" className="form-select" value={values.status} onChange={handleChange}>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div className="text-end">
                            <button type="button" className="btn btn-outline-secondary me-2" onClick={() => setFormOpen(false)}>Cancel</button>
                            <button className="btn btn-primary">Save</button>
                        </div>
                    </form>
                </Modal>
            )}

            {passwordUser && (
                <Modal title={`Reset Password: ${passwordUser.name}`} size="md" onClose={() => setPasswordUser(null)}>
                    <form onSubmit={savePassword}>
                        {passwordError && <div className="alert alert-danger">{passwordError}</div>}
                        <label className="form-label">New Password</label>
                        <input className="form-control mb-3" minLength={6} value={newPassword} onChange={(e) => setNewPassword(e.target.value)} required />
                        <div className="text-end">
                            <button type="button" className="btn btn-outline-secondary me-2" onClick={() => setPasswordUser(null)}>Cancel</button>
                            <button className="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </Modal>
            )}
        </>
    );
}
