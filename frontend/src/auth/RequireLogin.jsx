import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from './AuthContext';

// Protects the pages inside it. Not logged in -> Login page.
// With role="admin", customers are sent back to the catalog.
export default function RequireLogin({ role }) {
    const { user, loading } = useAuth();

    if (loading) {
        return <p className="text-center text-muted p-5">Loading...</p>;
    }
    if (!user) {
        return <Navigate to="/login" replace />;
    }
    if (role && user.role !== role) {
        return <Navigate to="/" replace />;
    }

    return <Outlet />;
}
