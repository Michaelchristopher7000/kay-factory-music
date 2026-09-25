import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import {
  canCreateRoyaltyStatement,
  canUpdateRoyaltyStatement,
  canDeleteRoyaltyStatement,
} from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const STATUSES = [
  { value: '', label: 'All statuses' },
  { value: 'draft', label: 'Draft' },
  { value: 'issued', label: 'Issued' },
  { value: 'paid', label: 'Paid' },
  { value: 'void', label: 'Void' },
];

const CURRENCIES = ['', 'NGN', 'USD', 'ZAR', 'GBP', 'EUR'];

const statusBadge = (s) => {
  switch (s) {
    case 'paid':   return 'badge bg-success-subtle text-success-emphasis';
    case 'issued': return 'badge bg-primary-subtle text-primary-emphasis';
    case 'draft':  return 'badge bg-secondary-subtle text-secondary-emphasis';
    case 'void':   return 'badge bg-danger-subtle text-danger-emphasis';
    default:       return 'badge bg-light text-dark';
  }
};

const fmt = (v, c) => `${c || 'NGN'} ${Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function RoyaltyStatementsListPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [statements, setStatements] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [currency, setCurrency] = useState('');
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [page, setPage] = useState(1);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchStatements = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/royalty-statements', {
        params: {
          search: search || undefined,
          status: status || undefined,
          currency: currency || undefined,
          from: from || undefined,
          to: to || undefined,
          per_page: 15,
          page,
        },
      });

      setStatements(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load statements.');
    } finally {
      setLoading(false);
    }
  }, [search, status, currency, from, to, page]);

  useEffect(() => {
    const t = setTimeout(fetchStatements, 250);
    return () => clearTimeout(t);
  }, [fetchStatements]);

  useEffect(() => { setPage(1); }, [search, status, currency, from, to]);

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await api.delete(`/royalty-statements/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchStatements();
    } catch (err) {
      alert('Could not delete statement. ' + (err.response?.data?.message || ''));
    } finally {
      setDeleting(false);
    }
  };

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <Link to="/royalties" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Royalties
          </Link>
          <h4 className="mb-1 mt-2">Royalty Statements</h4>
          <p className="text-muted mb-0 small">
            Period statements — {meta.total || 0} total
          </p>
        </div>

        {canCreateRoyaltyStatement(roleSlug) && (
          <Link to="/royalty-statements/new" className="btn btn-dark">
            <i className="bi bi-plus-lg me-2"></i>
            New Statement
          </Link>
        )}
      </div>

      <div className="card border-0 shadow-sm mb-3">
        <div className="card-body">
          <div className="row g-2">
            <div className="col-12 col-md-4">
              <div className="input-group">
                <span className="input-group-text bg-white"><i className="bi bi-search"></i></span>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Search by code or notes"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>
            <div className="col-6 col-md-2">
              <select className="form-select" value={status} onChange={(e) => setStatus(e.target.value)}>
                {STATUSES.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
              </select>
            </div>
            <div className="col-6 col-md-2">
              <select className="form-select" value={currency} onChange={(e) => setCurrency(e.target.value)}>
                {CURRENCIES.map((c) => <option key={c} value={c}>{c || 'All currencies'}</option>)}
              </select>
            </div>
            <div className="col-6 col-md-2">
              <input type="date" className="form-control" value={from} onChange={(e) => setFrom(e.target.value)} title="From (period end)" />
            </div>
            <div className="col-6 col-md-1">
              <input type="date" className="form-control" value={to} onChange={(e) => setTo(e.target.value)} title="To (period end)" />
            </div>
            <div className="col-12 col-md-1">
              <button type="button" className="btn btn-outline-secondary w-100" onClick={fetchStatements}>
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
                <th>Artist</th>
                <th style={{ width: 200 }}>Period</th>
                <th style={{ width: 100 }}>Status</th>
                <th style={{ width: 150 }} className="text-end">Revenue</th>
                <th style={{ width: 150 }} className="text-end">Royalty</th>
                <th style={{ width: 130 }} className="text-end">Balance</th>
                <th style={{ width: 160 }} className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr><td colSpan={8} className="text-center py-5 text-muted">
                  <div className="spinner-border spinner-border-sm me-2"></div>Loading…
                </td></tr>
              )}
              {!loading && error && (
                <tr><td colSpan={8} className="text-center py-5 text-danger">{error}</td></tr>
              )}
              {!loading && !error && statements.length === 0 && (
                <tr><td colSpan={8} className="text-center py-5 text-muted">No statements found.</td></tr>
              )}
              {!loading && statements.map((s) => (
                <tr key={s.id}>
                  <td><code className="text-dark">{s.statement_code}</code></td>
                  <td>
                    {s.artist && (
                      <Link to={`/artists/${s.artist.id}`} className="text-decoration-none fw-semibold text-dark">
                        {s.artist.name}
                      </Link>
                    )}
                  </td>
                  <td className="text-muted small">{s.period_start} → {s.period_end}</td>
                  <td><span className={statusBadge(s.status)}>{(s.status || '').replace(/_/g, ' ')}</span></td>
                  <td className="text-end text-muted small">{fmt(s.total_revenue, s.currency)}</td>
                  <td className="text-end fw-semibold">{fmt(s.total_royalty, s.currency)}</td>
                  <td className="text-end">
                    <span className={Number(s.balance) > 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold'}>
                      {fmt(s.balance, s.currency)}
                    </span>
                  </td>
                  <td className="text-end">
                    <Link to={`/royalty-statements/${s.id}`} className="btn btn-sm btn-outline-secondary me-1" title="View">
                      <i className="bi bi-eye"></i>
                    </Link>
                    {canUpdateRoyaltyStatement(roleSlug) && (
                      <Link to={`/royalty-statements/${s.id}/edit`} className="btn btn-sm btn-outline-secondary me-1" title="Edit">
                        <i className="bi bi-pencil"></i>
                      </Link>
                    )}
                    {canDeleteRoyaltyStatement(roleSlug) && (
                      <button type="button" className="btn btn-sm btn-outline-danger" onClick={() => setDeleteTarget(s)} title="Delete">
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
            <div className="text-muted small">Page {meta.current_page} of {meta.last_page}</div>
            <div>
              <button type="button" className="btn btn-sm btn-outline-secondary me-1" disabled={meta.current_page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
                <i className="bi bi-chevron-left"></i> Prev
              </button>
              <button type="button" className="btn btn-sm btn-outline-secondary" disabled={meta.current_page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>
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
              Loading…
            </div>
          </div>
        )}

        {!loading && error && <div className="alert alert-danger">{error}</div>}

        {!loading && !error && statements.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No statements found.
            </div>
          </div>
        )}

        {!loading && statements.map((s) => (
          <Link
            key={s.id}
            to={`/royalty-statements/${s.id}`}
            className="kfm-mobile-card"
          >
            <div className="kfm-mobile-card__head">
              <code className="kfm-mobile-card__ref">{s.statement_code}</code>
              <span className={statusBadge(s.status)}>
                {(s.status || '').replace(/_/g, ' ')}
              </span>
            </div>

            <div className="kfm-mobile-card__subject">
              {s.artist?.name || 'Statement'}
            </div>

            <div className="kfm-mobile-card__meta">
              <div className="kfm-mobile-card__meta-row">
                <i className="bi bi-calendar3"></i>
                <span>{s.period_start} → {s.period_end}</span>
              </div>
              <div className="kfm-mobile-card__meta-row">
                <i className="bi bi-cash-stack"></i>
                <span>Revenue: {fmt(s.total_revenue, s.currency)}</span>
              </div>
              <div className="kfm-mobile-card__meta-row">
                <i className="bi bi-wallet2"></i>
                <span>Royalty: {fmt(s.total_royalty, s.currency)}</span>
              </div>
              <div className="kfm-mobile-card__meta-row">
                <i className="bi bi-exclamation-circle"></i>
                <span className={Number(s.balance) > 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold'}>
                  Balance: {fmt(s.balance, s.currency)}
                </span>
              </div>
            </div>
          </Link>
        ))}

        {!loading && meta.last_page > 1 && (
          <div className="d-flex justify-content-between align-items-center mt-3">
            <div className="text-muted small">Page {meta.current_page} of {meta.last_page}</div>
            <div>
              <button type="button" className="btn btn-sm btn-outline-secondary me-1" disabled={meta.current_page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
                <i className="bi bi-chevron-left"></i> Prev
              </button>
              <button type="button" className="btn btn-sm btn-outline-secondary" disabled={meta.current_page >= meta.last_page} onClick={() => setPage((p) => p + 1)}>
                Next <i className="bi bi-chevron-right"></i>
              </button>
            </div>
          </div>
        )}
      </div>

      <ConfirmDeleteModal
        show={!!deleteTarget}
        title="Delete statement?"
        message={deleteTarget ? `Statement ${deleteTarget.statement_code} will be soft-deleted.` : ''}
        loading={deleting}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </>
  );
}