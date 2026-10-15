import { useState } from 'react';
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router-dom';
import { errorMessage } from '../api';
import { useAuth } from '../auth/AuthContext';

// Google login sends the browser back to /login?error=... when something goes wrong.
const googleErrors = {
    'google-not-configured': 'Google login is not set up yet. Please use your email and password.',
    'google-failed': 'Google login was cancelled or failed. Please try again.',
    inactive: 'This account is inactive. Please contact the store.',
};

export default function LoginPage() {
    const { user, login } = useAuth();
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState(googleErrors[searchParams.get('error')] || '');
    const [submitting, setSubmitting] = useState(false);

    // Already logged in? Go straight to the right place.
    if (user) {
        return <Navigate to={user.role === 'admin' ? '/admin' : '/'} replace />;
    }

    async function handleSubmit(event) {
        event.preventDefault();
        setSubmitting(true);
        setError('');

        try {
            const loggedInUser = await login(email, password);
            navigate(loggedInUser.role === 'admin' ? '/admin' : '/');
        } catch (err) {
            setError(errorMessage(err));
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="container mt-5">
            <div className="col-md-4 mx-auto">
                <div className="text-center mb-3">
                    <Link to="/" className="brand">
                        <img src="/images/logo-wordmark.png" alt="Anatech" className="brand-logo" />
                    </Link>
                </div>
                <Link to="/" className="btn btn-outline-secondary btn-sm mb-3">&larr; Back to Catalog</Link>

                <div className="card p-4">
                    <h3 className="text-center">Login</h3>

                    {error && <div className="alert alert-danger">{error}</div>}

                    <form onSubmit={handleSubmit}>
                        <input
                            type="email"
                            className="form-control mb-2"
                            placeholder="Email"
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
                            {submitting ? 'Logging in...' : 'Login'}
                        </button>
                    </form>

                    {/* A normal link, not <Link>: the browser has to leave React and go to Google. */}
                    <a href="/auth/google/redirect" className="btn btn-outline-dark w-100 mt-2">
                        <i className="bi bi-google me-2"></i>Continue with Google
                    </a>

                    <hr />
                    <small className="text-muted d-block">
                        New here? Accounts are set up by our staff when you make a purchase in-store.
                    </small>
                </div>
            </div>
        </div>
    );
}
