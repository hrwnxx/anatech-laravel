import { useEffect, useState } from 'react';
import api from '../api';
import GadgetCard from '../components/GadgetCard';

export default function CatalogPage() {
    const [gadgets, setGadgets] = useState([]);
    const [categories, setCategories] = useState([]);
    const [brands, setBrands] = useState([]);
    const [filters, setFilters] = useState({ keyword: '', category_id: '', brand: '', sort: '' });
    const [loading, setLoading] = useState(true);

    // Load the dropdown options once, when the page opens.
    useEffect(() => {
        api.get('/categories').then((response) => setCategories(response.data));
        api.get('/brands').then((response) => setBrands(response.data));
    }, []);

    // Reload the gadgets every time a filter changes. The 300 ms delay waits
    // for the user to stop typing, so we don't call the API on every key press.
    useEffect(() => {
        setLoading(true);
        const timer = setTimeout(() => {
            api.get('/gadgets', { params: filters })
                .then((response) => setGadgets(response.data))
                .finally(() => setLoading(false));
        }, 300);

        return () => clearTimeout(timer);
    }, [filters]);

    // One handler for all four inputs: the input's "name" says which filter to change.
    function handleFilterChange(event) {
        setFilters({ ...filters, [event.target.name]: event.target.value });
    }

    return (
        <>
            <div className="catalog-heading mb-4">
                <h2>Gadget Catalog</h2>
                <p className="text-muted mb-0">
                    Browse gadgets below. Click a gadget to view its details and customer reviews.
                </p>
            </div>

            <div className="row mb-4 g-2 filter-bar">
                <div className="col-md-4">
                    <div className="input-group">
                        <span className="input-group-text"><i className="bi bi-search"></i></span>
                        <input
                            name="keyword"
                            className="form-control"
                            placeholder="Search phone, laptop, brand or model..."
                            value={filters.keyword}
                            onChange={handleFilterChange}
                        />
                    </div>
                </div>
                <div className="col-md-3">
                    <select name="category_id" className="form-select" value={filters.category_id} onChange={handleFilterChange}>
                        <option value="">All Categories</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>{category.name}</option>
                        ))}
                    </select>
                </div>
                <div className="col-md-2">
                    <select name="brand" className="form-select" value={filters.brand} onChange={handleFilterChange}>
                        <option value="">All Brands</option>
                        {brands.map((brand) => (
                            <option key={brand} value={brand}>{brand}</option>
                        ))}
                    </select>
                </div>
                <div className="col-md-3">
                    <select name="sort" className="form-select" value={filters.sort} onChange={handleFilterChange}>
                        <option value="">Newest first</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="rating">Top Rated</option>
                        <option value="popular">Best Selling</option>
                        <option value="name">Name: A to Z</option>
                    </select>
                </div>
            </div>

            {loading && <p className="text-muted">Loading gadgets...</p>}

            {!loading && gadgets.length === 0 && (
                <div className="alert alert-secondary text-center">No gadgets match your filters.</div>
            )}

            <div className="row">
                {gadgets.map((gadget) => (
                    <GadgetCard key={gadget.id} gadget={gadget} />
                ))}
            </div>
        </>
    );
}
