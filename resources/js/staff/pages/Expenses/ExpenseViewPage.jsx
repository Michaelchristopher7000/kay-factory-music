import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import { canUpdateExpense, canDeleteExpense } from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const fmt = (v, c) => `${c || 'NGN'} ${Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function ExpenseViewPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [expense, setExpense] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get(`/expenses/${id}`);
        setExpense(data.data);
      } catch (err) {
        if (err.response?.status === 401) return;
        if (err.response?.status === 404) setError('Expense not found.');
        else setError('Could not load expense.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id]);

  const doDelete = async () => {
    setDeleting(true);
    try {
      await api.delete(`/expenses/${id}`);
      navigate('/expenses');
    } catch {
      alert('Could not delete expense.');
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

  if (error || !expense) {
    return (
      <>
        <Link to="/expenses" className="text-muted text-decoration-none small">
          <i className="bi bi-arrow-left me-1"></i> Back to Expenses
        </Link>
        <div className="alert alert-warning mt-3">{error || 'Expense not found.'}</div>
      </>
    );
  }

  return (
    <>
      <div className="d-flex justify-content-between align-items-start mb-4">
        <div>
          <Link to="/expenses" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Expenses
          </Link>
          <div className="d-flex align-items-center gap-3 mt-2">
            <h4 className="mb-0">{expense.expense_code}</h4>
            <span className="badge bg-light text-dark border text-capitalize">
              {(expense.category || '').replace(/_/g, ' ')}
            </span>
          </div>
          {expense.description && (
            <p className="text-muted mb-0 small mt-1">{expense.description}</p>
          )}
        </div>

        <div className="d-flex gap-2">
          {canUpdateExpense(roleSlug) && (
            <Link to={`/expenses/${expense.id}/edit`} className="btn btn-outline-secondary">
              <i className="bi bi-pencil me-2"></i>
              Edit
            </Link>
          )}
          {canDeleteExpense(roleSlug) && (
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
                <dt className="col-sm-4 text-muted fw-normal">Amount</dt>
                <dd className="col-sm-8 fw-semibold">{fmt(expense.amount, expense.currency)}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Currency</dt>
                <dd className="col-sm-8">{expense.currency}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Incurred At</dt>
                <dd className="col-sm-8">{expense.incurred_at || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Paid At</dt>
                <dd className="col-sm-8">{expense.paid_at || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Reference</dt>
                <dd className="col-sm-8">{expense.reference || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Artist</dt>
                <dd className="col-sm-8">
                  {expense.artist ? <Link to={`/artists/${expense.artist.id}`}>{expense.artist.name}</Link> : '—'}
                </dd>

                <dt className="col-sm-4 text-muted fw-normal">Release</dt>
                <dd className="col-sm-8">
                  {expense.release ? <Link to={`/releases/${expense.release.id}`}>{expense.release.title}</Link> : '—'}
                </dd>

                <dt className="col-sm-4 text-muted fw-normal">Track</dt>
                <dd className="col-sm-8">
                  {expense.track ? <Link to={`/tracks/${expense.track.id}`}>{expense.track.title}</Link> : '—'}
                </dd>

                <dt className="col-sm-4 text-muted fw-normal">Distribution</dt>
                <dd className="col-sm-8">
                  {expense.distribution ? (
                    <Link to={`/distributions/${expense.distribution.id}`}>
                      {expense.distribution.distribution_code}
                    </Link>
                  ) : (
                    '—'
                  )}
                </dd>

                <dt className="col-sm-4 text-muted fw-normal">Added by</dt>
                <dd className="col-sm-8">{expense.created_by?.name || '—'}</dd>

                <dt className="col-sm-4 text-muted fw-normal">Added on</dt>
                <dd className="col-sm-8">
                  {expense.created_at ? new Date(expense.created_at).toLocaleString() : '—'}
                </dd>
              </dl>
            </div>
          </div>

          {expense.notes && (
            <div className="card border-0 shadow-sm">
              <div className="card-header bg-white"><strong>Notes</strong></div>
              <div className="card-body">
                <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{expense.notes}</p>
              </div>
            </div>
          )}
        </div>
      </div>

      <ConfirmDeleteModal
        show={confirmDelete}
        title="Delete expense?"
        message={`Expense ${expense.expense_code} will be soft-deleted.`}
        loading={deleting}
        onCancel={() => setConfirmDelete(false)}
        onConfirm={doDelete}
      />
    </>
  );
}