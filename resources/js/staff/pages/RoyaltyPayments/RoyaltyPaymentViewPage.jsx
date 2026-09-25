import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canUpdateRoyaltyPayment, canDeleteRoyaltyPayment } from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const fmt = (v, c) => `${c || 'NGN'} ${Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function RoyaltyPaymentViewPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [payment, setPayment] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get(`/royalty-payments/${id}`);
        setPayment(data.data);
      } catch (err) {
        if (err.response?.status === 401) return;
        if (err.response?.status === 404) setError('Payment not found.');
        else setError('Could not load payment.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id]);

  const doDelete = async () => {
    setDeleting(true);
    try {
      await api.delete(`/royalty-payments/${id}`);
      navigate('/royalty-payments');
    } catch {
      alert('Could not delete payment.');
      setDeleting(false);
    }
  };

  if (loading) {
    return (
      <div className="text-center py-5 text-muted">
        <div className="spinner-border spinner-border-sm me-2"></div>Loading…
      </div>
    );
  }

  if (error || !payment) {
    return (
      <>
        <Link to="/royalty-payments" className="text-muted text-decoration-none small">
          <i className="bi bi-arrow-left me-1"></i> Back to Payments
        </Link>
        <div className="alert alert-warning mt-3">{error || 'Payment not found.'}</div>
      </>
    );
  }

  return (
    <>
      <div className="d-flex justify-content-between align-items-start mb-4">
        <div>
          <Link to="/royalty-payments" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Payments
          </Link>
          <div className="d-flex align-items-center gap-3 mt-2">
            <h4 className="mb-0">{payment.payment_code}</h4>
            <span className="badge bg-light text-dark border text-capitalize">
              {(payment.method || '').replace(/_/g, ' ')}
            </span>
          </div>
          {payment.artist && (
            <p className="text-muted mb-0 small mt-1">
              Paid to <Link to={`/artists/${payment.artist.id}`}>{payment.artist.name}</Link>
            </p>
          )}
        </div>

        <div className="d-flex gap-2">
          {canUpdateRoyaltyPayment(roleSlug) && (
            <Link to={`/royalty-payments/${payment.id}/edit`} className="btn btn-outline-secondary">
              <i className="bi bi-pencil me-2"></i> Edit
            </Link>
          )}
          {canDeleteRoyaltyPayment(roleSlug) && (
            <button
              type="button"
              className="btn btn-outline-danger"
              onClick={() => setConfirmDelete(true)}
            >
              <i className="bi bi-trash me-2"></i> Delete
            </button>
          )}
        </div>
      </div>

      <div className="row g-3">
        <div className="col-lg-8">
          <div className="card border-0 shadow-sm mb-3">
            <div className="card-header bg-white"><strong>Details</strong></div>
            <div className="card-body">
              <dl className="row mb-0">
                <dt className="col-sm-4 text-muted fw-normal">Amount</dt>
                <dd className="col-sm-8 fw-semibold">{fmt(payment.amount, payment.currency)}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Currency</dt>
                <dd className="col-sm-8">{payment.currency}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Paid At</dt>
                <dd className="col-sm-8">{payment.paid_at || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Method</dt>
                <dd className="col-sm-8 text-capitalize">{(payment.method || '').replace(/_/g, ' ')}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Reference</dt>
                <dd className="col-sm-8">{payment.reference || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Statement</dt>
                <dd className="col-sm-8">
                  {payment.statement ? (
                    <Link to={`/royalty-statements/${payment.statement.id}`}>
                      {payment.statement.statement_code}
                    </Link>
                  ) : ('—')}
                </dd>

                <dt className="col-sm-4 text-muted fw-normal">Recorded by</dt>
                <dd className="col-sm-8">{payment.created_by?.name || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Recorded on</dt>
                <dd className="col-sm-8">
                  {payment.created_at ? new Date(payment.created_at).toLocaleString() : '—'}
                </dd>
              </dl>
            </div>
          </div>

          {payment.notes && (
            <div className="card border-0 shadow-sm">
              <div className="card-header bg-white"><strong>Notes</strong></div>
              <div className="card-body">
                <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{payment.notes}</p>
              </div>
            </div>
          )}
        </div>
      </div>

      <ConfirmDeleteModal
        show={confirmDelete}
        title="Delete payment?"
        message={`Payment ${payment.payment_code} will be soft-deleted. If the parent statement becomes underpaid, its status will auto-revert to "issued".`}
        loading={deleting}
        onCancel={() => setConfirmDelete(false)}
        onConfirm={doDelete}
      />
    </>
  );
}