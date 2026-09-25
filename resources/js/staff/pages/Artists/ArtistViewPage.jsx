import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canUpdateArtist, canDeleteArtist } from '../../permissions';
import ConfirmDeleteModal from './ConfirmDeleteModal';

const SOCIAL_KEYS = ['spotify', 'apple_music', 'instagram', 'twitter', 'youtube', 'soundcloud', 'website'];

const SOCIAL_ICONS = {
  spotify: 'bi-spotify',
  apple_music: 'bi-apple',
  instagram: 'bi-instagram',
  twitter: 'bi-twitter-x',
  youtube: 'bi-youtube',
  soundcloud: 'bi-soundwave',
  website: 'bi-globe',
};

export default function ArtistViewPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [artist, setArtist] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get(`/artists/${id}`);
        setArtist(data.data);
      } catch (err) {
        if (err.response?.status === 401) return;
        if (err.response?.status === 404) {
          setError('Artist not found.');
        } else {
          setError('Could not load artist.');
        }
      } finally {
        setLoading(false);
      }
    })();
  }, [id]);

  const doDelete = async () => {
    setDeleting(true);
    try {
      await api.delete(`/artists/${id}`);
      navigate('/artists');
    } catch (err) {
      alert('Could not delete artist.');
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

  if (error || !artist) {
    return (
      <>
        <Link to="/artists" className="text-muted text-decoration-none small">
          <i className="bi bi-arrow-left me-1"></i> Back to Artists
        </Link>
        <div className="alert alert-warning mt-3">{error || 'Artist not found.'}</div>
      </>
    );
  }

  return (
    <>
      <div className="d-flex justify-content-between align-items-start mb-4">
        <div>
          <Link to="/artists" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Artists
          </Link>
          <div className="d-flex align-items-center gap-3 mt-2">
            <h4 className="mb-0">{artist.name}</h4>
            <span className="badge bg-light text-dark border">
              <code>{artist.artist_code}</code>
            </span>
          </div>
          {artist.real_name && (
            <p className="text-muted mb-0 small">{artist.real_name}</p>
          )}
        </div>

        <div className="d-flex gap-2">
          {canUpdateArtist(roleSlug) && (
            <Link to={`/artists/${artist.id}/edit`} className="btn btn-outline-secondary">
              <i className="bi bi-pencil me-2"></i>
              Edit
            </Link>
          )}
          {canDeleteArtist(roleSlug) && (
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
            <div className="card-header bg-white"><strong>Profile</strong></div>
            <div className="card-body">
              <dl className="row mb-0">
                <dt className="col-sm-3 text-muted fw-normal">Genre</dt>
                <dd className="col-sm-9">{artist.genre || '—'}</dd>

                <dt className="col-sm-3 text-muted fw-normal">Status</dt>
                <dd className="col-sm-9 text-capitalize">
                  {(artist.status || '').replace(/_/g, ' ')}
                </dd>

                <dt className="col-sm-3 text-muted fw-normal">Country</dt>
                <dd className="col-sm-9">{artist.country || '—'}</dd>

                <dt className="col-sm-3 text-muted fw-normal">City</dt>
                <dd className="col-sm-9">{artist.city || '—'}</dd>

                <dt className="col-sm-3 text-muted fw-normal">Email</dt>
                <dd className="col-sm-9">
                  {artist.email ? <a href={`mailto:${artist.email}`}>{artist.email}</a> : '—'}
                </dd>

                <dt className="col-sm-3 text-muted fw-normal">Phone</dt>
                <dd className="col-sm-9">{artist.phone || '—'}</dd>

                <dt className="col-sm-3 text-muted fw-normal">Manager</dt>
                <dd className="col-sm-9">{artist.manager?.name || '—'}</dd>

                <dt className="col-sm-3 text-muted fw-normal">Added by</dt>
                <dd className="col-sm-9">{artist.created_by?.name || '—'}</dd>

                <dt className="col-sm-3 text-muted fw-normal">Added on</dt>
                <dd className="col-sm-9">
                  {artist.created_at ? new Date(artist.created_at).toLocaleString() : '—'}
                </dd>
              </dl>
            </div>
          </div>

          {artist.bio && (
            <div className="card border-0 shadow-sm">
              <div className="card-header bg-white"><strong>Bio</strong></div>
              <div className="card-body">
                <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{artist.bio}</p>
              </div>
            </div>
          )}
        </div>

        <div className="col-lg-4">
          <div className="card border-0 shadow-sm mb-3">
            <div className="card-header bg-white"><strong>Avatar</strong></div>
            <div className="card-body text-center">
              {artist.avatar ? (
                <img
                  src={artist.avatar}
                  alt={artist.name}
                  className="img-fluid rounded"
                  style={{ maxHeight: 320 }}
                />
              ) : (
                <div
                  className="bg-light text-muted d-flex align-items-center justify-content-center rounded"
                  style={{ height: 240 }}
                >
                  <i className="bi bi-person" style={{ fontSize: '4rem' }}></i>
                </div>
              )}
            </div>
          </div>

          {artist.social_links && Object.keys(artist.social_links).length > 0 && (
            <div className="card border-0 shadow-sm">
              <div className="card-header bg-white"><strong>Social Links</strong></div>
              <div className="card-body">
                <div className="d-flex flex-column gap-2">
                  {SOCIAL_KEYS.map((key) => {
                    const url = artist.social_links?.[key];
                    if (!url) return null;

                    return (
                      <a
                        key={key}
                        href={url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn btn-outline-secondary btn-sm text-start text-capitalize"
                      >
                        <i className={`bi ${SOCIAL_ICONS[key]} me-2`}></i>
                        {key.replace(/_/g, ' ')}
                        <i className="bi bi-box-arrow-up-right ms-2 small opacity-50"></i>
                      </a>
                    );
                  })}
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      <ConfirmDeleteModal
        show={confirmDelete}
        title="Delete artist?"
        message={`"${artist.name}" will be soft-deleted. You can restore it later from the database.`}
        loading={deleting}
        onCancel={() => setConfirmDelete(false)}
        onConfirm={doDelete}
      />
    </>
  );
}