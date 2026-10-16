import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from './AuthContext';

// Protects the pages inside it. Not logged in -> the matching login page
// (staff login for admin pages, customer login for the rest).
// With role="admin", customers are sent back to the catalog.
export default function RequireLogin({ role }) {
    const { user, loading } = useAuth();

    if (loading) {
        return <p className="text-center text-muted p-5">Loading...</p>;
    }
    if (!user) {
        return <Navigate to={role === 'admin' ? '/staff-login' : '/login'} replace />;
    }
    if (role && user.role !== role) {
        return <Navigate to="/" replace />;
    }

    return <Outlet />;
}
