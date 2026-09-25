import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import {
  canUpdateRoyaltyStatement,
  canDeleteRoyaltyStatement,
  canRegenerateRoyaltyStatement,
  canEditStatementLines,
} from '../../permissions';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';
import LineEditModal from './LineEditModal';

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

export default function RoyaltyStatementViewPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';

  const [statement, setStatement] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [regenerating, setRegenerating] = useState(false);
  const [editingLine, setEditingLine] = useState(null);
  const [savingLine, setSavingLine] = useState(false);

  const load = async () => {
    try {
      const { data } = await api.get(`/royalty-statements/${id}`);
      setStatement(data.data);
    } catch (err) {
      if (err.response?.status === 401) return;
      if (err.response?.status === 404) setError('Statement not found.');
      else setError('Could not load statement.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { load(); /* eslint-disable-next-line */ }, [id]);

  const doDelete = async () => {
    setDeleting(true);
    try {
      await api.delete(`/royalty-statements/${id}`);
      navigate('/royalty-statements');
    } catch {
      alert('Could not delete statement.');
      setDeleting(false);
    }
  };

  const doRegenerate = async () => {
    if (!window.confirm('Regenerate lines? Existing draft lines will be replaced.')) return;
    setRegenerating(true);
    try {
      await api.post(`/royalty-statements/${id}/regenerate`);
      await load();
    } catch (err) {
      alert(err.response?.data?.message || 'Could not regenerate.');
    } finally {
      setRegenerating(false);
    }
  };

  const saveLine = async (payload) => {
    if (!editingLine) return;
    setSavingLine(true);
    try {
      await api.patch(`/royalty-statement-lines/${editingLine.id}`, payload);
      setEditingLine(null);
      await load();
    } catch (err) {
      alert(err.response?.data?.message || 'Could not save line.');
    } finally {
      setSavingLine(false);
    }
  };

  const deleteLine = async (line) => {
    if (!window.confirm('Delete this line?')) return;
    try {
      await api.delete(`/royalty-statement-lines/${line.id}`);
      await load();
    } catch (err) {
      alert(err.response?.data?.message || 'Could not delete line.');
    }
  };

  if (loading) {
    return (
      <div className="text-center py-5 text-muted">
        <div className="spinner-border spinner-border-sm me-2"></div>Loading…
      </div>
    );
  }

  if (error || !statement) {
    return (
      <>
        <Link to="/royalty-statements" className="text-muted text-decoration-none small">
          <i className="bi bi-arrow-left me-1"></i> Back to Statements
        </Link>
        <div className="alert alert-warning mt-3">{error || 'Statement not found.'}</div>
      </>
    );
  }

  const isDraft = statement.status === 'draft';

  return (
    <>
      <div className="d-flex justify-content-between align-items-start mb-4">
        <div>
          <Link to="/royalty-statements" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Statements
          </Link>
          <div className="d-flex align-items-center gap-3 mt-2">
            <h4 className="mb-0">{statement.statement_code}</h4>
            <span className={statusBadge(statement.status)}>
              {(statement.status || '').replace(/_/g, ' ')}
            </span>
          </div>
          {statement.artist && (
            <p className="text-muted mb-0 small mt-1">
              Artist: <Link to={`/artists/${statement.artist.id}`}>{statement.artist.name}</Link>
              {' · '}
              {statement.period_start} → {statement.period_end}
              {' · '}
              {statement.currency}
            </p>
          )}
        </div>

        <div className="d-flex gap-2">
          {isDraft && canRegenerateRoyaltyStatement(roleSlug) && (
            <button
              type="button"
              className="btn btn-outline-secondary"
              onClick={doRegenerate}
              disabled={regenerating}
            >
              <i className="bi bi-arrow-clockwise me-2"></i>
              {regenerating ? 'Regenerating…' : 'Regenerate Lines'}
            </button>
          )}
          {canUpdateRoyaltyStatement(roleSlug) && (
            <Link to={`/royalty-statements/${statement.id}/edit`} className="btn btn-outline-secondary">
              <i className="bi bi-pencil me-2"></i> Edit
            </Link>
          )}
          {canDeleteRoyaltyStatement(roleSlug) && (
            <button
              type="button"
              className="btn btn-outline-danger"
              onClick={() => setConfirmDelete(true)}
            >
              <i className="bi bi-trash me-2"></i> Delete
            </button>
          )}
        </div>
      </div>

      <div className="row g-3 mb-3">
        <div className="col-md-3">
          <div className="card border-0 shadow-sm">
            <div className="card-body">
              <div className="text-muted small">Total Revenue</div>
              <div className="fw-semibold">{fmt(statement.total_revenue, statement.currency)}</div>
            </div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="card border-0 shadow-sm">
            <div className="card-body">
              <div className="text-muted small">Total Royalty</div>
              <div className="fw-semibold">{fmt(statement.total_royalty, statement.currency)}</div>
            </div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="card border-0 shadow-sm">
            <div className="card-body">
              <div className="text-muted small">Total Paid</div>
              <div className="fw-semibold">{fmt(statement.total_paid, statement.currency)}</div>
            </div>
          </div>
        </div>
        <div className="col-md-3">
          <div className="card border-0 shadow-sm">
            <div className="card-body">
              <div className="text-muted small">Balance</div>
              <div className={`fw-semibold ${Number(statement.balance) > 0 ? 'text-danger' : 'text-success'}`}>
                {fmt(statement.balance, statement.currency)}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="row g-3">
        <div className="col-lg-8">
          <div className="card border-0 shadow-sm mb-3">
            <div className="card-header bg-white d-flex justify-content-between align-items-center">
              <strong>Lines</strong>
              <span className="text-muted small">
                {Array.isArray(statement.lines) ? statement.lines.length : 0} lines
              </span>
            </div>
            <div className="table-responsive">
              <table className="table table-hover align-middle mb-0">
                <thead className="table-light">
                  <tr>
                    <th style={{ width: 120 }}>Source</th>
                    <th>Release / Track</th>
                    <th style={{ width: 130 }} className="text-end">Revenue</th>
                    <th style={{ width: 80 }} className="text-end">Rate</th>
                    <th style={{ width: 130 }} className="text-end">Royalty</th>
                    {isDraft && canEditStatementLines(roleSlug) && (
                      <th style={{ width: 100 }} className="text-end">Actions</th>
                    )}
                  </tr>
                </thead>
                <tbody>
                  {(!Array.isArray(statement.lines) || statement.lines.length === 0) && (
                    <tr>
                      <td colSpan={6} className="text-center py-4 text-muted">No lines.</td>
                    </tr>
                  )}
                  {Array.isArray(statement.lines) && statement.lines.map((line) => (
                    <tr key={line.id}>
                      <td className="text-capitalize small">{(line.source || '').replace(/_/g, ' ')}</td>
                      <td>
                        {line.release && (
                          <div className="small">
                            <Link to={`/releases/${line.release.id}`}>{line.release.title}</Link>
                          </div>
                        )}
                        {line.track && (
                          <div className="text-muted" style={{ fontSize: '0.72rem' }}>
                            {line.track.title}
                          </div>
                        )}
                        {!line.release && !line.track && <span className="text-muted small">—</span>}
                      </td>
                      <td className="text-end small">{Number(line.revenue_amount).toLocaleString()}</td>
                      <td className="text-end small">{line.royalty_rate}%</td>
                      <td className="text-end fw-semibold">{Number(line.royalty_amount).toLocaleString()}</td>
                      {isDraft && canEditStatementLines(roleSlug) && (
                        <td className="text-end">
                          <button
                            type="button"
                            className="btn btn-sm btn-outline-secondary me-1"
                            onClick={() => setEditingLine(line)}
                          >
                            <i className="bi bi-pencil"></i>
                          </button>
                          <button
                            type="button"
                            className="btn btn-sm btn-outline-danger"
                            onClick={() => deleteLine(line)}
                          >
                            <i className="bi bi-trash"></i>
                          </button>
                        </td>
                      )}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {statement.notes && (
            <div className="card border-0 shadow-sm">
              <div className="card-header bg-white"><strong>Notes</strong></div>
              <div className="card-body">
                <p className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{statement.notes}</p>
              </div>
            </div>
          )}
        </div>

        <div className="col-lg-4">
          <div className="card border-0 shadow-sm">
            <div className="card-header bg-white"><strong>Meta</strong></div>
            <div className="card-body">
              <dl className="row mb-0 small">
                <dt className="col-7 text-muted fw-normal">Currency</dt>
                <dd className="col-5 text-end">{statement.currency}</dd>
                <dt className="col-7 text-muted fw-normal">Royalty Rate</dt>
                <dd className="col-5 text-end">{statement.royalty_rate}%</dd>
                <dt className="col-7 text-muted fw-normal">Issued At</dt>
                <dd className="col-5 text-end">{statement.issued_at || '—'}</dd>
                <dt className="col-7 text-muted fw-normal">Created by</dt>
                <dd className="col-5 text-end">{statement.created_by?.name || '—'}</dd>
                <dt className="col-7 text-muted fw-normal">Created</dt>
                <dd className="col-5 text-end">
                  {statement.created_at ? new Date(statement.created_at).toLocaleDateString() : '—'}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <ConfirmDeleteModal
        show={confirmDelete}
        title="Delete statement?"
        message={`Statement ${statement.statement_code} will be soft-deleted.`}
        loading={deleting}
        onCancel={() => setConfirmDelete(false)}
        onConfirm={doDelete}
      />

      <LineEditModal
        show={!!editingLine}
        line={editingLine}
        saving={savingLine}
        onCancel={() => setEditingLine(null)}
        onSave={saveLine}
      />
    </>
  );
}