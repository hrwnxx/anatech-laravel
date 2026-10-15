export default function StockBadge({ stock }) {
    if (stock === 0) {
        return <span className="badge bg-danger">Out of Stock</span>;
    }
    if (stock <= 5) {
        return <span className="badge bg-warning text-dark">Low Stock: {stock}</span>;
    }
    return <span className="badge bg-success">In Stock: {stock}</span>;
}
