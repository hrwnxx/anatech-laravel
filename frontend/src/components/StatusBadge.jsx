const colors = {
    pending: 'bg-warning text-dark',
    approved: 'bg-success',
    rejected: 'bg-danger',
    active: 'bg-success',
    inactive: 'bg-secondary',
};

// A colored label for a review status (pending/approved/rejected)
// or an account status (active/inactive).
export default function StatusBadge({ status }) {
    return <span className={`badge text-capitalize ${colors[status] || 'bg-secondary'}`}>{status}</span>;
}
