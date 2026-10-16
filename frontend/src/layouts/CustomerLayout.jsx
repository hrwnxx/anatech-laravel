import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom';
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
        <div className="site-shell">
            <nav className="site-nav sticky-top" aria-label="Main navigation">
                <div className="container d-flex justify-content-between align-items-center gap-3 py-2">
                    <Link to="/" className="brand" aria-label="Anatech home">
                        <img src="/images/logo-wordmark.png" alt="Anatech" className="brand-logo" />
                    </Link>

                    {user ? (
                        <div className="nav-actions">
                            <span className="nav-greeting d-none d-lg-inline">Hi, {user.name.split(' ')[0]}</span>
                            {user.role === 'admin' && (
                                <Link to="/admin" className="btn btn-dark btn-sm">
                                    <i className="bi bi-speedometer2"></i>
                                    <span className="d-none d-sm-inline ms-1">Admin Panel</span>
                                </Link>
                            )}
                            <NavLink to="/my/purchases" className="nav-pill">
                                <i className="bi bi-bag-check"></i>
                                <span className="d-none d-sm-inline ms-1">My Purchases</span>
                            </NavLink>
                            <NavLink to="/my/reviews" className="nav-pill">
                                <i className="bi bi-chat-square-text"></i>
                                <span className="d-none d-sm-inline ms-1">My Reviews</span>
                            </NavLink>
                            <button type="button" onClick={handleLogout} className="nav-pill nav-pill-danger">
                                <i className="bi bi-box-arrow-right"></i>
                                <span className="d-none d-sm-inline ms-1">Logout</span>
                            </button>
                        </div>
                    ) : (
                        <Link to="/login" className="btn btn-dark btn-sm">
                            <i className="bi bi-person me-1"></i>Login
                        </Link>
                    )}
                </div>
            </nav>

            <main className="container site-main">
                <Outlet />
            </main>

            <footer className="site-footer">
                <div className="container d-flex flex-column flex-md-row justify-content-between gap-1">
                    <span>&copy; {new Date().getFullYear()} Anatech. Gadget catalog &amp; reviews.</span>
                    <span>Reviews are from verified in-store buyers only.</span>
                </div>
            </footer>
        </div>
    );
}
