import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canUpdateTrack, canDeleteTrack } from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const formatDuration = (seconds) => {
  if (!seconds) return '—';
  const m = Math.floor(seconds / 60);
  const s = String(seconds % 60).padStart(2, '0');
  return `${m}:${s}`;
};

const renderArray = (arr, keyPrefix) => {
  if (!Array.isArray(arr) || arr.length === 0) return '—';
  return (
    <ul className="list-unstyled mb-0">
      {arr.map((item, i) => (
        <li key={`${keyPrefix}-${i}`}>{item}</li>
      ))}
    </ul>
  );
};

export default function TrackViewPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [track, setTrack] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get(`/tracks/${id}`);
        setTrack(data.data);
      } catch (err) {
        if (err.response?.status === 401) return;
        if (err.response?.status === 404) setError('Track not found.');
        else setError('Could not load track.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id]);

  const doDelete = async () => {
    setDeleting(true);
    try {
      await api.delete(`/tracks/${id}`);
      navigate('/tracks');
    } catch {
      alert('Could not delete track.');
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

  if (error || !track) {
    return (
      <>
        <Link to="/tracks" className="text-muted text-decoration-none small">
          <i className="bi bi-arrow-left me-1"></i> Back to Catalogue
        </Link>
        <div className="alert alert-warning mt-3">{error || 'Track not found.'}</div>
      </>
    );
  }

  return (
    <>
      <div className="d-flex justify-content-between align-items-start mb-4">
        <div>
          <Link to="/tracks" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Catalogue
          </Link>
          <div className="d-flex align-items-center gap-3 mt-2">
            <h4 className="mb-0">{track.title}</h4>
            <span className="badge bg-light text-dark border">
              <code>{track.track_code}</code>
            </span>
            {track.is_explicit && (
              <span className="badge bg-danger-subtle text-danger-emphasis">Explicit</span>
            )}
          </div>
          {track.artist && (
            <p className="text-muted mb-0 small mt-1">
              Artist:{' '}
              <Link to={`/artists/${track.artist.id}`}>
                {track.artist.name}
              </Link>
            </p>
          )}
        </div>

        <div className="d-flex gap-2">
          {canUpdateTrack(roleSlug) && (
            <Link to={`/tracks/${track.id}/edit`} className="btn btn-outline-secondary">
              <i className="bi bi-pencil me-2"></i>
              Edit
            </Link>
          )}
          {canDeleteTrack(roleSlug) && (
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
                <dt className="col-sm-4 text-muted fw-normal">ISRC</dt>
                <dd className="col-sm-8">{track.isrc || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Genre</dt>
                <dd className="col-sm-8">{track.genre || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Language</dt>
                <dd className="col-sm-8 text-uppercase">{track.language || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Duration</dt>
                <dd className="col-sm-8">{formatDuration(track.duration_seconds)}</dd>

                <dt className="col-sm-4 text-muted fw-normal">BPM</dt>
                <dd className="col-sm-8">{track.bpm ?? '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Key</dt>
                <dd className="col-sm-8">{track.key || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Composer</dt>
                <dd className="col-sm-8">{track.composer || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Recorded</dt>
                <dd className="col-sm-8">{track.recorded_date || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Added by</dt>
                <dd className="col-sm-8">{track.created_by?.name || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Added on</dt>
                <dd className="col-sm-8">
                  {track.created_at ? new Date(track.created_at).toLocaleString() : '—'}
                </dd>
              </dl>
            </div>
          </div>

          <div className="card border-0 shadow-sm mb-3">
            <div className="card-header bg-white"><strong>Contributors</strong></div>
            <div className="card-body">
              <dl className="row mb-0">
                <dt className="col-sm-4 text-muted fw-normal">Writers</dt>
                <dd className="col-sm-8">{renderArray(track.writers, 'w')}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Producers</dt>
                <dd className="col-sm-8">{renderArray(track.producers, 'p')}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Featured Artists</dt>
                <dd className="col-sm-8">{renderArray(track.featured_artists, 'f')}</dd>
              </dl>
            </div>
          </div>

          {track.lyrics && (
            <div className="card border-0 shadow-sm">
              <div className="card-header bg-white"><strong>Lyrics</strong></div>
              <div className="card-body">
                <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{track.lyrics}</p>
              </div>
            </div>
          )}
        </div>

        <div className="col-lg-4">
          {track.audio_path && (
            <div className="card border-0 shadow-sm mb-3">
              <div className="card-header bg-white"><strong>Audio</strong></div>
              <div className="card-body">
                <a
                  href={track.audio_path}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-break small"
                >
                  {track.audio_path}
                </a>
              </div>
            </div>
          )}

          {track.notes && (
            <div className="card border-0 shadow-sm">
              <div className="card-header bg-white"><strong>Notes</strong></div>
              <div className="card-body">
                <p className="mb-0 small" style={{ whiteSpace: 'pre-wrap' }}>{track.notes}</p>
              </div>
            </div>
          )}
        </div>
      </div>

      <ConfirmDeleteModal
        show={confirmDelete}
        title="Delete track?"
        message={`"${track.title}" (${track.track_code}) will be soft-deleted.`}
        loading={deleting}
        onCancel={() => setConfirmDelete(false)}
        onConfirm={doDelete}
      />
    </>
  );
}