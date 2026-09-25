import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import {
  canCreateRevenueEntry,
  canUpdateRevenueEntry,
  canDeleteRevenueEntry,
} from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const SOURCES = [
  { value: '', label: 'All sources' },
  { value: 'streaming', label: 'Streaming' },
  { value: 'sync', label: 'Sync' },
  { value: 'physical', label: 'Physical' },
  { value: 'youtube_content_id', label: 'YouTube Content ID' },
  { value: 'publishing', label: 'Publishing' },
  { value: 'merch', label: 'Merch' },
  { value: 'other', label: 'Other' },
];

const CURRENCIES = ['', 'NGN', 'USD', 'ZAR', 'GBP', 'EUR'];

const formatMoney = (v, c) => `${c || 'NGN'} ${Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function RevenueEntriesListPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [entries, setEntries] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState('');
  const [source, setSource] = useState('');
  const [currency, setCurrency] = useState('');
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [page, setPage] = useState(1);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchEntries = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/revenue-entries', {
        params: {
          search: search || undefined,
          source: source || undefined,
          currency: currency || undefined,
          from: from || undefined,
          to: to || undefined,
          per_page: 15,
          page,
        },
      });

      setEntries(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load revenue entries.');
    } finally {
      setLoading(false);
    }
  }, [search, source, currency, from, to, page]);

  useEffect(() => {
    const t = setTimeout(fetchEntries, 250);
    return () => clearTimeout(t);
  }, [fetchEntries]);

  useEffect(() => { setPage(1); }, [search, source, currency, from, to]);

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);

    try {
      await api.delete(`/revenue-entries/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchEntries();
    } catch (err) {
      alert('Could not delete entry. ' + (err.response?.data?.message || ''));
    } finally {
      setDeleting(false);
    }
  };

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <Link to="/finance" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Finance
          </Link>
          <h4 className="mb-1 mt-2">Revenue Entries</h4>
          <p className="text-muted mb-0 small">
            Money received — {meta.total || 0} total
          </p>
        </div>

        {canCreateRevenueEntry(roleSlug) && (
          <Link to="/revenue-entries/new" className="btn btn-dark">
            <i className="bi bi-plus-lg me-2"></i>
            Add Revenue
          </Link>
        )}
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
                  placeholder="Search by code, reference, notes"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>
            <div className="col-6 col-md-2">
              <select
                className="form-select"
                value={source}
                onChange={(e) => setSource(e.target.value)}
              >
                {SOURCES.map((s) => (
                  <option key={s.value} value={s.value}>{s.label}</option>
                ))}
              </select>
            </div>
            <div className="col-6 col-md-2">
              <select
                className="form-select"
                value={currency}
                onChange={(e) => setCurrency(e.target.value)}
              >
                {CURRENCIES.map((c) => (
                  <option key={c} value={c}>{c || 'All currencies'}</option>
                ))}
              </select>
            </div>
            <div className="col-6 col-md-2">
              <input
                type="date"
                className="form-control"
                placeholder="From"
                value={from}
                onChange={(e) => setFrom(e.target.value)}
                title="From (period end)"
              />
            </div>
            <div className="col-6 col-md-1">
              <input
                type="date"
                className="form-control"
                placeholder="To"
                value={to}
                onChange={(e) => setTo(e.target.value)}
                title="To (period end)"
              />
            </div>
            <div className="col-12 col-md-1 d-flex">
              <button
                type="button"
                className="btn btn-outline-secondary w-100"
                title="Refresh"
                onClick={fetchEntries}
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
                <th style={{ width: 130 }}>Source</th>
                <th>Artist / Release</th>
                <th style={{ width: 130 }}>Period End</th>
                <th style={{ width: 150 }} className="text-end">Amount</th>
                <th style={{ width: 160 }} className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={6} className="text-center py-5 text-muted">
                    <div className="spinner-border spinner-border-sm me-2"></div>
                    Loading revenue…
                  </td>
                </tr>
              )}

              {!loading && error && (
                <tr>
                  <td colSpan={6} className="text-center py-5 text-danger">{error}</td>
                </tr>
              )}

              {!loading && !error && entries.length === 0 && (
                <tr>
                  <td colSpan={6} className="text-center py-5 text-muted">
                    No revenue entries found.
                  </td>
                </tr>
              )}

              {!loading && entries.map((e) => (
                <tr key={e.id}>
                  <td><code className="text-dark">{e.entry_code}</code></td>
                  <td className="text-muted small text-capitalize">
                    {(e.source || '').replace(/_/g, ' ')}
                  </td>
                  <td>
                    {e.artist && (
                      <Link
                        to={`/artists/${e.artist.id}`}
                        className="text-decoration-none fw-semibold text-dark"
                      >
                        {e.artist.name}
                      </Link>
                    )}
                    {e.release && (
                      <div className="text-muted small">
                        <Link to={`/releases/${e.release.id}`} className="text-muted text-decoration-none">
                          {e.release.title}
                        </Link>
                      </div>
                    )}
                    {!e.artist && !e.release && <span className="text-muted">—</span>}
                  </td>
                  <td className="text-muted small">{e.period_end || '—'}</td>
                  <td className="text-end fw-semibold">{formatMoney(e.amount, e.currency)}</td>
                  <td className="text-end">
                    <Link
                      to={`/revenue-entries/${e.id}`}
                      className="btn btn-sm btn-outline-secondary me-1"
                      title="View"
                    >
                      <i className="bi bi-eye"></i>
                    </Link>

                    {canUpdateRevenueEntry(roleSlug) && (
                      <Link
                        to={`/revenue-entries/${e.id}/edit`}
                        className="btn btn-sm btn-outline-secondary me-1"
                        title="Edit"
                      >
                        <i className="bi bi-pencil"></i>
                      </Link>
                    )}

                    {canDeleteRevenueEntry(roleSlug) && (
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger"
                        title="Delete"
                        onClick={() => setDeleteTarget(e)}
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
              Loading revenue…
            </div>
          </div>
        )}

        {!loading && error && <div className="alert alert-danger">{error}</div>}

        {!loading && !error && entries.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No revenue entries found.
            </div>
          </div>
        )}

        {!loading && entries.map((e) => (
          <Link
            key={e.id}
            to={`/revenue-entries/${e.id}`}
            className="kfm-mobile-card"
          >
            <div className="kfm-mobile-card__head">
              <code className="kfm-mobile-card__ref">{e.entry_code}</code>
              <span className="badge bg-light text-dark text-capitalize">
                {(e.source || '').replace(/_/g, ' ')}
              </span>
            </div>

            <div className="kfm-mobile-card__subject">
              {formatMoney(e.amount, e.currency)}
            </div>

            <div className="kfm-mobile-card__meta">
              {e.artist?.name && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-person"></i>
                  <span>{e.artist.name}</span>
                </div>
              )}
              {e.release?.title && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-vinyl"></i>
                  <span className="text-truncate">{e.release.title}</span>
                </div>
              )}
              {e.period_end && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-calendar3"></i>
                  <span>Period end: {e.period_end}</span>
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
        title="Delete revenue entry?"
        message={
          deleteTarget
            ? `Entry ${deleteTarget.entry_code} will be soft-deleted.`
            : ''
        }
        loading={deleting}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </>
  );
}