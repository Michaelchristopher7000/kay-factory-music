import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canCreateDistribution, canUpdateDistribution, canDeleteDistribution } from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const STATUSES = [
  { value: '', label: 'All statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'submitted', label: 'Submitted' },
  { value: 'live', label: 'Live' },
  { value: 'takedown', label: 'Takedown' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'failed', label: 'Failed' },
];

const PLATFORMS = [
  { value: '', label: 'All platforms' },
  { value: 'spotify', label: 'Spotify' },
  { value: 'apple_music', label: 'Apple Music' },
  { value: 'youtube_music', label: 'YouTube Music' },
  { value: 'amazon_music', label: 'Amazon Music' },
  { value: 'deezer', label: 'Deezer' },
  { value: 'tidal', label: 'Tidal' },
  { value: 'audiomack', label: 'Audiomack' },
  { value: 'boomplay', label: 'Boomplay' },
  { value: 'soundcloud', label: 'SoundCloud' },
  { value: 'pandora', label: 'Pandora' },
  { value: 'other', label: 'Other' },
];

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

const statusLabel = (s) => (s || '').replace(/_/g, ' ');
const platformLabel = (p) => (p || '').replace(/_/g, ' ');

export default function DistributionsListPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [distributions, setDistributions] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState('');
  const [platform, setPlatform] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchDistributions = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/distributions', {
        params: {
          search: search || undefined,
          platform: platform || undefined,
          status: status || undefined,
          per_page: 15,
          page,
        },
      });

      setDistributions(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load distributions. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [search, platform, status, page]);

  useEffect(() => {
    const t = setTimeout(fetchDistributions, 250);
    return () => clearTimeout(t);
  }, [fetchDistributions]);

  useEffect(() => { setPage(1); }, [search, platform, status]);

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);

    try {
      await api.delete(`/distributions/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchDistributions();
    } catch (err) {
      alert('Could not delete distribution. ' + (err.response?.data?.message || ''));
    } finally {
      setDeleting(false);
    }
  };

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h4 className="mb-1">Distribution</h4>
          <p className="text-muted mb-0 small">
            Track platform deliveries — {meta.total || 0} total
          </p>
        </div>

        {canCreateDistribution(roleSlug) && (
          <Link to="/distributions/new" className="btn btn-dark">
            <i className="bi bi-plus-lg me-2"></i>
            Add Distribution
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
                  placeholder="Search by code, distributor, or platform ID"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>
            <div className="col-6 col-md-3">
              <select
                className="form-select"
                value={platform}
                onChange={(e) => setPlatform(e.target.value)}
              >
                {PLATFORMS.map((p) => (
                  <option key={p.value} value={p.value}>{p.label}</option>
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
                onClick={fetchDistributions}
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
                <th>Release</th>
                <th style={{ width: 150 }}>Platform</th>
                <th style={{ width: 120 }}>Status</th>
                <th style={{ width: 140 }}>Distributor</th>
                <th style={{ width: 120 }}>Live</th>
                <th style={{ width: 160 }} className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    <div className="spinner-border spinner-border-sm me-2"></div>
                    Loading distributions…
                  </td>
                </tr>
              )}

              {!loading && error && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-danger">{error}</td>
                </tr>
              )}

              {!loading && !error && distributions.length === 0 && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    No distributions found.
                    {(search || platform || status) && <> Try clearing the filters.</>}
                  </td>
                </tr>
              )}

              {!loading && distributions.map((d) => (
                <tr key={d.id}>
                  <td><code className="text-dark">{d.distribution_code}</code></td>
                  <td>
                    {d.release ? (
                      <Link to={`/releases/${d.release.id}`} className="text-decoration-none fw-semibold text-dark">
                        {d.release.title}
                      </Link>
                    ) : (
                      <span className="text-muted">—</span>
                    )}
                    {d.release?.release_code && (
                      <div className="text-muted small">{d.release.release_code}</div>
                    )}
                  </td>
                  <td>
                    <i className={`bi ${PLATFORM_ICONS[d.platform] || 'bi-globe'} me-2`}></i>
                    <span className="text-capitalize">{platformLabel(d.platform)}</span>
                  </td>
                  <td>
                    <span className={statusBadge(d.status)}>
                      {statusLabel(d.status)}
                    </span>
                  </td>
                  <td className="text-muted small">{d.distributor || '—'}</td>
                  <td className="text-muted small">{d.live_at || '—'}</td>
                  <td className="text-end">
                    <Link
                      to={`/distributions/${d.id}`}
                      className="btn btn-sm btn-outline-secondary me-1"
                      title="View"
                    >
                      <i className="bi bi-eye"></i>
                    </Link>

                    {canUpdateDistribution(roleSlug) && (
                      <Link
                        to={`/distributions/${d.id}/edit`}
                        className="btn btn-sm btn-outline-secondary me-1"
                        title="Edit"
                      >
                        <i className="bi bi-pencil"></i>
                      </Link>
                    )}

                    {canDeleteDistribution(roleSlug) && (
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger"
                        title="Delete"
                        onClick={() => setDeleteTarget(d)}
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
              Loading distributions…
            </div>
          </div>
        )}

        {!loading && error && <div className="alert alert-danger">{error}</div>}

        {!loading && !error && distributions.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No distributions found.
              {(search || platform || status) && <> Try clearing the filters.</>}
            </div>
          </div>
        )}

        {!loading && distributions.map((d) => (
          <Link
            key={d.id}
            to={`/distributions/${d.id}`}
            className="kfm-mobile-card"
          >
            <div className="kfm-mobile-card__head">
              <code className="kfm-mobile-card__ref">{d.distribution_code}</code>
              <span className={statusBadge(d.status)}>
                {statusLabel(d.status)}
              </span>
            </div>

            <div className="kfm-mobile-card__subject">
              {d.release?.title || 'No release'}
            </div>

            <div className="kfm-mobile-card__meta">
              {d.platform && (
                <div className="kfm-mobile-card__meta-row">
                  <i className={`bi ${PLATFORM_ICONS[d.platform] || 'bi-globe'}`}></i>
                  <span className="text-capitalize">{platformLabel(d.platform)}</span>
                </div>
              )}
              {d.release?.release_code && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-upc"></i>
                  <span>{d.release.release_code}</span>
                </div>
              )}
              {d.distributor && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-truck"></i>
                  <span className="text-truncate">{d.distributor}</span>
                </div>
              )}
              {d.live_at && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-calendar3"></i>
                  <span>{d.live_at}</span>
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
        title="Delete distribution?"
        message={
          deleteTarget
            ? `"${deleteTarget.distribution_code}" (${deleteTarget.platform}) will be soft-deleted.`
            : ''
        }
        loading={deleting}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </>
  );
}