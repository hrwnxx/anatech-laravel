// How someone logs in, shown on the admin Users and Customers pages.
export default function LoginMethodBadge({ user }) {
    if (user.role === 'admin') {
        return <span className="badge bg-light text-dark border">Staff password</span>;
    }

    return (
        <>
            {user.has_google_login ? (
                <span className="badge bg-success me-1"><i className="bi bi-google me-1"></i>Google</span>
            ) : (
                !user.has_password && (
                    <span className="badge bg-warning text-dark me-1" title="Linked the first time they use Continue with Google">
                        Google (not used yet)
                    </span>
                )
            )}
            {user.has_password && <span className="badge bg-secondary">Password</span>}
        </>
    );
}
