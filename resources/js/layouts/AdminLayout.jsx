import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';

const links = [
    { to: '/admin', icon: 'bi-speedometer2', label: 'Dashboard' },
    { to: '/admin/gadgets', icon: 'bi-phone', label: 'Gadgets' },
    { to: '/admin/reviews', icon: 'bi-star', label: 'Reviews' },
    { to: '/admin/purchases', icon: 'bi-bag-check', label: 'Purchases' },
    { to: '/admin/customers', icon: 'bi-person-lines-fill', label: 'Customers' },
    { to: '/admin/users', icon: 'bi-people', label: 'Users' },
];

// Highlights the link of the page you're on (same blue as the old admin panel).
function activeStyle({ isActive }) {
    return isActive ? { background: '#0d6efd', fontWeight: 600 } : undefined;
}

// Sidebar + top bar shared by every admin page. <Outlet /> is where the page goes.
export default function AdminLayout() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();

    async function handleLogout(event) {
        event.preventDefault();
        await logout();
        navigate('/login');
    }

    return (
        <div className="wrapper">
            <div className="sidebar">
                <div className="logo">
                    <img src="/images/logo-mark.png" alt="Anatech" className="sidebar-logo" />
                </div>
                <ul>
                    {links.map((link) => (
                        <li key={link.to}>
                            <NavLink to={link.to} end style={activeStyle}>
                                <i className={`bi ${link.icon}`}></i> {link.label}
                            </NavLink>
                        </li>
                    ))}
                    <li>
                        <NavLink to="/">
                            <i className="bi bi-shop"></i> View Store
                        </NavLink>
                    </li>
                    <li>
                        <a href="/logout" onClick={handleLogout}>
                            <i className="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <div className="main">
                <div className="topbar">
                    <div>
                        <h3>Administrator Dashboard</h3>
                        <small>Welcome, {user.name}</small>
                    </div>
                    <i className="bi bi-person-circle fs-2"></i>
                </div>
                <div className="content">
                    <Outlet />
                </div>
            </div>
        </div>
    );
}
