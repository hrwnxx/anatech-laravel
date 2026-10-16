import { useState } from 'react';
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router-dom';
import { errorMessage } from '../../api';
import { useAuth } from '../../auth/AuthContext';

// Google login sends the browser back to /login?error=... when something goes wrong.
const googleErrors = {
    'google-not-configured': 'Google login is not set up yet. If our staff gave you a password, log in with it below.',
    'google-cancelled': 'Google login was cancelled.',
    'google-failed': 'Google login failed. Please try again.',
    'google-unverified': "Google hasn't verified this account's email address, so it can't be used here.",
    'no-account': "We couldn't find a customer account for that Google email. Accounts are created by our staff when you buy in store, so please ask them to check the email on your account.",
    inactive: 'This account is inactive. Please contact the store.',
};

// Customer login. Customers log in with the Gmail address they gave our staff
// at the store; customers without Gmail use the temporary password instead.
// (Staff use their own login page, which isn't linked from the store.)
export default function LoginPage() {
    const { user, login } = useAuth();
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const errorCode = searchParams.get('error');
    const [showPasswordLogin, setShowPasswordLogin] = useState(errorCode === 'google-not-configured');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState(googleErrors[errorCode] || '');
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
            await login(email, password);
            navigate('/');
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
                    <Link to="/" className="brand">
                        <img src="/images/logo-wordmark.png" alt="Anatech" className="brand-logo" />
                    </Link>
                </div>
                <Link to="/" className="btn btn-outline-secondary btn-sm mb-3">&larr; Back to Catalog</Link>

                <div className="card p-4">
                    <h3 className="text-center">Customer Login</h3>

                    {error && <div className="alert alert-danger">{error}</div>}

                    {/* A normal link, not <Link>: the browser has to leave React and go to Google. */}
                    <a href="/auth/google/redirect" className="btn btn-dark w-100">
                        <i className="bi bi-google me-2"></i>Continue with Google
                    </a>
                    <small className="text-muted d-block text-center mt-2">
                        Use the Gmail address you gave our staff when you bought your gadget.
                    </small>

                    <hr />

                    {showPasswordLogin ? (
                        <form onSubmit={handleSubmit}>
                            <p className="small text-muted mb-2">
                                No Gmail? Log in with the temporary password our staff gave you.
                            </p>
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
                            <button className="btn btn-outline-dark w-100" disabled={submitting}>
                                {submitting ? 'Logging in...' : 'Log in with password'}
                            </button>
                        </form>
                    ) : (
                        <button type="button" className="btn btn-link w-100" onClick={() => setShowPasswordLogin(true)}>
                            No Gmail? Log in with password
                        </button>
                    )}

                    <hr />
                    <small className="text-muted d-block">
                        New here? Accounts are created by our staff when you buy a gadget in store.
                    </small>
                </div>
            </div>
        </div>
    );
}
