import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import {
  canCreateExpense,
  canUpdateExpense,
  canDeleteExpense,
} from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const CATEGORIES = [
  { value: '', label: 'All categories' },
  { value: 'studio', label: 'Studio' },
  { value: 'marketing', label: 'Marketing' },
  { value: 'distribution_fee', label: 'Distribution Fee' },
  { value: 'advance', label: 'Advance' },
  { value: 'video', label: 'Video' },
  { value: 'travel', label: 'Travel' },
  { value: 'legal', label: 'Legal' },
  { value: 'admin', label: 'Admin' },
  { value: 'other', label: 'Other' },
];

const CURRENCIES = ['', 'NGN', 'USD', 'ZAR', 'GBP', 'EUR'];

const formatMoney = (v, c) =>
  `${c || 'NGN'} ${Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function ExpensesListPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [expenses, setExpenses] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('');
  const [currency, setCurrency] = useState('');
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [page, setPage] = useState(1);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchExpenses = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/expenses', {
        params: {
          search: search || undefined,
          category: category || undefined,
          currency: currency || undefined,
          from: from || undefined,
          to: to || undefined,
          per_page: 15,
          page,
        },
      });

      setExpenses(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load expenses.');
    } finally {
      setLoading(false);
    }
  }, [search, category, currency, from, to, page]);

  useEffect(() => {
    const t = setTimeout(fetchExpenses, 250);
    return () => clearTimeout(t);
  }, [fetchExpenses]);

  useEffect(() => { setPage(1); }, [search, category, currency, from, to]);

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);

    try {
      await api.delete(`/expenses/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchExpenses();
    } catch (err) {
      alert('Could not delete expense. ' + (err.response?.data?.message || ''));
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
          <h4 className="mb-1 mt-2">Expenses</h4>
          <p className="text-muted mb-0 small">
            Money spent — {meta.total || 0} total
          </p>
        </div>

        {canCreateExpense(roleSlug) && (
          <Link to="/expenses/new" className="btn btn-dark">
            <i className="bi bi-plus-lg me-2"></i>
            Add Expense
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
                  placeholder="Search by code, reference, description"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>
            <div className="col-6 col-md-2">
              <select
                className="form-select"
                value={category}
                onChange={(e) => setCategory(e.target.value)}
              >
                {CATEGORIES.map((c) => (
                  <option key={c.value} value={c.value}>{c.label}</option>
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
                title="From (incurred at)"
              />
            </div>
            <div className="col-6 col-md-1">
              <input
                type="date"
                className="form-control"
                placeholder="To"
                value={to}
                onChange={(e) => setTo(e.target.value)}
                title="To (incurred at)"
              />
            </div>
            <div className="col-12 col-md-1 d-flex">
              <button
                type="button"
                className="btn btn-outline-secondary w-100"
                title="Refresh"
                onClick={fetchExpenses}
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
                <th style={{ width: 140 }}>Category</th>
                <th>Description / Artist</th>
                <th style={{ width: 120 }}>Incurred</th>
                <th style={{ width: 150 }} className="text-end">Amount</th>
                <th style={{ width: 160 }} className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={6} className="text-center py-5 text-muted">
                    <div className="spinner-border spinner-border-sm me-2"></div>
                    Loading expenses…
                  </td>
                </tr>
              )}

              {!loading && error && (
                <tr>
                  <td colSpan={6} className="text-center py-5 text-danger">{error}</td>
                </tr>
              )}

              {!loading && !error && expenses.length === 0 && (
                <tr>
                  <td colSpan={6} className="text-center py-5 text-muted">
                    No expenses found.
                  </td>
                </tr>
              )}

              {!loading && expenses.map((x) => (
                <tr key={x.id}>
                  <td><code className="text-dark">{x.expense_code}</code></td>
                  <td className="text-muted small text-capitalize">
                    {(x.category || '').replace(/_/g, ' ')}
                  </td>
                  <td>
                    {x.description && (
                      <div className="fw-semibold text-dark small">{x.description}</div>
                    )}
                    {x.artist && (
                      <div className="text-muted small">
                        <Link to={`/artists/${x.artist.id}`} className="text-muted text-decoration-none">
                          {x.artist.name}
                        </Link>
                      </div>
                    )}
                    {!x.description && !x.artist && <span className="text-muted">—</span>}
                  </td>
                  <td className="text-muted small">{x.incurred_at || '—'}</td>
                  <td className="text-end fw-semibold">{formatMoney(x.amount, x.currency)}</td>
                  <td className="text-end">
                    <Link
                      to={`/expenses/${x.id}`}
                      className="btn btn-sm btn-outline-secondary me-1"
                      title="View"
                    >
                      <i className="bi bi-eye"></i>
                    </Link>

                    {canUpdateExpense(roleSlug) && (
                      <Link
                        to={`/expenses/${x.id}/edit`}
                        className="btn btn-sm btn-outline-secondary me-1"
                        title="Edit"
                      >
                        <i className="bi bi-pencil"></i>
                      </Link>
                    )}

                    {canDeleteExpense(roleSlug) && (
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger"
                        title="Delete"
                        onClick={() => setDeleteTarget(x)}
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
              Loading expenses…
            </div>
          </div>
        )}

        {!loading && error && <div className="alert alert-danger">{error}</div>}

        {!loading && !error && expenses.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No expenses found.
            </div>
          </div>
        )}

        {!loading && expenses.map((x) => (
          <Link
            key={x.id}
            to={`/expenses/${x.id}`}
            className="kfm-mobile-card"
          >
            <div className="kfm-mobile-card__head">
              <code className="kfm-mobile-card__ref">{x.expense_code}</code>
              <span className="badge bg-light text-dark text-capitalize">
                {(x.category || '').replace(/_/g, ' ')}
              </span>
            </div>

            <div className="kfm-mobile-card__subject">
              {formatMoney(x.amount, x.currency)}
            </div>

            <div className="kfm-mobile-card__meta">
              {x.description && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-card-text"></i>
                  <span className="text-truncate">{x.description}</span>
                </div>
              )}
              {x.artist?.name && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-person"></i>
                  <span>{x.artist.name}</span>
                </div>
              )}
              {x.incurred_at && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-calendar3"></i>
                  <span>{x.incurred_at}</span>
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
        title="Delete expense?"
        message={
          deleteTarget
            ? `Expense ${deleteTarget.expense_code} will be soft-deleted.`
            : ''
        }
        loading={deleting}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </>
  );
}