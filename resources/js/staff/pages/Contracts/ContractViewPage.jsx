import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canUpdateContract, canDeleteContract } from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

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

const formatMoney = (amount, currency = 'NGN') => {
  if (amount === null || amount === undefined || amount === '') return '—';
  return `${currency} ${Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

export default function ContractViewPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [contract, setContract] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get(`/contracts/${id}`);
        setContract(data.data);
      } catch (err) {
        if (err.response?.status === 401) return;
        if (err.response?.status === 404) setError('Contract not found.');
        else setError('Could not load contract.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id]);

  const doDelete = async () => {
    setDeleting(true);
    try {
      await api.delete(`/contracts/${id}`);
      navigate('/contracts');
    } catch {
      alert('Could not delete contract.');
      setDeleting(false);
    }
  };

  if (loading) {
    return (
      <div className="text-center py-5 text-muted">
        <div className="spinner-border spinner-border-sm me-2"></div>
        Loading…
      </div>
    );
  }

  if (error || !contract) {
    return (
      <>
        <Link to="/contracts" className="text-muted text-decoration-none small">
          <i className="bi bi-arrow-left me-1"></i> Back to Contracts
        </Link>
        <div className="alert alert-warning mt-3">{error || 'Contract not found.'}</div>
      </>
    );
  }

  return (
    <>
      <div className="d-flex justify-content-between align-items-start mb-4">
        <div>
          <Link to="/contracts" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Contracts
          </Link>
          <div className="d-flex align-items-center gap-3 mt-2">
            <h4 className="mb-0">{contract.title}</h4>
            <span className="badge bg-light text-dark border">
              <code>{contract.contract_code}</code>
            </span>
            <span className={statusBadge(contract.status)}>
              {(contract.status || '').replace(/_/g, ' ')}
            </span>
          </div>
          {contract.artist && (
            <p className="text-muted mb-0 small mt-1">
              Artist:{' '}
              <Link to={`/artists/${contract.artist.id}`}>
                {contract.artist.name}
              </Link>
            </p>
          )}
        </div>

        <div className="d-flex gap-2">
          {canUpdateContract(roleSlug) && (
            <Link to={`/contracts/${contract.id}/edit`} className="btn btn-outline-secondary">
              <i className="bi bi-pencil me-2"></i>
              Edit
            </Link>
          )}
          {canDeleteContract(roleSlug) && (
            <button
              type="button"
              className="btn btn-outline-danger"
              onClick={() => setConfirmDelete(true)}
            >
              <i className="bi bi-trash me-2"></i>
              Delete
            </button>
          )}
        </div>
      </div>

      <div className="row g-3">
        <div className="col-lg-8">
          <div className="card border-0 shadow-sm mb-3">
            <div className="card-header bg-white"><strong>Details</strong></div>
            <div className="card-body">
              <dl className="row mb-0">
                <dt className="col-sm-4 text-muted fw-normal">Type</dt>
                <dd className="col-sm-8 text-capitalize">{(contract.type || '').replace(/_/g, ' ')}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Status</dt>
                <dd className="col-sm-8 text-capitalize">{(contract.status || '').replace(/_/g, ' ')}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Signed Date</dt>
                <dd className="col-sm-8">{contract.signed_date || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Start Date</dt>
                <dd className="col-sm-8">{contract.start_date || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">End Date</dt>
                <dd className="col-sm-8">{contract.end_date || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Created by</dt>
                <dd className="col-sm-8">{contract.created_by?.name || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Created on</dt>
                <dd className="col-sm-8">
                  {contract.created_at ? new Date(contract.created_at).toLocaleString() : '—'}
                </dd>
              </dl>
            </div>
          </div>

          {contract.terms && (
            <div className="card border-0 shadow-sm">
              <div className="card-header bg-white"><strong>Terms</strong></div>
              <div className="card-body">
                <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{contract.terms}</p>
              </div>
            </div>
          )}
        </div>

        <div className="col-lg-4">
          <div className="card border-0 shadow-sm mb-3">
            <div className="card-header bg-white"><strong>Financials</strong></div>
            <div className="card-body">
              <dl className="row mb-0">
                <dt className="col-sm-6 text-muted fw-normal">Advance</dt>
                <dd className="col-sm-6 text-end">
                  {formatMoney(contract.advance_amount, contract.currency)}
                </dd>

                <dt className="col-sm-6 text-muted fw-normal">Currency</dt>
                <dd className="col-sm-6 text-end">{contract.currency || '—'}</dd>

                <dt className="col-sm-6 text-muted fw-normal">Royalty Rate</dt>
                <dd className="col-sm-6 text-end">
                  {contract.royalty_rate != null ? `${contract.royalty_rate}%` : '—'}
                </dd>
              </dl>
            </div>
          </div>

          {contract.document_path && (
            <div className="card border-0 shadow-sm">
              <div className="card-header bg-white"><strong>Document</strong></div>
              <div className="card-body">
                <a
                  href={contract.document_path}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-break small"
                >
                  {contract.document_path}
                </a>
              </div>
            </div>
          )}
        </div>
      </div>

      <ConfirmDeleteModal
        show={confirmDelete}
        title="Delete contract?"
        message={`"${contract.title}" (${contract.contract_code}) will be soft-deleted.`}
        loading={deleting}
        onCancel={() => setConfirmDelete(false)}
        onConfirm={doDelete}
      />
    </>
  );
}