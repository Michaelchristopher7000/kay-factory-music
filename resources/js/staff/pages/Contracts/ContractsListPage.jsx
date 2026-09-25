import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canCreateContract, canUpdateContract, canDeleteContract } from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const STATUSES = [
  { value: '', label: 'All statuses' },
  { value: 'draft', label: 'Draft' },
  { value: 'pending_signature', label: 'Pending Signature' },
  { value: 'active', label: 'Active' },
  { value: 'expired', label: 'Expired' },
  { value: 'terminated', label: 'Terminated' },
  { value: 'cancelled', label: 'Cancelled' },
];

const TYPES = [
  { value: '', label: 'All types' },
  { value: 'artist_agreement', label: 'Artist Agreement' },
  { value: 'recording_agreement', label: 'Recording Agreement' },
  { value: 'distribution_agreement', label: 'Distribution Agreement' },
  { value: 'management_agreement', label: 'Management Agreement' },
  { value: 'publishing_agreement', label: 'Publishing Agreement' },
  { value: 'licensing_agreement', label: 'Licensing Agreement' },
  { value: 'other', label: 'Other' },
];

const statusBadge = (status) => {
  switch (status) {
    case 'active':            return 'badge bg-success-subtle text-success-emphasis';
    case 'pending_signature': return 'badge bg-warning-subtle text-warning-emphasis';
    case 'draft':             return 'badge bg-secondary-subtle text-secondary-emphasis';
    case 'expired':           return 'badge bg-secondary-subtle text-secondary-emphasis';
    case 'terminated':        return 'badge bg-danger-subtle text-danger-emphasis';
    case 'cancelled':         return 'badge bg-dark-subtle text-dark-emphasis';
    default:                  return 'badge bg-light text-dark';
  }
};

const statusLabel = (s) => (s || '').replace(/_/g, ' ');
const typeLabel = (t) => (t || '').replace(/_/g, ' ');

export default function ContractsListPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [contracts, setContracts] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [type, setType] = useState('');
  const [page, setPage] = useState(1);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchContracts = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/contracts', {
        params: {
          search: search || undefined,
          status: status || undefined,
          type: type || undefined,
          per_page: 15,
          page,
        },
      });

      setContracts(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load contracts. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [search, status, type, page]);

  useEffect(() => {
    const t = setTimeout(fetchContracts, 250);
    return () => clearTimeout(t);
  }, [fetchContracts]);

  useEffect(() => { setPage(1); }, [search, status, type]);

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);

    try {
      await api.delete(`/contracts/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchContracts();
    } catch (err) {
      alert('Could not delete contract. ' + (err.response?.data?.message || ''));
    } finally {
      setDeleting(false);
    }
  };

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h4 className="mb-1">Contracts</h4>
          <p className="text-muted mb-0 small">
            Manage label agreements — {meta.total || 0} total
          </p>
        </div>

        {canCreateContract(roleSlug) && (
          <Link to="/contracts/new" className="btn btn-dark">
            <i className="bi bi-plus-lg me-2"></i>
            Add Contract
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
                  placeholder="Search by title or code"
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
            <div className="col-12 col-md-1 d-flex">
              <button
                type="button"
                className="btn btn-outline-secondary w-100"
                title="Refresh"
                onClick={fetchContracts}
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
                <th style={{ width: 130 }}>Type</th>
                <th style={{ width: 130 }}>Status</th>
                <th style={{ width: 120 }}>Signed</th>
                <th style={{ width: 160 }} className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    <div className="spinner-border spinner-border-sm me-2"></div>
                    Loading contracts…
                  </td>
                </tr>
              )}

              {!loading && error && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-danger">{error}</td>
                </tr>
              )}

              {!loading && !error && contracts.length === 0 && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    No contracts found.
                    {(search || status || type) && <> Try clearing the filters.</>}
                  </td>
                </tr>
              )}

              {!loading && contracts.map((c) => (
                <tr key={c.id}>
                  <td><code className="text-dark">{c.contract_code}</code></td>
                  <td>
                    <Link to={`/contracts/${c.id}`} className="text-decoration-none fw-semibold text-dark">
                      {c.title}
                    </Link>
                  </td>
                  <td className="text-muted small">
                    {c.artist?.name || '—'}
                  </td>
                  <td className="text-muted small text-capitalize">
                    {typeLabel(c.type)}
                  </td>
                  <td>
                    <span className={statusBadge(c.status)}>
                      {statusLabel(c.status)}
                    </span>
                  </td>
                  <td className="text-muted small">
                    {c.signed_date || '—'}
                  </td>
                  <td className="text-end">
                    <Link
                      to={`/contracts/${c.id}`}
                      className="btn btn-sm btn-outline-secondary me-1"
                      title="View"
                    >
                      <i className="bi bi-eye"></i>
                    </Link>

                    {canUpdateContract(roleSlug) && (
                      <Link
                        to={`/contracts/${c.id}/edit`}
                        className="btn btn-sm btn-outline-secondary me-1"
                        title="Edit"
                      >
                        <i className="bi bi-pencil"></i>
                      </Link>
                    )}

                    {canDeleteContract(roleSlug) && (
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger"
                        title="Delete"
                        onClick={() => setDeleteTarget(c)}
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
              Loading contracts…
            </div>
          </div>
        )}

        {!loading && error && <div className="alert alert-danger">{error}</div>}

        {!loading && !error && contracts.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No contracts found.
              {(search || status || type) && <> Try clearing the filters.</>}
            </div>
          </div>
        )}

        {!loading && contracts.map((c) => (
          <Link
            key={c.id}
            to={`/contracts/${c.id}`}
            className="kfm-mobile-card"
          >
            <div className="kfm-mobile-card__head">
              <code className="kfm-mobile-card__ref">{c.contract_code}</code>
              <span className={statusBadge(c.status)}>
                {statusLabel(c.status)}
              </span>
            </div>

            <div className="kfm-mobile-card__subject">{c.title}</div>

            <div className="kfm-mobile-card__meta">
              {c.artist?.name && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-person"></i>
                  <span>{c.artist.name}</span>
                </div>
              )}
              {c.type && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-file-earmark-text"></i>
                  <span className="text-capitalize">{typeLabel(c.type)}</span>
                </div>
              )}
              <div className="kfm-mobile-card__meta-row">
                <i className="bi bi-calendar3"></i>
                <span>{c.signed_date || 'Not signed'}</span>
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
        title="Delete contract?"
        message={
          deleteTarget
            ? `"${deleteTarget.title}" (${deleteTarget.contract_code}) will be soft-deleted.`
            : ''
        }
        loading={deleting}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </>
  );
}