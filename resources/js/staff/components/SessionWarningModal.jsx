export default function SessionWarningModal({ open, onStay, onLogout }) {
  if (!open) return null;

  return (
    <div className="kfm-modal-overlay" role="dialog" aria-modal="true">
      <div className="kfm-modal kfm-modal--sm">
        <div className="kfm-modal__header">
          <h5 className="mb-0">Are you still there?</h5>
        </div>

        <div className="kfm-modal__body">
          <p className="mb-3">
            You&apos;ve been inactive for a while. For your security,
            we&apos;ll sign you out soon unless you continue.
          </p>
          <p className="text-muted small mb-0">
            Any unsaved work may be lost if your session expires.
          </p>
        </div>

        <div className="kfm-modal__footer d-flex gap-2 justify-content-end">
          <button
            type="button"
            className="btn btn-outline-secondary"
            onClick={onLogout}
          >
            Log out
          </button>
          <button
            type="button"
            className="btn btn-dark"
            onClick={onStay}
            autoFocus
          >
            Stay signed in
          </button>
        </div>
      </div>
    </div>
  );
}