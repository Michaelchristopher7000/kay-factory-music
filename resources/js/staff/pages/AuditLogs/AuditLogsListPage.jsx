import { useEffect, useState, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canViewAuditLogs } from '../../permissions';
import AuditLogViewModal from './AuditLogViewModal';

const ACTIONS = [
  { value: '', label: 'All actions' },
  { value: 'created', label: 'Created' },
  { value: 'updated', label: 'Updated' },
  { value: 'deleted', label: 'Deleted' },
  { value: 'restored', label: 'Restored' },
];

const MODEL_TYPES = [
  { value: '', label: 'All models' },
  { value: 'Artist', label: 'Artist' },
  { value: 'Contract', label: 'Contract' },
  { value: 'Track', label: 'Track' },
  { value: 'Release', label: 'Release' },
  { value: 'Distribution', label: 'Distribution' },
  { value: 'RevenueEntry', label: 'Revenue Entry' },
  { value: 'Expense', label: 'Expense' },
  { value: 'RoyaltyStatement', label: 'Royalty Statement' },
  { value: 'RoyaltyStatementLine', label: 'Royalty Statement Line' },
  { value: 'RoyaltyPayment', label: 'Royalty Payment' },
];

const actionBadge = (action) => {
  switch (action) {
    case 'created':  return 'badge bg-success-subtle text-success-emphasis';
    case 'updated':  return 'badge bg-primary-subtle text-primary-emphasis';
    case 'deleted':  return 'badge bg-danger-subtle text-danger-emphasis';
    case 'restored': return 'badge bg-warning-subtle text-warning-emphasis';
    default:         return 'badge bg-light text-dark';
  }
};

const actionIcon = (action) => {
  switch (action) {
    case 'created':  return 'bi-plus-circle';
    case 'updated':  return 'bi-pencil-square';
    case 'deleted':  return 'bi-trash';
    case 'restored': return 'bi-arrow-counterclockwise';
    default:         return 'bi-activity';
  }
};

