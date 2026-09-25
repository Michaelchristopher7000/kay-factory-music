import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canCreateArtist, canUpdateArtist, canDeleteArtist } from '../../permissions';
import ConfirmDeleteModal from './ConfirmDeleteModal';

const STATUSES = [
  { value: '', label: 'All statuses' },
  { value: 'in_talks', label: 'In Talks' },
  { value: 'signed', label: 'Signed' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'former', label: 'Former' },
];

const statusBadge = (status) => {
  switch (status) {
    case 'signed':   return 'badge bg-success-subtle text-success-emphasis';
    case 'in_talks': return 'badge bg-warning-subtle text-warning-emphasis';
    case 'inactive': return 'badge bg-secondary-subtle text-secondary-emphasis';
    case 'former':   return 'badge bg-dark-subtle text-dark-emphasis';
    default:         return 'badge bg-light text-dark';
  }
};

const statusLabel = (status) => (status || '').replace(/_/g, ' ');

export default function ArtistsListPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [artists, setArtists] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [genre, setGenre] = useState('');
  const [page, setPage] = useState(1);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchArtists = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/artists', {
        params: {
          search: search || undefined,
          status: status || undefined,
          genre: genre || undefined,
          per_page: 15,
          page,
        },
      });

      setArtists(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load artists. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [search, status, genre, page]);

  useEffect(() => {
    const t = setTimeout(fetchArtists, 250);
    return () => clearTimeout(t);
  }, [fetchArtists]);

  useEffect(() => { setPage(1); }, [search, status, genre]);

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);

    try {
      await api.delete(`/artists/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchArtists();
    } catch (err) {
      alert('Could not delete artist. ' + (err.response?.data?.message || ''));
    } finally {
      setDeleting(false);
    }
  };

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h4 className="mb-1">Artists</h4>
          <p className="text-muted mb-0 small">
            Manage the label roster — {meta.total || 0} total
          </p>
        </div>

        {canCreateArtist(roleSlug) && (
          <Link to="/artists/new" className="btn btn-dark">
            <i className="bi bi-plus-lg me-2"></i>
            Add Artist
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
                  placeholder="Search by name, code, or email"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>
            <div className="col-6 col-md-3">
              <select
                className="form-select"
                value={status}
                onChange={(e) => setStatus(e.target.value)}
              >
                {STATUSES.map((s) => (
                  <option key={s.value} value={s.value}>{s.label}</option>
                ))}
              </select>
            </div>
            <div className="col-6 col-md-3">
              <input
                type="text"
                className="form-control"
                placeholder="Filter by genre"
                value={genre}
                onChange={(e) => setGenre(e.target.value)}
              />
            </div>
            <div className="col-12 col-md-1 d-flex">
              <button
                type="button"
                className="btn btn-outline-secondary w-100"
                title="Refresh"
                onClick={fetchArtists}
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
                <th>Name</th>
                <th>Genre</th>
                <th style={{ width: 120 }}>Status</th>
                <th>Manager</th>
                <th style={{ width: 140 }}>Created</th>
                <th style={{ width: 160 }} className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    <div className="spinner-border spinner-border-sm me-2"></div>
                    Loading artists…
                  </td>
                </tr>
              )}

              {!loading && error && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-danger">
                    {error}
                  </td>
                </tr>
              )}

              {!loading && !error && artists.length === 0 && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    No artists found.
                    {(search || status || genre) && (
                      <> Try clearing the filters.</>
                    )}
                  </td>
                </tr>
              )}

              {!loading && artists.map((a) => (
                <tr key={a.id}>
                  <td><code className="text-dark">{a.artist_code}</code></td>
                  <td>
                    <Link to={`/artists/${a.id}`} className="text-decoration-none fw-semibold text-dark">
                      {a.name}
                    </Link>
                    {a.real_name && (
                      <div className="text-muted small">{a.real_name}</div>
                    )}
                  </td>
                  <td className="text-muted">{a.genre || '—'}</td>
                  <td>
                    <span className={statusBadge(a.status)}>
                      {statusLabel(a.status)}
                    </span>
                  </td>
                  <td className="text-muted small">
                    {a.manager?.name || '—'}
                  </td>
                  <td className="text-muted small">
                    {a.created_at ? new Date(a.created_at).toLocaleDateString() : '—'}
                  </td>
                  <td className="text-end">
                    <Link
                      to={`/artists/${a.id}`}
                      className="btn btn-sm btn-outline-secondary me-1"
                      title="View"
                    >
                      <i className="bi bi-eye"></i>
                    </Link>

                    {canUpdateArtist(roleSlug) && (
                      <Link
                        to={`/artists/${a.id}/edit`}
                        className="btn btn-sm btn-outline-secondary me-1"
                        title="Edit"
                      >
                        <i className="bi bi-pencil"></i>
                      </Link>
                    )}

                    {canDeleteArtist(roleSlug) && (
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger"
                        title="Delete"
                        onClick={() => setDeleteTarget(a)}
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
              Loading artists…
            </div>
          </div>
        )}

        {!loading && error && <div className="alert alert-danger">{error}</div>}

        {!loading && !error && artists.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No artists found.
              {(search || status || genre) && <> Try clearing the filters.</>}
            </div>
          </div>
        )}

        {!loading && artists.map((a) => (
          <Link
            key={a.id}
            to={`/artists/${a.id}`}
            className="kfm-mobile-card"
          >
            <div className="kfm-mobile-card__head">
              <code className="kfm-mobile-card__ref">{a.artist_code}</code>
              <span className={statusBadge(a.status)}>
                {statusLabel(a.status)}
              </span>
            </div>

            <div className="kfm-mobile-card__subject">
              {a.name}
              {a.real_name && (
                <div className="text-muted small fw-normal" style={{ marginTop: 2 }}>
                  {a.real_name}
                </div>
              )}
            </div>

            <div className="kfm-mobile-card__meta">
              {a.genre && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-music-note"></i>
                  <span>{a.genre}</span>
                </div>
              )}
              {a.manager?.name && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-person-badge"></i>
                  <span>{a.manager.name}</span>
                </div>
              )}
              <div className="kfm-mobile-card__meta-row">
                <i className="bi bi-calendar3"></i>
                <span>{a.created_at ? new Date(a.created_at).toLocaleDateString() : '—'}</span>
              </div>
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
        title="Delete artist?"
        message={
          deleteTarget
            ? `"${deleteTarget.name}" will be soft-deleted. You can restore it later from the database.`
            : ''
        }
        loading={deleting}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </>
  );
}