import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canCreateTrack, canUpdateTrack, canDeleteTrack } from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const formatDuration = (seconds) => {
  if (!seconds) return '—';
  const m = Math.floor(seconds / 60);
  const s = String(seconds % 60).padStart(2, '0');
  return `${m}:${s}`;
};

export default function TracksListPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [tracks, setTracks] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState('');
  const [genre, setGenre] = useState('');
  const [explicit, setExplicit] = useState('');
  const [page, setPage] = useState(1);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchTracks = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/tracks', {
        params: {
          search: search || undefined,
          genre: genre || undefined,
          is_explicit: explicit === '' ? undefined : explicit,
          per_page: 15,
          page,
        },
      });

      setTracks(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load tracks. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [search, genre, explicit, page]);

  useEffect(() => {
    const t = setTimeout(fetchTracks, 250);
    return () => clearTimeout(t);
  }, [fetchTracks]);

  useEffect(() => { setPage(1); }, [search, genre, explicit]);

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);

    try {
      await api.delete(`/tracks/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchTracks();
    } catch (err) {
      alert('Could not delete track. ' + (err.response?.data?.message || ''));
    } finally {
      setDeleting(false);
    }
  };

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h4 className="mb-1">Catalogue</h4>
          <p className="text-muted mb-0 small">
            Manage tracks — {meta.total || 0} total
          </p>
        </div>

        {canCreateTrack(roleSlug) && (
          <Link to="/tracks/new" className="btn btn-dark">
            <i className="bi bi-plus-lg me-2"></i>
            Add Track
          </Link>
        )}
      </div>

      <div className="card border-0 shadow-sm mb-3">
        <div className="card-body">
          <div className="row g-2">
            <div className="col-12 col-md-5">
              <div className="input-group">
                <span className="input-group-text bg-white">
                  <i className="bi bi-search"></i>
                </span>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Search by title, code, or ISRC"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>
            <div className="col-6 col-md-3">
              <input
                type="text"
                className="form-control"
                placeholder="Genre"
                value={genre}
                onChange={(e) => setGenre(e.target.value)}
              />
            </div>
            <div className="col-6 col-md-3">
              <select
                className="form-select"
                value={explicit}
                onChange={(e) => setExplicit(e.target.value)}
              >
                <option value="">All</option>
                <option value="true">Explicit only</option>
                <option value="false">Clean only</option>
              </select>
            </div>
            <div className="col-12 col-md-1 d-flex">
              <button
                type="button"
                className="btn btn-outline-secondary w-100"
                title="Refresh"
                onClick={fetchTracks}
              >
                <i className="bi bi-arrow-clockwise"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* ---------- DESKTOP TABLE ---------- */}
      <div className="card border-0 shadow-sm kfm-desktop-only">
        <div className="table-responsive">
          <table className="table table-hover align-middle mb-0">
            <thead className="table-light">
              <tr>
                <th style={{ width: 130 }}>Code</th>
                <th>Title</th>
                <th>Artist</th>
                <th style={{ width: 120 }}>Genre</th>
                <th style={{ width: 90 }}>Duration</th>
                <th style={{ width: 80 }}>Explicit</th>
                <th style={{ width: 160 }} className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    <div className="spinner-border spinner-border-sm me-2"></div>
                    Loading tracks…
                  </td>
                </tr>
              )}

              {!loading && error && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-danger">{error}</td>
                </tr>
              )}

              {!loading && !error && tracks.length === 0 && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    No tracks found.
                    {(search || genre || explicit !== '') && <> Try clearing the filters.</>}
                  </td>
                </tr>
              )}

              {!loading && tracks.map((t) => (
                <tr key={t.id}>
                  <td><code className="text-dark">{t.track_code}</code></td>
                  <td>
                    <Link to={`/tracks/${t.id}`} className="text-decoration-none fw-semibold text-dark">
                      {t.title}
                    </Link>
                    {t.isrc && (
                      <div className="text-muted small">ISRC: {t.isrc}</div>
                    )}
                  </td>
                  <td className="text-muted small">
                    {t.artist?.name || '—'}
                  </td>
                  <td className="text-muted small">{t.genre || '—'}</td>
                  <td className="text-muted small">
                    {formatDuration(t.duration_seconds)}
                  </td>
                  <td className="small">
                    {t.is_explicit ? (
                      <span className="badge bg-danger-subtle text-danger-emphasis">E</span>
                    ) : (
                      <span className="badge bg-secondary-subtle text-secondary-emphasis">Clean</span>
                    )}
                  </td>
                  <td className="text-end">
                    <Link
                      to={`/tracks/${t.id}`}
                      className="btn btn-sm btn-outline-secondary me-1"
                      title="View"
                    >
                      <i className="bi bi-eye"></i>
                    </Link>

                    {canUpdateTrack(roleSlug) && (
                      <Link
                        to={`/tracks/${t.id}/edit`}
                        className="btn btn-sm btn-outline-secondary me-1"
                        title="Edit"
                      >
                        <i className="bi bi-pencil"></i>
                      </Link>
                    )}

                    {canDeleteTrack(roleSlug) && (
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger"
                        title="Delete"
                        onClick={() => setDeleteTarget(t)}
                      >
                        <i className="bi bi-trash"></i>
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {!loading && meta.last_page > 1 && (
          <div className="card-footer bg-white d-flex justify-content-between align-items-center">
            <div className="text-muted small">
              Page {meta.current_page} of {meta.last_page}
            </div>
            <div>
              <button
                type="button"
                className="btn btn-sm btn-outline-secondary me-1"
                disabled={meta.current_page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                <i className="bi bi-chevron-left"></i> Prev
              </button>
              <button
                type="button"
                className="btn btn-sm btn-outline-secondary"
                disabled={meta.current_page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
              >
                Next <i className="bi bi-chevron-right"></i>
              </button>
            </div>
          </div>
        )}
      </div>

      {/* ---------- MOBILE CARDS ---------- */}
      <div className="kfm-mobile-only">
        {loading && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              <div className="spinner-border spinner-border-sm me-2"></div>
              Loading tracks…
            </div>
          </div>
        )}

        {!loading && error && <div className="alert alert-danger">{error}</div>}

        {!loading && !error && tracks.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No tracks found.
              {(search || genre || explicit !== '') && <> Try clearing the filters.</>}
            </div>
          </div>
        )}

        {!loading && tracks.map((t) => (
          <Link
            key={t.id}
            to={`/tracks/${t.id}`}
            className="kfm-mobile-card"
          >
            <div className="kfm-mobile-card__head">
              <code className="kfm-mobile-card__ref">{t.track_code}</code>
              {t.is_explicit ? (
                <span className="badge bg-danger-subtle text-danger-emphasis">E</span>
              ) : (
                <span className="badge bg-secondary-subtle text-secondary-emphasis">Clean</span>
              )}
            </div>

            <div className="kfm-mobile-card__subject">{t.title}</div>

            <div className="kfm-mobile-card__meta">
              {t.artist?.name && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-person"></i>
                  <span>{t.artist.name}</span>
                </div>
              )}
              {t.genre && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-music-note"></i>
                  <span>{t.genre}</span>
                </div>
              )}
              {t.duration_seconds && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-clock"></i>
                  <span>{formatDuration(t.duration_seconds)}</span>
                </div>
              )}
              {t.isrc && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-upc"></i>
                  <span className="text-truncate">ISRC: {t.isrc}</span>
                </div>
              )}
            </div>
          </Link>
        ))}

        {!loading && meta.last_page > 1 && (
          <div className="d-flex justify-content-between align-items-center mt-3">
            <div className="text-muted small">
              Page {meta.current_page} of {meta.last_page}
            </div>
            <div>
              <button
                type="button"
                className="btn btn-sm btn-outline-secondary me-1"
                disabled={meta.current_page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                <i className="bi bi-chevron-left"></i> Prev
              </button>
              <button
                type="button"
                className="btn btn-sm btn-outline-secondary"
                disabled={meta.current_page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
              >
                Next <i className="bi bi-chevron-right"></i>
              </button>
            </div>
          </div>
        )}
      </div>

      <ConfirmDeleteModal
        show={!!deleteTarget}
        title="Delete track?"
        message={
          deleteTarget
            ? `"${deleteTarget.title}" (${deleteTarget.track_code}) will be soft-deleted.`
            : ''
        }
        loading={deleting}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </>
  );
}