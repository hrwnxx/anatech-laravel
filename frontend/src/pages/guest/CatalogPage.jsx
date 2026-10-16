import { useEffect, useState } from 'react';
import api from '../../api';
import GadgetCard from '../../components/GadgetCard';

const emptyFilters = { keyword: '', category_id: '', brand: '', sort: '' };

// Gray placeholder cards shown while the gadgets load.
function SkeletonCard() {
    return (
        <div className="col-6 col-md-4 col-lg-3 mb-4" aria-hidden="true">
            <div className="card gadget-card skeleton-card">
                <div className="gadget-card-img-wrap skeleton"></div>
                <div className="card-body">
                    <div className="skeleton skeleton-line w-75"></div>
                    <div className="skeleton skeleton-line w-50"></div>
                    <div className="skeleton skeleton-line w-25 mt-3"></div>
                    <div className="skeleton skeleton-button mt-3"></div>
                </div>
            </div>
        </div>
    );
}

export default function CatalogPage() {
    const [gadgets, setGadgets] = useState([]);
    const [categories, setCategories] = useState([]);
    const [brands, setBrands] = useState([]);
    const [filters, setFilters] = useState(emptyFilters);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [reloadCount, setReloadCount] = useState(0);

    // Load the dropdown options once, when the page opens.
    useEffect(() => {
        api.get('/categories').then((response) => setCategories(response.data)).catch(() => {});
        api.get('/brands').then((response) => setBrands(response.data)).catch(() => {});
    }, []);

    // Reload the gadgets every time a filter changes (or "Try again" is pressed).
    // The 300 ms delay waits for the user to stop typing. "ignore" drops the
    // answer of an older request, so a slow response can't replace newer results.
    useEffect(() => {
        let ignore = false;
        setLoading(true);
        setError('');

        const timer = setTimeout(() => {
            api.get('/gadgets', { params: filters })
                .then((response) => {
                    if (!ignore) {
                        setGadgets(response.data);
                    }
                })
                .catch(() => {
                    if (!ignore) {
                        setError('We could not load the gadgets. Check your connection and try again.');
                    }
                })
                .finally(() => {
                    if (!ignore) {
                        setLoading(false);
                    }
                });
        }, 300);

        return () => {
            ignore = true;
            clearTimeout(timer);
        };
    }, [filters, reloadCount]);

    // One handler for the text box and the dropdowns: the input's "name" says which filter to change.
    function handleFilterChange(event) {
        setFilters({ ...filters, [event.target.name]: event.target.value });
    }

    function chooseCategory(categoryId) {
        setFilters({ ...filters, category_id: categoryId });
    }

    const hasFilters = Object.values(filters).some((value) => value !== '');

    return (
        <>
            <section className="catalog-hero">
                <p className="hero-eyebrow">Anatech Gadget Store</p>
                <h1>Find your next gadget.</h1>
                <p className="hero-text">
                    Phones, tablets, laptops, smartwatches and accessories, with honest reviews
                    from customers who actually bought them in store.
                </p>
                <div className="hero-search">
                    <i className="bi bi-search" aria-hidden="true"></i>
                    <input
                        name="keyword"
                        type="search"
                        aria-label="Search gadgets"
                        placeholder="Search gadgets, brands, models..."
                        value={filters.keyword}
                        onChange={handleFilterChange}
                    />
                </div>
            </section>

            <div className="catalog-toolbar">
                <div className="chip-row" role="group" aria-label="Filter by category">
                    <button
                        type="button"
                        className={`chip ${filters.category_id === '' ? 'active' : ''}`}
                        aria-pressed={filters.category_id === ''}
                        onClick={() => chooseCategory('')}
                    >
                        All
                    </button>
                    {categories.map((category) => (
                        <button
                            type="button"
                            key={category.id}
                            className={`chip ${String(filters.category_id) === String(category.id) ? 'active' : ''}`}
                            aria-pressed={String(filters.category_id) === String(category.id)}
                            onClick={() => chooseCategory(String(category.id))}
                        >
                            {category.name}
                        </button>
                    ))}
                </div>

                <div className="toolbar-selects">
                    <select name="brand" aria-label="Filter by brand" className="form-select" value={filters.brand} onChange={handleFilterChange}>
                        <option value="">All Brands</option>
                        {brands.map((brand) => (
                            <option key={brand} value={brand}>{brand}</option>
                        ))}
                    </select>
                    <select name="sort" aria-label="Sort gadgets" className="form-select" value={filters.sort} onChange={handleFilterChange}>
                        <option value="">Newest first</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="rating">Top Rated</option>
                        <option value="popular">Best Selling</option>
                        <option value="name">Name: A to Z</option>
                    </select>
                </div>
            </div>

            <div className="result-bar" aria-live="polite">
                <span>
                    {loading ? 'Loading gadgets...' : `${gadgets.length} ${gadgets.length === 1 ? 'gadget' : 'gadgets'}`}
                </span>
                {hasFilters && (
                    <button type="button" className="clear-filters" onClick={() => setFilters(emptyFilters)}>
                        <i className="bi bi-x-circle me-1"></i>Clear filters
                    </button>
                )}
            </div>

            {error && (
                <div className="state-box" role="alert">
                    <i className="bi bi-wifi-off"></i>
                    <p>{error}</p>
                    <button type="button" className="btn btn-dark btn-sm" onClick={() => setReloadCount(reloadCount + 1)}>
                        <i className="bi bi-arrow-clockwise me-1"></i>Try again
                    </button>
                </div>
            )}

            {!error && !loading && gadgets.length === 0 && (
                <div className="state-box">
                    <i className="bi bi-search"></i>
                    <p>No gadgets match your search.</p>
                    <button type="button" className="btn btn-outline-dark btn-sm" onClick={() => setFilters(emptyFilters)}>
                        Clear filters
                    </button>
                </div>
            )}

            <div className="row">
                {!error && loading && Array.from({ length: 8 }, (_, index) => <SkeletonCard key={index} />)}
                {!error && !loading && gadgets.map((gadget) => <GadgetCard key={gadget.id} gadget={gadget} />)}
            </div>
        </>
    );
}
