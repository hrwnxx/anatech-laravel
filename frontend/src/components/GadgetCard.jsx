import { Link } from 'react-router-dom';
import { peso } from '../format';
import StarRating from './StarRating';
import StockBadge from './StockBadge';

// One product card in the catalog grid.
export default function GadgetCard({ gadget }) {
    return (
        <div className="col-md-4 col-lg-3 mb-4">
            <div className="card gadget-card">
                <div className="gadget-card-img-wrap">
                    <span className="gadget-card-category">{gadget.category}</span>
                    {gadget.image_url ? (
                        <img src={gadget.image_url} alt={gadget.gadget_name} />
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
                    <div className="mb-2">
                        <StockBadge stock={gadget.stock} />
                    </div>
                    <Link to={`/gadgets/${gadget.id}`} className="btn btn-dark btn-sm w-100">View Details</Link>
                </div>
            </div>
        </div>
    );
}
