import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canUpdateRelease, canDeleteRelease } from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const statusBadge = (status) => {
  switch (status) {
    case 'released':  return 'badge bg-success-subtle text-success-emphasis';
    case 'scheduled': return 'badge bg-primary-subtle text-primary-emphasis';
    case 'draft':     return 'badge bg-secondary-subtle text-secondary-emphasis';
    case 'archived':  return 'badge bg-dark-subtle text-dark-emphasis';
    case 'cancelled': return 'badge bg-danger-subtle text-danger-emphasis';
    default:          return 'badge bg-light text-dark';
  }
};

export default function ReleaseViewPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [release, setRelease] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get(`/releases/${id}`);
        setRelease(data.data);
      } catch (err) {
        if (err.response?.status === 401) return;
        if (err.response?.status === 404) setError('Release not found.');
        else setError('Could not load release.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id]);

  const doDelete = async () => {
    setDeleting(true);
    try {
      await api.delete(`/releases/${id}`);
      navigate('/releases');
    } catch {
      alert('Could not delete release.');
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

  if (error || !release) {
    return (
      <>
        <Link to="/releases" className="text-muted text-decoration-none small">
          <i className="bi bi-arrow-left me-1"></i> Back to Releases
        </Link>
        <div className="alert alert-warning mt-3">{error || 'Release not found.'}</div>
      </>
    );
  }

  return (
    <>
      <div className="d-flex justify-content-between align-items-start mb-4">
        <div>
          <Link to="/releases" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Releases
          </Link>
          <div className="d-flex align-items-center gap-3 mt-2">
            <h4 className="mb-0">{release.title}</h4>
            <span className="badge bg-light text-dark border">
              <code>{release.release_code}</code>
            </span>
            <span className={statusBadge(release.status)}>
              {(release.status || '').replace(/_/g, ' ')}
            </span>
          </div>
          {release.artist && (
            <p className="text-muted mb-0 small mt-1">
              Artist:{' '}
              <Link to={`/artists/${release.artist.id}`}>
                {release.artist.name}
              </Link>
            </p>
          )}
        </div>

        <div className="d-flex gap-2">
          {canUpdateRelease(roleSlug) && (
            <Link to={`/releases/${release.id}/edit`} className="btn btn-outline-secondary">
              <i className="bi bi-pencil me-2"></i>
              Edit
            </Link>
          )}
          {canDeleteRelease(roleSlug) && (
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
                <dt className="col-sm-4 text-muted fw-normal">Type</dt>
                <dd className="col-sm-8 text-uppercase">{release.type}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Status</dt>
                <dd className="col-sm-8 text-capitalize">
                  {(release.status || '').replace(/_/g, ' ')}
                </dd>

                <dt className="col-sm-4 text-muted fw-normal">Release Date</dt>
                <dd className="col-sm-8">{release.release_date || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Pre-Save Date</dt>
                <dd className="col-sm-8">{release.pre_save_date || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">UPC</dt>
                <dd className="col-sm-8">{release.upc || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Label Copy</dt>
                <dd className="col-sm-8">{release.label_copy || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Created by</dt>
                <dd className="col-sm-8">{release.created_by?.name || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Created on</dt>
                <dd className="col-sm-8">
                  {release.created_at ? new Date(release.created_at).toLocaleString() : '—'}
                </dd>
              </dl>
            </div>
          </div>

          {release.description && (
            <div className="card border-0 shadow-sm mb-3">
              <div className="card-header bg-white"><strong>Description</strong></div>
              <div className="card-body">
                <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{release.description}</p>
              </div>
            </div>
          )}

          {Array.isArray(release.tracks) && release.tracks.length > 0 && (
            <div className="card border-0 shadow-sm">
              <div className="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Tracklist</strong>
                <span className="text-muted small">{release.tracks.length} tracks</span>
              </div>
              <div className="card-body p-0">
                <ol className="list-group list-group-flush list-group-numbered mb-0">
                  {release.tracks.map((t) => (
                    <li key={t.id} className="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <Link
                          to={`/tracks/${t.id}`}
                          className="text-decoration-none text-dark fw-semibold"
                        >
                          {t.title}
                        </Link>
                        <div className="text-muted small">
                          {t.track_code}
                          {t.position ? ` · Position ${t.position}` : ''}
                        </div>
                      </div>
                      {t.is_explicit && (
                        <span className="badge bg-danger-subtle text-danger-emphasis">E</span>
                      )}
                    </li>
                  ))}
                </ol>
              </div>
            </div>
          )}
        </div>

        <div className="col-lg-4">
          <div className="card border-0 shadow-sm mb-3">
            <div className="card-header bg-white"><strong>Cover Art</strong></div>
            <div className="card-body text-center">
              {release.cover_art_path ? (
                <img
                  src={release.cover_art_path}
                  alt={release.title}
                  className="img-fluid rounded"
                />
              ) : (
                <div
                  className="bg-light text-muted d-flex align-items-center justify-content-center rounded"
                  style={{ height: 260 }}
                >
                  <i className="bi bi-vinyl" style={{ fontSize: '4rem' }}></i>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      <ConfirmDeleteModal
        show={confirmDelete}
        title="Delete release?"
        message={`"${release.title}" (${release.release_code}) will be soft-deleted.`}
        loading={deleting}
        onCancel={() => setConfirmDelete(false)}
        onConfirm={doDelete}
      />
    </>
  );
}