export default function AuditLogsListPage() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';
  const canView = canViewAuditLogs(roleSlug);

  const [logs, setLogs] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState('');
  const [action, setAction] = useState('');
  const [modelType, setModelType] = useState('');
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [page, setPage] = useState(1);

  const [viewing, setViewing] = useState(null);

  useEffect(() => {
    if (!canView) {
      navigate('/', { replace: true });
    }
  }, [canView, navigate]);

  const fetchLogs = useCallback(async () => {
    if (!canView) return;

    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/audit-logs', {
        params: {
          search: search || undefined,
          action: action || undefined,
          model_type: modelType || undefined,
          from: from || undefined,
          to: to || undefined,
          per_page: 25,
          page,
        },
      });

      setLogs(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      if (err.response?.status === 403) {
        setError('You do not have permission to view audit logs.');
      } else {
        setError('Could not load audit logs.');
      }
    } finally {
      setLoading(false);
    }
  }, [canView, search, action, modelType, from, to, page]);

  useEffect(() => {
    const t = setTimeout(fetchLogs, 250);
    return () => clearTimeout(t);
  }, [fetchLogs]);

  useEffect(() => {
    setPage(1);
  }, [search, action, modelType, from, to]);

  if (!canView) return null;

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h4 className="mb-1">Audit Logs</h4>
          <p className="text-muted mb-0 small">
            Immutable record of every change — {meta.total || 0} entries
          </p>
        </div>
      </div>

      <div className="card border-0 shadow-sm mb-3">
        <div className="card-body">
          <div className="row g-2">
            <div className="col-12 col-md-4">
              <div className="input-group">
                <span className="input-group-text bg-white">
                  <i className="bi bi-search"></i>
                </span>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Search by label, email, or name"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>
            <div className="col-6 col-md-2">
              <select
                className="form-select"
                value={action}
                onChange={(e) => setAction(e.target.value)}
              >
                {ACTIONS.map((a) => (
                  <option key={a.value} value={a.value}>{a.label}</option>
                ))}
              </select>
            </div>
            <div className="col-6 col-md-2">
              <select
                className="form-select"
                value={modelType}
                onChange={(e) => setModelType(e.target.value)}
              >
                {MODEL_TYPES.map((m) => (
                  <option key={m.value} value={m.value}>{m.label}</option>
                ))}
              </select>
            </div>
            <div className="col-6 col-md-2">
              <input
                type="date"
                className="form-control"
                value={from}
                onChange={(e) => setFrom(e.target.value)}
                title="From"
              />
            </div>
            <div className="col-6 col-md-1">
              <input
                type="date"
                className="form-control"
                value={to}
                onChange={(e) => setTo(e.target.value)}
                title="To"
              />
            </div>
            <div className="col-12 col-md-1">
              <button
                type="button"
                className="btn btn-outline-secondary w-100"
                title="Refresh"
                onClick={fetchLogs}
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
                <th style={{ width: 130 }}>Action</th>
                <th>Entity</th>
                <th style={{ width: 220 }}>User</th>
                <th style={{ width: 180 }}>When</th>
                <th style={{ width: 100 }} className="text-end">Details</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={5} className="text-center py-5 text-muted">
                    <div className="spinner-border spinner-border-sm me-2"></div>
                    Loading audit logs…
                  </td>
                </tr>
              )}

              {!loading && error && (
                <tr>
                  <td colSpan={5} className="text-center py-5 text-danger">{error}</td>
                </tr>
              )}

              {!loading && !error && logs.length === 0 && (
                <tr>
                  <td colSpan={5} className="text-center py-5 text-muted">
                    No audit log entries match your filters.
                  </td>
                </tr>
              )}

              {!loading && logs.map((log) => (
                <tr key={log.id}>
                  <td>
                    <span className={actionBadge(log.action)}>
                      <i className={`bi ${actionIcon(log.action)} me-1`}></i>
                      {log.action}
                    </span>
                  </td>
                  <td>
                    <div className="fw-semibold text-dark small">
                      {log.model_label || `${log.model_type} #${log.model_id}`}
                    </div>
                    <div className="text-muted" style={{ fontSize: '0.72rem' }}>
                      {log.model_type} · ID {log.model_id}
                    </div>
                  </td>
                  <td className="small">
                    <div className="text-dark">{log.user_name || 'system'}</div>
                    <div className="text-muted" style={{ fontSize: '0.72rem' }}>
                      {log.user_email || '—'}
                    </div>
                  </td>
                  <td className="text-muted small">
                    {log.created_at ? new Date(log.created_at).toLocaleString() : '—'}
                  </td>
                  <td className="text-end">
                    <button
                      type="button"
                      className="btn btn-sm btn-outline-secondary"
                      onClick={() => setViewing(log)}
                    >
                      <i className="bi bi-eye"></i>
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {!loading && meta.last_page > 1 && (
          <div className="card-footer bg-white d-flex justify-content-between align-items-center">
            <div className="text-muted small">
              Page {meta.current_page} of {meta.last_page} · {meta.total} total
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
              Loading audit logs…
            </div>
          </div>
        )}

        {!loading && error && <div className="alert alert-danger">{error}</div>}

        {!loading && !error && logs.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No audit log entries match your filters.
            </div>
          </div>
        )}

        {!loading && logs.map((log) => (
          <button
            key={log.id}
            type="button"
            onClick={() => setViewing(log)}
            className="kfm-mobile-card"
            style={{ textAlign: 'left', border: 'none', width: '100%', cursor: 'pointer' }}
          >
            <div className="kfm-mobile-card__head">
              <span className={actionBadge(log.action)}>
                <i className={`bi ${actionIcon(log.action)} me-1`}></i>
                {log.action}
              </span>
              <span className="text-muted small">
                {log.created_at ? new Date(log.created_at).toLocaleDateString() : ''}
              </span>
            </div>

            <div className="kfm-mobile-card__subject">
              {log.model_label || `${log.model_type} #${log.model_id}`}
            </div>

            <div className="kfm-mobile-card__meta">
              <div className="kfm-mobile-card__meta-row">
                <i className="bi bi-tag"></i>
                <span>{log.model_type} · ID {log.model_id}</span>
              </div>
              <div className="kfm-mobile-card__meta-row">
                <i className="bi bi-person"></i>
                <span>{log.user_name || 'system'}</span>
              </div>
              {log.user_email && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-envelope"></i>
                  <span className="text-truncate">{log.user_email}</span>
                </div>
              )}
              <div className="kfm-mobile-card__meta-row">
                <i className="bi bi-clock"></i>
                <span>
                  {log.created_at ? new Date(log.created_at).toLocaleString() : '—'}
                </span>
              </div>
            </div>
          </button>
        ))}

        {!loading && meta.last_page > 1 && (
          <div className="d-flex justify-content-between align-items-center mt-3">
            <div className="text-muted small">
              Page {meta.current_page} of {meta.last_page} · {meta.total} total
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

      <AuditLogViewModal
        show={!!viewing}
        log={viewing}
        onClose={() => setViewing(null)}
      />
    </>
  );
}