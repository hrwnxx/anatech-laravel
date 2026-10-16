import { useState } from 'react';
import { Navigate, useNavigate } from 'react-router-dom';
import { errorMessage } from '../../api';
import { useAuth } from '../../auth/AuthContext';

// Staff login at /staff-login. Deliberately not linked from any public page;
// staff bookmark it. The real protection is still the server: every admin
// API checks the logged-in user's role.
export default function StaffLoginPage() {
    const { user, staffLogin } = useAuth();
    const navigate = useNavigate();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [submitting, setSubmitting] = useState(false);

    if (user) {
        return <Navigate to={user.role === 'admin' ? '/admin' : '/'} replace />;
    }

    async function handleSubmit(event) {
        event.preventDefault();
        setSubmitting(true);
        setError('');

        try {
            await staffLogin(email, password);
            navigate('/admin');
        } catch (err) {
            setError(errorMessage(err));
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="container mt-5">
            <div className="col-md-5 col-lg-4 mx-auto">
                <div className="text-center mb-3">
                    <img src="/images/logo-wordmark.png" alt="Anatech" className="brand-logo" />
                </div>

                <div className="card p-4">
                    <h3 className="text-center">Staff Login</h3>

                    {error && <div className="alert alert-danger">{error}</div>}

                    <form onSubmit={handleSubmit}>
                        <input
                            type="email"
                            className="form-control mb-2"
                            placeholder="Staff email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            required
                        />
                        <input
                            type="password"
                            className="form-control mb-2"
                            placeholder="Password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            required
                        />
                        <button className="btn btn-primary w-100" disabled={submitting}>
                            {submitting ? 'Logging in...' : 'Log in'}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}
