import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canUpdateDistribution, canDeleteDistribution } from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const statusBadge = (status) => {
  switch (status) {
    case 'live':      return 'badge bg-success-subtle text-success-emphasis';
    case 'submitted': return 'badge bg-primary-subtle text-primary-emphasis';
    case 'pending':   return 'badge bg-warning-subtle text-warning-emphasis';
    case 'takedown':  return 'badge bg-secondary-subtle text-secondary-emphasis';
    case 'rejected':  return 'badge bg-danger-subtle text-danger-emphasis';
    case 'failed':    return 'badge bg-danger-subtle text-danger-emphasis';
    default:          return 'badge bg-light text-dark';
  }
};

const PLATFORM_ICONS = {
  spotify: 'bi-spotify',
  apple_music: 'bi-apple',
  youtube_music: 'bi-youtube',
  amazon_music: 'bi-music-note',
  deezer: 'bi-music-note-list',
  tidal: 'bi-water',
  audiomack: 'bi-music-note-beamed',
  boomplay: 'bi-play-circle',
  soundcloud: 'bi-soundwave',
  pandora: 'bi-broadcast',
  other: 'bi-globe',
};

export default function DistributionViewPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [distribution, setDistribution] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get(`/distributions/${id}`);
        setDistribution(data.data);
      } catch (err) {
        if (err.response?.status === 401) return;
        if (err.response?.status === 404) setError('Distribution not found.');
        else setError('Could not load distribution.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id]);

  const doDelete = async () => {
    setDeleting(true);
    try {
      await api.delete(`/distributions/${id}`);
      navigate('/distributions');
    } catch {
      alert('Could not delete distribution.');
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

  if (error || !distribution) {
    return (
      <>
        <Link to="/distributions" className="text-muted text-decoration-none small">
          <i className="bi bi-arrow-left me-1"></i> Back to Distribution
        </Link>
        <div className="alert alert-warning mt-3">{error || 'Distribution not found.'}</div>
      </>
    );
  }

  return (
    <>
      <div className="d-flex justify-content-between align-items-start mb-4">
        <div>
          <Link to="/distributions" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Distribution
          </Link>
          <div className="d-flex align-items-center gap-3 mt-2">
            <h4 className="mb-0">
              <i className={`bi ${PLATFORM_ICONS[distribution.platform] || 'bi-globe'} me-2`}></i>
              {(distribution.platform || '').replace(/_/g, ' ')}
            </h4>
            <span className="badge bg-light text-dark border">
              <code>{distribution.distribution_code}</code>
            </span>
            <span className={statusBadge(distribution.status)}>
              {(distribution.status || '').replace(/_/g, ' ')}
            </span>
          </div>
          {distribution.release && (
            <p className="text-muted mb-0 small mt-1">
              Release:{' '}
              <Link to={`/releases/${distribution.release.id}`}>
                {distribution.release.title}
              </Link>{' '}
              ({distribution.release.release_code})
            </p>
          )}
        </div>

        <div className="d-flex gap-2">
          {canUpdateDistribution(roleSlug) && (
            <Link to={`/distributions/${distribution.id}/edit`} className="btn btn-outline-secondary">
              <i className="bi bi-pencil me-2"></i>
              Edit
            </Link>
          )}
          {canDeleteDistribution(roleSlug) && (
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
                <dt className="col-sm-4 text-muted fw-normal">Distributor</dt>
                <dd className="col-sm-8">{distribution.distributor || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Territory</dt>
                <dd className="col-sm-8">{distribution.territory || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Platform Release ID</dt>
                <dd className="col-sm-8">
                  {distribution.platform_release_id ? (
                    <code>{distribution.platform_release_id}</code>
                  ) : (
                    '—'
                  )}
                </dd>

                <dt className="col-sm-4 text-muted fw-normal">Platform URL</dt>
                <dd className="col-sm-8 text-break">
                  {distribution.platform_url ? (
                    <a
                      href={distribution.platform_url}
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      {distribution.platform_url}
                      <i className="bi bi-box-arrow-up-right ms-2 small opacity-50"></i>
                    </a>
                  ) : (
                    '—'
                  )}
                </dd>
              </dl>
            </div>
          </div>

          {distribution.notes && (
            <div className="card border-0 shadow-sm">
              <div className="card-header bg-white"><strong>Notes</strong></div>
              <div className="card-body">
                <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{distribution.notes}</p>
              </div>
            </div>
          )}
        </div>

        <div className="col-lg-4">
          <div className="card border-0 shadow-sm">
            <div className="card-header bg-white"><strong>Timeline</strong></div>
            <div className="card-body">
              <dl className="row mb-0">
                <dt className="col-7 text-muted fw-normal">Scheduled For</dt>
                <dd className="col-5 text-end">{distribution.scheduled_for || '—'}</dd>

                <dt className="col-7 text-muted fw-normal">Submitted At</dt>
                <dd className="col-5 text-end">{distribution.submitted_at || '—'}</dd>

                <dt className="col-7 text-muted fw-normal">Live At</dt>
                <dd className="col-5 text-end">{distribution.live_at || '—'}</dd>

                <dt className="col-7 text-muted fw-normal">Takedown At</dt>
                <dd className="col-5 text-end">{distribution.takedown_at || '—'}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <ConfirmDeleteModal
        show={confirmDelete}
        title="Delete distribution?"
        message={`"${distribution.distribution_code}" (${distribution.platform}) will be soft-deleted.`}
        loading={deleting}
        onCancel={() => setConfirmDelete(false)}
        onConfirm={doDelete}
      />
    </>
  );
}