import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canUpdateRevenueEntry, canDeleteRevenueEntry } from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const fmt = (v, c) => `${c || 'NGN'} ${Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function RevenueEntryViewPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [entry, setEntry] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get(`/revenue-entries/${id}`);
        setEntry(data.data);
      } catch (err) {
        if (err.response?.status === 401) return;
        if (err.response?.status === 404) setError('Revenue entry not found.');
        else setError('Could not load revenue entry.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id]);

  const doDelete = async () => {
    setDeleting(true);
    try {
      await api.delete(`/revenue-entries/${id}`);
      navigate('/revenue-entries');
    } catch {
      alert('Could not delete entry.');
      setDeleting(false);
    }
  };

  if (loading) {
    return (
      <div className="text-center py-5 text-muted">
        <div className="spinner-border spinner-border-sm me-2"></div>
        Loading…
      </div>
    );
  }

  if (error || !entry) {
    return (
      <>
        <Link to="/revenue-entries" className="text-muted text-decoration-none small">
          <i className="bi bi-arrow-left me-1"></i> Back to Revenue
        </Link>
        <div className="alert alert-warning mt-3">{error || 'Revenue entry not found.'}</div>
      </>
    );
  }

  return (
    <>
      <div className="d-flex justify-content-between align-items-start mb-4">
        <div>
          <Link to="/revenue-entries" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Revenue
          </Link>
          <div className="d-flex align-items-center gap-3 mt-2">
            <h4 className="mb-0">{entry.entry_code}</h4>
            <span className="badge bg-light text-dark border text-capitalize">
              {(entry.source || '').replace(/_/g, ' ')}
            </span>
          </div>
          <p className="text-muted mb-0 small mt-1">{entry.platform || '—'}</p>
        </div>

        <div className="d-flex gap-2">
          {canUpdateRevenueEntry(roleSlug) && (
            <Link to={`/revenue-entries/${entry.id}/edit`} className="btn btn-outline-secondary">
              <i className="bi bi-pencil me-2"></i>
              Edit
            </Link>
          )}
          {canDeleteRevenueEntry(roleSlug) && (
            <button
              type="button"
              className="btn btn-outline-danger"
              onClick={() => setConfirmDelete(true)}
            >
              <i className="bi bi-trash me-2"></i>
              Delete
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
                <dd className="col-sm-8 fw-semibold">{fmt(entry.amount, entry.currency)}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Currency</dt>
                <dd className="col-sm-8">{entry.currency}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Period</dt>
                <dd className="col-sm-8">
                  {entry.period_start || '—'} → {entry.period_end || '—'}
                </dd>

                <dt className="col-sm-4 text-muted fw-normal">Received At</dt>
                <dd className="col-sm-8">{entry.received_at || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Reference</dt>
                <dd className="col-sm-8">{entry.reference || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Artist</dt>
                <dd className="col-sm-8">
                  {entry.artist ? <Link to={`/artists/${entry.artist.id}`}>{entry.artist.name}</Link> : '—'}
                </dd>

                <dt className="col-sm-4 text-muted fw-normal">Release</dt>
                <dd className="col-sm-8">
                  {entry.release ? <Link to={`/releases/${entry.release.id}`}>{entry.release.title}</Link> : '—'}
                </dd>

                <dt className="col-sm-4 text-muted fw-normal">Track</dt>
                <dd className="col-sm-8">
                  {entry.track ? <Link to={`/tracks/${entry.track.id}`}>{entry.track.title}</Link> : '—'}
                </dd>

                <dt className="col-sm-4 text-muted fw-normal">Distribution</dt>
                <dd className="col-sm-8">
                  {entry.distribution ? (
                    <Link to={`/distributions/${entry.distribution.id}`}>
                      {entry.distribution.distribution_code}
                    </Link>
                  ) : (
                    '—'
                  )}
                </dd>

                <dt className="col-sm-4 text-muted fw-normal">Added by</dt>
                <dd className="col-sm-8">{entry.created_by?.name || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Added on</dt>
                <dd className="col-sm-8">
                  {entry.created_at ? new Date(entry.created_at).toLocaleString() : '—'}
                </dd>
              </dl>
            </div>
          </div>

          {entry.notes && (
            <div className="card border-0 shadow-sm">
              <div className="card-header bg-white"><strong>Notes</strong></div>
              <div className="card-body">
                <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{entry.notes}</p>
              </div>
            </div>
          )}
        </div>
      </div>

      <ConfirmDeleteModal
        show={confirmDelete}
        title="Delete revenue entry?"
        message={`Entry ${entry.entry_code} will be soft-deleted.`}
        loading={deleting}
        onCancel={() => setConfirmDelete(false)}
        onConfirm={doDelete}
      />
    </>
  );
}