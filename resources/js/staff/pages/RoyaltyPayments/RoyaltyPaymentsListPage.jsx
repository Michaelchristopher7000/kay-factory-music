import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import {
  canCreateRoyaltyPayment,
  canUpdateRoyaltyPayment,
  canDeleteRoyaltyPayment,
} from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const METHODS = [
  { value: '', label: 'All methods' },
  { value: 'bank_transfer', label: 'Bank Transfer' },
  { value: 'cash', label: 'Cash' },
  { value: 'cheque', label: 'Cheque' },
  { value: 'other', label: 'Other' },
];

const fmt = (v, c) => `${c || 'NGN'} ${Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function RoyaltyPaymentsListPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [payments, setPayments] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState('');
  const [method, setMethod] = useState('');
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [page, setPage] = useState(1);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchPayments = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/royalty-payments', {
        params: {
          search: search || undefined,
          method: method || undefined,
          from: from || undefined,
          to: to || undefined,
          per_page: 15,
          page,
        },
      });

      setPayments(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load payments.');
    } finally {
      setLoading(false);
    }
  }, [search, method, from, to, page]);

  useEffect(() => {
    const t = setTimeout(fetchPayments, 250);
    return () => clearTimeout(t);
  }, [fetchPayments]);

  useEffect(() => { setPage(1); }, [search, method, from, to]);

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await api.delete(`/royalty-payments/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchPayments();
    } catch (err) {
      alert('Could not delete payment. ' + (err.response?.data?.message || ''));
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
          <h4 className="mb-1 mt-2">Royalty Payments</h4>
          <p className="text-muted mb-0 small">
            Cash paid to artists — {meta.total || 0} total
          </p>
        </div>

        {canCreateRoyaltyPayment(roleSlug) && (
          <Link to="/royalty-payments/new" className="btn btn-dark">
            <i className="bi bi-plus-lg me-2"></i>
            Record Payment
          </Link>
        )}
      </div>

      <div className="card border-0 shadow-sm mb-3">
        <div className="card-body">
          <div className="row g-2">
            <div className="col-12 col-md-5">
              <div className="input-group">
                <span className="input-group-text bg-white"><i className="bi bi-search"></i></span>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Search by code, reference, notes"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>
            <div className="col-6 col-md-3">
              <select className="form-select" value={method} onChange={(e) => setMethod(e.target.value)}>
                {METHODS.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
              </select>
            </div>
            <div className="col-6 col-md-2">
              <input type="date" className="form-control" value={from} onChange={(e) => setFrom(e.target.value)} title="From (paid at)" />
            </div>
            <div className="col-6 col-md-1">
              <input type="date" className="form-control" value={to} onChange={(e) => setTo(e.target.value)} title="To (paid at)" />
            </div>
            <div className="col-6 col-md-1">
              <button type="button" className="btn btn-outline-secondary w-100" onClick={fetchPayments}>
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
                <th style={{ width: 140 }}>Statement</th>
                <th style={{ width: 130 }}>Method</th>
                <th style={{ width: 120 }}>Paid At</th>
                <th style={{ width: 150 }} className="text-end">Amount</th>
                <th style={{ width: 160 }} className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr><td colSpan={7} className="text-center py-5 text-muted">
                  <div className="spinner-border spinner-border-sm me-2"></div>Loading…
                </td></tr>
              )}
              {!loading && error && (
                <tr><td colSpan={7} className="text-center py-5 text-danger">{error}</td></tr>
              )}
              {!loading && !error && payments.length === 0 && (
                <tr><td colSpan={7} className="text-center py-5 text-muted">No payments recorded.</td></tr>
              )}
              {!loading && payments.map((p) => (
                <tr key={p.id}>
                  <td><code className="text-dark">{p.payment_code}</code></td>
                  <td>
                    {p.artist && (
                      <Link to={`/artists/${p.artist.id}`} className="text-decoration-none fw-semibold text-dark">
                        {p.artist.name}
                      </Link>
                    )}
                  </td>
                  <td className="small">
                    {p.statement ? (
                      <Link to={`/royalty-statements/${p.statement.id}`}>
                        {p.statement.statement_code}
                      </Link>
                    ) : ('—')}
                  </td>
                  <td className="text-muted small text-capitalize">{(p.method || '').replace(/_/g, ' ')}</td>
                  <td className="text-muted small">{p.paid_at || '—'}</td>
                  <td className="text-end fw-semibold">{fmt(p.amount, p.currency)}</td>
                  <td className="text-end">
                    <Link to={`/royalty-payments/${p.id}`} className="btn btn-sm btn-outline-secondary me-1" title="View">
                      <i className="bi bi-eye"></i>
                    </Link>
                    {canUpdateRoyaltyPayment(roleSlug) && (
                      <Link to={`/royalty-payments/${p.id}/edit`} className="btn btn-sm btn-outline-secondary me-1" title="Edit">
                        <i className="bi bi-pencil"></i>
                      </Link>
                    )}
                    {canDeleteRoyaltyPayment(roleSlug) && (
                      <button type="button" className="btn btn-sm btn-outline-danger" onClick={() => setDeleteTarget(p)} title="Delete">
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

        {!loading && !error && payments.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No payments recorded.
            </div>
          </div>
        )}

        {!loading && payments.map((p) => (
          <Link
            key={p.id}
            to={`/royalty-payments/${p.id}`}
            className="kfm-mobile-card"
          >
            <div className="kfm-mobile-card__head">
              <code className="kfm-mobile-card__ref">{p.payment_code}</code>
              <span className="badge bg-light text-dark text-capitalize">
                {(p.method || '').replace(/_/g, ' ')}
              </span>
            </div>

            <div className="kfm-mobile-card__subject">
              {fmt(p.amount, p.currency)}
            </div>

            <div className="kfm-mobile-card__meta">
              {p.artist?.name && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-person"></i>
                  <span>{p.artist.name}</span>
                </div>
              )}
              {p.statement?.statement_code && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-file-earmark-text"></i>
                  <span>{p.statement.statement_code}</span>
                </div>
              )}
              {p.paid_at && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-calendar3"></i>
                  <span>Paid {p.paid_at}</span>
                </div>
              )}
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
        title="Delete payment?"
        message={deleteTarget ? `Payment ${deleteTarget.payment_code} will be soft-deleted.` : ''}
        loading={deleting}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </>
  );
}