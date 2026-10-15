import { Link, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';

// Top navigation shared by every customer page. <Outlet /> is where the page goes.
export default function CustomerLayout() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();

    async function handleLogout() {
        await logout();
        navigate('/');
    }

    return (
        <>
            <nav className="site-nav py-3 mb-5">
                <div className="container d-flex justify-content-between align-items-center">
                    <Link to="/" className="brand">
                        <img src="/images/logo-wordmark.png" alt="Anatech" className="brand-logo" />
                    </Link>

                    {user ? (
                        <div>
                            <span className="text-muted me-3">Hi, {user.name}</span>
                            {user.role === 'admin' && (
                                <Link to="/admin" className="btn btn-dark btn-sm me-1">Admin Panel</Link>
                            )}
                            <Link to="/my/purchases" className="btn btn-outline-dark btn-sm me-1">My Purchases</Link>
                            <Link to="/my/reviews" className="btn btn-outline-dark btn-sm me-1">My Reviews</Link>
                            <button onClick={handleLogout} className="btn btn-outline-danger btn-sm">Logout</button>
                        </div>
                    ) : (
                        <Link to="/login" className="btn btn-outline-primary btn-sm">Login</Link>
                    )}
                </div>
            </nav>

            <div className="container pb-5">
                <Outlet />
            </div>
        </>
    );
}
