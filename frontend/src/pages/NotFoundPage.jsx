import { Link } from 'react-router-dom';

export default function NotFoundPage() {
    return (
        <div className="container text-center py-5">
            <h1 className="display-4">404</h1>
            <p className="text-muted">We couldn't find that page.</p>
            <Link to="/" className="btn btn-dark">Back to Catalog</Link>
        </div>
    );
}
