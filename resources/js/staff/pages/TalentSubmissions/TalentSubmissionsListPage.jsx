import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';

const STATUS_STYLES = {
  pending:     { bg: 'bg-secondary-subtle', text: 'text-secondary-emphasis', label: 'Pending' },
  reviewing:   { bg: 'bg-info-subtle',      text: 'text-info-emphasis',      label: 'Reviewing' },
  shortlisted: { bg: 'bg-warning-subtle',   text: 'text-warning-emphasis',   label: 'Shortlisted' },
  accepted:    { bg: 'bg-success-subtle',   text: 'text-success-emphasis',   label: 'Accepted' },
  rejected:    { bg: 'bg-danger-subtle',    text: 'text-danger-emphasis',    label: 'Rejected' },
};

const formatDate = (d) => {
  if (!d) return '—';
  try {
    return new Date(d).toLocaleDateString(undefined, {
      year: 'numeric', month: 'short', day: 'numeric',
    });
  } catch {
    return '—';
  }
};

export default function TalentSubmissionsListPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 20 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);

  const fetchItems = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/talent-submissions', {
        params: {
          search: search || undefined,
          status: status || undefined,
          per_page: 20,
          page,
        },
      });

      setItems(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      if (err.response?.status === 403) {
        setError('You do not have access to talent submissions.');
      } else {
        setError('Could not load submissions. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  }, [search, status, page]);

  useEffect(() => {
    const t = setTimeout(fetchItems, 250);
    return () => clearTimeout(t);
  }, [fetchItems]);

  useEffect(() => { setPage(1); }, [search, status]);

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h4 className="mb-1">Talent Submissions</h4>
          <p className="text-muted mb-0 small">
            Review incoming talent demos — {meta.total || 0} total
          </p>
        </div>
      </div>

      <div className="card border-0 shadow-sm mb-3">
        <div className="card-body">
          <div className="row g-2">
            <div className="col-12 col-md-6">
              <div className="input-group">
                <span className="input-group-text bg-white">
                  <i className="bi bi-search"></i>
                </span>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Search by name, email, or reference"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>
            <div className="col-8 col-md-4">
              <select
                className="form-select"
                value={status}
                onChange={(e) => setStatus(e.target.value)}
              >
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="reviewing">Reviewing</option>
                <option value="shortlisted">Shortlisted</option>
                <option value="accepted">Accepted</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
            <div className="col-4 col-md-2 d-flex">
              <button
                type="button"
                className="btn btn-outline-secondary w-100"
                title="Refresh"
                onClick={fetchItems}
              >
                <i className="bi bi-arrow-clockwise"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* ---------- DESKTOP TABLE (hidden on mobile) ---------- */}
      <div className="card border-0 shadow-sm kfm-desktop-only">
        <div className="table-responsive">
          <table className="table table-hover align-middle mb-0">
            <thead className="table-light">
              <tr>
                <th style={{ width: 140 }}>Reference</th>
                <th>Name</th>
                <th style={{ width: 160 }}>Category</th>
                <th style={{ width: 220 }}>Email</th>
                <th style={{ width: 130 }}>Submitted</th>
                <th style={{ width: 130 }}>Status</th>
                <th style={{ width: 100 }} className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    <div className="spinner-border spinner-border-sm me-2"></div>
                    Loading submissions…
                  </td>
                </tr>
              )}

              {!loading && error && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-danger">{error}</td>
                </tr>
              )}

              {!loading && !error && items.length === 0 && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    No submissions found.
                    {(search || status) && <> Try clearing the filters.</>}
                  </td>
                </tr>
              )}

              {!loading && items.map((t) => {
                const style = STATUS_STYLES[t.status] || STATUS_STYLES.pending;
                return (
                  <tr key={t.id}>
                    <td><code className="text-dark">{t.reference_number}</code></td>
                    <td className="fw-semibold text-dark">{t.full_name}</td>
                    <td className="small">{t.talent_category}</td>
                    <td className="small text-muted">{t.email}</td>
                    <td className="small text-muted">{formatDate(t.created_at)}</td>
                    <td>
                      <span className={`badge ${style.bg} ${style.text}`}>
                        {style.label}
                      </span>
                    </td>
                    <td className="text-end">
                      <Link
                        to={`/talent-submissions/${t.id}`}
                        className="btn btn-sm btn-outline-secondary"
                        title="View"
                      >
                        <i className="bi bi-eye"></i>
                      </Link>
                    </td>
                  </tr>
                );
              })}
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

      {/* ---------- MOBILE CARDS (hidden on desktop) ---------- */}
      <div className="kfm-mobile-only">
        {loading && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              <div className="spinner-border spinner-border-sm me-2"></div>
              Loading submissions…
            </div>
          </div>
        )}

        {!loading && error && (
          <div className="alert alert-danger">{error}</div>
        )}

        {!loading && !error && items.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No submissions found.
              {(search || status) && <> Try clearing the filters.</>}
            </div>
          </div>
        )}

        {!loading && items.map((t) => {
          const style = STATUS_STYLES[t.status] || STATUS_STYLES.pending;
          return (
            <Link
              key={t.id}
              to={`/talent-submissions/${t.id}`}
              className={`kfm-mobile-card ${t.status === 'pending' ? 'is-unread' : ''}`}
            >
              <div className="kfm-mobile-card__head">
                <code className="kfm-mobile-card__ref">{t.reference_number}</code>
                <span className={`badge ${style.bg} ${style.text}`}>{style.label}</span>
              </div>

              <div className="kfm-mobile-card__subject">{t.full_name}</div>

              <div className="kfm-mobile-card__meta">
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-tag"></i>
                  <span>{t.talent_category}</span>
                </div>
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-envelope"></i>
                  <span className="text-truncate">{t.email}</span>
                </div>
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-calendar3"></i>
                  <span>{formatDate(t.created_at)}</span>
                </div>
              </div>
            </Link>
          );
        })}

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
    </>
  );
}