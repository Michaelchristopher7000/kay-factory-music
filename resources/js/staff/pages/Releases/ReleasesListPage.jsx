import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canCreateRelease, canUpdateRelease, canDeleteRelease } from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const TYPES = [
  { value: '', label: 'All types' },
  { value: 'single', label: 'Single' },
  { value: 'ep', label: 'EP' },
  { value: 'album', label: 'Album' },
  { value: 'mixtape', label: 'Mixtape' },
  { value: 'compilation', label: 'Compilation' },
  { value: 'other', label: 'Other' },
];

const STATUSES = [
  { value: '', label: 'All statuses' },
  { value: 'draft', label: 'Draft' },
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'released', label: 'Released' },
  { value: 'archived', label: 'Archived' },
  { value: 'cancelled', label: 'Cancelled' },
];

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

const statusLabel = (s) => (s || '').replace(/_/g, ' ');
const typeLabel = (t) => (t || '').toUpperCase();

export default function ReleasesListPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [releases, setReleases] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState('');
  const [type, setType] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchReleases = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/releases', {
        params: {
          search: search || undefined,
          type: type || undefined,
          status: status || undefined,
          per_page: 15,
          page,
        },
      });

      setReleases(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load releases. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [search, type, status, page]);

  useEffect(() => {
    const t = setTimeout(fetchReleases, 250);
    return () => clearTimeout(t);
  }, [fetchReleases]);

  useEffect(() => { setPage(1); }, [search, type, status]);

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);

    try {
      await api.delete(`/releases/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchReleases();
    } catch (err) {
      alert('Could not delete release. ' + (err.response?.data?.message || ''));
    } finally {
      setDeleting(false);
    }
  };

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h4 className="mb-1">Releases</h4>
          <p className="text-muted mb-0 small">
            Manage releases — {meta.total || 0} total
          </p>
        </div>

        {canCreateRelease(roleSlug) && (
          <Link to="/releases/new" className="btn btn-dark">
            <i className="bi bi-plus-lg me-2"></i>
            Add Release
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
                  placeholder="Search by title, code, or UPC"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>
            <div className="col-6 col-md-3">
              <select
                className="form-select"
                value={type}
                onChange={(e) => setType(e.target.value)}
              >
                {TYPES.map((t) => (
                  <option key={t.value} value={t.value}>{t.label}</option>
                ))}
              </select>
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
            <div className="col-12 col-md-1 d-flex">
              <button
                type="button"
                className="btn btn-outline-secondary w-100"
                title="Refresh"
                onClick={fetchReleases}
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
                <th style={{ width: 100 }}>Type</th>
                <th style={{ width: 120 }}>Status</th>
                <th style={{ width: 120 }}>Release Date</th>
                <th style={{ width: 160 }} className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    <div className="spinner-border spinner-border-sm me-2"></div>
                    Loading releases…
                  </td>
                </tr>
              )}

              {!loading && error && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-danger">{error}</td>
                </tr>
              )}

              {!loading && !error && releases.length === 0 && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    No releases found.
                    {(search || type || status) && <> Try clearing the filters.</>}
                  </td>
                </tr>
              )}

              {!loading && releases.map((r) => (
                <tr key={r.id}>
                  <td><code className="text-dark">{r.release_code}</code></td>
                  <td>
                    <Link to={`/releases/${r.id}`} className="text-decoration-none fw-semibold text-dark">
                      {r.title}
                    </Link>
                    {r.upc && (
                      <div className="text-muted small">UPC: {r.upc}</div>
                    )}
                  </td>
                  <td className="text-muted small">
                    {r.artist?.name || '—'}
                  </td>
                  <td>
                    <span className="badge bg-light text-dark border">
                      {typeLabel(r.type)}
                    </span>
                  </td>
                  <td>
                    <span className={statusBadge(r.status)}>
                      {statusLabel(r.status)}
                    </span>
                  </td>
                  <td className="text-muted small">
                    {r.release_date || '—'}
                  </td>
                  <td className="text-end">
                    <Link
                      to={`/releases/${r.id}`}
                      className="btn btn-sm btn-outline-secondary me-1"
                      title="View"
                    >
                      <i className="bi bi-eye"></i>
                    </Link>

                    {canUpdateRelease(roleSlug) && (
                      <Link
                        to={`/releases/${r.id}/edit`}
                        className="btn btn-sm btn-outline-secondary me-1"
                        title="Edit"
                      >
                        <i className="bi bi-pencil"></i>
                      </Link>
                    )}

                    {canDeleteRelease(roleSlug) && (
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger"
                        title="Delete"
                        onClick={() => setDeleteTarget(r)}
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
              Loading releases…
            </div>
          </div>
        )}

        {!loading && error && <div className="alert alert-danger">{error}</div>}

        {!loading && !error && releases.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No releases found.
              {(search || type || status) && <> Try clearing the filters.</>}
            </div>
          </div>
        )}

        {!loading && releases.map((r) => (
          <Link
            key={r.id}
            to={`/releases/${r.id}`}
            className="kfm-mobile-card"
          >
            <div className="kfm-mobile-card__head">
              <code className="kfm-mobile-card__ref">{r.release_code}</code>
              <span className={statusBadge(r.status)}>
                {statusLabel(r.status)}
              </span>
            </div>

            <div className="kfm-mobile-card__subject">{r.title}</div>

            <div className="kfm-mobile-card__meta">
              {r.artist?.name && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-person"></i>
                  <span>{r.artist.name}</span>
                </div>
              )}
              {r.type && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-tag"></i>
                  <span>{typeLabel(r.type)}</span>
                </div>
              )}
              {r.upc && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-upc"></i>
                  <span className="text-truncate">UPC: {r.upc}</span>
                </div>
              )}
              <div className="kfm-mobile-card__meta-row">
                <i className="bi bi-calendar3"></i>
                <span>{r.release_date || '—'}</span>
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
        title="Delete release?"
        message={
          deleteTarget
            ? `"${deleteTarget.title}" (${deleteTarget.release_code}) will be soft-deleted.`
            : ''
        }
        loading={deleting}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </>
  );
}