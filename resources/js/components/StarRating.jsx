// Shows a 1-5 rating as stars, e.g. 4 -> ★★★★☆
export default function StarRating({ rating }) {
    const filled = Math.round(rating);

    return (
        <span className="star-rating" title={`${rating} out of 5`}>
            {'★'.repeat(filled)}
            {'☆'.repeat(5 - filled)}
        </span>
    );
}
