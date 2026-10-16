// A Bootstrap-styled popup controlled by React state:
// {showForm && <Modal title="Add Gadget" onClose={() => setShowForm(false)}>...</Modal>}
export default function Modal({ title, onClose, children, size = 'lg' }) {
    return (
        <>
            <div className="modal d-block" tabIndex="-1" onClick={onClose}>
                <div className={`modal-dialog modal-dialog-centered modal-${size}`} onClick={(e) => e.stopPropagation()}>
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title">{title}</h5>
                            <button type="button" className="btn-close" onClick={onClose}></button>
                        </div>
                        <div className="modal-body">{children}</div>
                    </div>
                </div>
            </div>
            <div className="modal-backdrop show"></div>
        </>
    );
}
