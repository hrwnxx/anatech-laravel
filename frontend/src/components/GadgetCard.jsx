import { Link } from 'react-router-dom';
import { peso } from '../format';
import StarRating from './StarRating';
import StockBadge from './StockBadge';

// One product card in the catalog grid. The whole card is clickable: the
// "View Details" link stretches over it (Bootstrap's stretched-link).
export default function GadgetCard({ gadget }) {
    const soldOut = gadget.stock === 0;

    return (
        <div className="col-6 col-md-4 col-lg-3 mb-4">
            <div className={`card gadget-card ${soldOut ? 'is-sold-out' : ''}`}>
                <div className="gadget-card-img-wrap">
                    <span className="gadget-card-category">{gadget.category}</span>
                    {soldOut && <span className="gadget-card-soldout">Sold out</span>}
                    {gadget.image_url ? (
                        <img src={gadget.image_url} alt={gadget.gadget_name} loading="lazy" />
                    ) : (
                        <i className="bi bi-image fs-1 text-muted"></i>
                    )}
                </div>
                <div className="card-body">
                    <h6 className="card-title">{gadget.brand} {gadget.gadget_name}</h6>
                    <p className="card-model mb-0">{gadget.model}</p>
                    <p className="card-rating">
                        {gadget.review_count > 0 ? (
                            <>
                                <StarRating rating={gadget.avg_rating} /> ({gadget.review_count})
                            </>
                        ) : (
                            'No reviews yet'
                        )}
                    </p>
                    <p className="card-price">{peso(gadget.price)}</p>
                    <div className="mb-3">
                        <StockBadge stock={gadget.stock} />
                    </div>
                    <Link
                        to={`/gadgets/${gadget.id}`}
                        className="btn btn-dark btn-sm w-100 stretched-link card-cta"
                        aria-label={`View details for ${gadget.brand} ${gadget.gadget_name}`}
                    >
                        View Details <i className="bi bi-arrow-right ms-1"></i>
                    </Link>
                </div>
            </div>
        </div>
    );
}
