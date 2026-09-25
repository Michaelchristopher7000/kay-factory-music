import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import api from '../../api';

const METHODS = [
  { value: 'bank_transfer', label: 'Bank Transfer' },
  { value: 'cash', label: 'Cash' },
  { value: 'cheque', label: 'Cheque' },
  { value: 'other', label: 'Other' },
];

const emptyForm = {
  statement_id: '',
  amount: '',
  currency: 'NGN',
  paid_at: '',
  method: 'bank_transfer',
  reference: '',
  notes: '',
};

export default function RoyaltyPaymentFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = Boolean(id);

  const [form, setForm] = useState(emptyForm);
  const [statements, setStatements] = useState([]);
  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');
  const [selectedStatement, setSelectedStatement] = useState(null);

  // Load statements — only issued/paid for new payments
  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get('/royalty-statements', { params: { per_page: 100 } });
        const eligible = (data.data || []).filter((s) => ['issued', 'paid'].includes(s.status));
        setStatements(eligible);
      } catch {}
    })();
  }, []);

  // Load existing payment for edit
  useEffect(() => {
    if (!isEdit) return;

    (async () => {
      try {
        const { data } = await api.get(`/royalty-payments/${id}`);
        const p = data.data;

        setForm({
          statement_id: p.statement_id || '',
          amount: p.amount ?? '',
          currency: p.currency || 'NGN',
          paid_at: p.paid_at || '',
          method: p.method || 'bank_transfer',
          reference: p.reference || '',
          notes: p.notes || '',
        });
      } catch (err) {
        if (err.response?.status === 401) return;
        setGlobalError('Could not load payment.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id, isEdit]);

  // When statement changes, sync currency
  useEffect(() => {
    if (!form.statement_id) {
      setSelectedStatement(null);
      return;
    }
    const s = statements.find((x) => String(x.id) === String(form.statement_id));
    setSelectedStatement(s || null);
    if (s && !isEdit) {
      setForm((f) => ({ ...f, currency: s.currency }));
    }
  }, [form.statement_id, statements, isEdit]);

  const set = (field, value) => setForm((f) => ({ ...f, [field]: value }));

  const submit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});
    setGlobalError('');

    const payload = isEdit
      ? {
          amount: Number(form.amount),
          paid_at: form.paid_at,
          method: form.method,
          reference: form.reference || null,
          notes: form.notes || null,
        }
      : {
          statement_id: Number(form.statement_id),
          amount: Number(form.amount),
          currency: form.currency,
          paid_at: form.paid_at,
          method: form.method,
          reference: form.reference || null,
          notes: form.notes || null,
        };

    try {
      if (isEdit) {
        await api.patch(`/royalty-payments/${id}`, payload);
      } else {
        await api.post('/royalty-payments', payload);
      }
      navigate('/royalty-payments');
    } catch (err) {
      if (err.response?.status === 401) return;
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
        if (err.response.data.errors?.currency) {
          setGlobalError(err.response.data.errors.currency[0]);
        }
      } else {
        setGlobalError(err.response?.data?.message || 'Could not save payment.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="text-center py-5 text-muted">
        <div className="spinner-border spinner-border-sm me-2"></div>Loading…
      </div>
    );
  }

  const fieldError = (key) => errors[key]?.[0];

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <Link to="/royalty-payments" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Payments
          </Link>
          <h4 className="mb-0 mt-2">{isEdit ? 'Edit Payment' : 'Record Payment'}</h4>
        </div>
      </div>

      {globalError && <div className="alert alert-danger">{globalError}</div>}

      <form onSubmit={submit}>
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Payment</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label">Statement *</label>
                <select
                  className={`form-select ${fieldError('statement_id') ? 'is-invalid' : ''}`}
                  value={form.statement_id}
                  onChange={(e) => set('statement_id', e.target.value)}
                  required
                  disabled={isEdit}
                >
                  <option value="">— Select statement —</option>
                  {statements.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.statement_code} — {s.artist?.name} ({s.currency} {Number(s.balance).toLocaleString()})
                    </option>
                  ))}
                </select>
                {fieldError('statement_id') && <div className="invalid-feedback">{fieldError('statement_id')}</div>}
                {isEdit && <div className="form-text">Statement cannot be changed after creation.</div>}
              </div>

              {selectedStatement && (
                <div className="col-md-6">
                  <label className="form-label">Balance on Statement</label>
                  <div className="form-control bg-light">
                    {selectedStatement.currency} {Number(selectedStatement.balance).toLocaleString()}
                  </div>
                </div>
              )}

              <div className="col-md-4">
                <label className="form-label">Amount *</label>
                <input
                  type="number"
                  step="0.01"
                  min="0.01"
                  className={`form-control ${fieldError('amount') ? 'is-invalid' : ''}`}
                  value={form.amount}
                  onChange={(e) => set('amount', e.target.value)}
                  required
                />
                {fieldError('amount') && <div className="invalid-feedback">{fieldError('amount')}</div>}
              </div>

              <div className="col-md-4">
                <label className="form-label">Currency *</label>
                <input
                  type="text"
                  className="form-control"
                  value={form.currency}
                  readOnly
                  disabled
                />
                <div className="form-text">Must match the statement currency.</div>
              </div>

              <div className="col-md-4">
                <label className="form-label">Method *</label>
                <select
                  className={`form-select ${fieldError('method') ? 'is-invalid' : ''}`}
                  value={form.method}
                  onChange={(e) => set('method', e.target.value)}
                  required
                >
                  {METHODS.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
                </select>
                {fieldError('method') && <div className="invalid-feedback">{fieldError('method')}</div>}
              </div>

              <div className="col-md-4">
                <label className="form-label">Paid At *</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('paid_at') ? 'is-invalid' : ''}`}
                  value={form.paid_at}
                  onChange={(e) => set('paid_at', e.target.value)}
                  required
                />
                {fieldError('paid_at') && <div className="invalid-feedback">{fieldError('paid_at')}</div>}
              </div>

              <div className="col-md-8">
                <label className="form-label">Reference</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('reference') ? 'is-invalid' : ''}`}
                  value={form.reference}
                  onChange={(e) => set('reference', e.target.value)}
                  placeholder="Transfer ref, cheque number, etc."
                />
                {fieldError('reference') && <div className="invalid-feedback">{fieldError('reference')}</div>}
              </div>

              <div className="col-12">
                <label className="form-label">Notes</label>
                <textarea
                  rows={3}
                  className={`form-control ${fieldError('notes') ? 'is-invalid' : ''}`}
                  value={form.notes}
                  onChange={(e) => set('notes', e.target.value)}
                />
                {fieldError('notes') && <div className="invalid-feedback">{fieldError('notes')}</div>}
              </div>
            </div>
          </div>
        </div>

        <div className="d-flex justify-content-end gap-2 mb-4">
          <Link to="/royalty-payments" className="btn btn-outline-secondary">Cancel</Link>
          <button type="submit" className="btn btn-dark" disabled={submitting}>
            {submitting ? 'Saving…' : (isEdit ? 'Save Changes' : 'Record Payment')}
          </button>
        </div>
      </form>
    </>
  );
}