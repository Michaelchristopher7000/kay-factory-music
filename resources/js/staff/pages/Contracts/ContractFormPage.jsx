import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import api from '../../api';

const TYPES = [
  { value: 'artist_agreement', label: 'Artist Agreement' },
  { value: 'recording_agreement', label: 'Recording Agreement' },
  { value: 'distribution_agreement', label: 'Distribution Agreement' },
  { value: 'management_agreement', label: 'Management Agreement' },
  { value: 'publishing_agreement', label: 'Publishing Agreement' },
  { value: 'licensing_agreement', label: 'Licensing Agreement' },
  { value: 'other', label: 'Other' },
];

const STATUSES = [
  { value: 'draft', label: 'Draft' },
  { value: 'pending_signature', label: 'Pending Signature' },
  { value: 'active', label: 'Active' },
  { value: 'expired', label: 'Expired' },
  { value: 'terminated', label: 'Terminated' },
  { value: 'cancelled', label: 'Cancelled' },
];

const CURRENCIES = ['NGN', 'USD', 'ZAR', 'GBP', 'EUR'];

const emptyForm = {
  artist_id: '',
  title: '',
  type: 'recording_agreement',
  status: 'draft',
  start_date: '',
  end_date: '',
  signed_date: '',
  advance_amount: '',
  currency: 'NGN',
  royalty_rate: '',
  terms: '',
  document_path: '',
};

export default function ContractFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = Boolean(id);

  const [form, setForm] = useState(emptyForm);
  const [artists, setArtists] = useState([]);
  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');

  // Fetch artists for the dropdown
  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get('/artists', { params: { per_page: 100 } });
        setArtists(data.data || []);
      } catch {
        // silent — dropdown will be empty
      }
    })();
  }, []);

  // Fetch existing contract for edit
  useEffect(() => {
    if (!isEdit) return;

    (async () => {
      try {
        const { data } = await api.get(`/contracts/${id}`);
        const c = data.data;

        setForm({
          artist_id: c.artist_id || '',
          title: c.title || '',
          type: c.type || 'recording_agreement',
          status: c.status || 'draft',
          start_date: c.start_date || '',
          end_date: c.end_date || '',
          signed_date: c.signed_date || '',
          advance_amount: c.advance_amount || '',
          currency: c.currency || 'NGN',
          royalty_rate: c.royalty_rate || '',
          terms: c.terms || '',
          document_path: c.document_path || '',
        });
      } catch (err) {
        if (err.response?.status === 401) return;
        setGlobalError('Could not load contract.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id, isEdit]);

  const set = (field, value) => setForm((f) => ({ ...f, [field]: value }));

  const submit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});
    setGlobalError('');

    const payload = {
      artist_id: form.artist_id ? Number(form.artist_id) : null,
      title: form.title,
      type: form.type,
      status: form.status,
      start_date: form.start_date || null,
      end_date: form.end_date || null,
      signed_date: form.signed_date || null,
      advance_amount: form.advance_amount === '' ? null : Number(form.advance_amount),
      currency: form.currency || null,
      royalty_rate: form.royalty_rate === '' ? null : Number(form.royalty_rate),
      terms: form.terms || null,
      document_path: form.document_path || null,
    };

    try {
      if (isEdit) {
        await api.patch(`/contracts/${id}`, payload);
      } else {
        await api.post('/contracts', payload);
      }
      navigate('/contracts');
    } catch (err) {
      if (err.response?.status === 401) return;

      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setGlobalError(err.response?.data?.message || 'Could not save contract.');
      }
    } finally {
      setSubmitting(false);
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

  const fieldError = (key) => errors[key]?.[0];

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <Link to="/contracts" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Contracts
          </Link>
          <h4 className="mb-0 mt-2">{isEdit ? 'Edit Contract' : 'New Contract'}</h4>
        </div>
      </div>

      {globalError && <div className="alert alert-danger">{globalError}</div>}

      <form onSubmit={submit}>
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Contract Details</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label">Artist *</label>
                <select
                  className={`form-select ${fieldError('artist_id') ? 'is-invalid' : ''}`}
                  value={form.artist_id}
                  onChange={(e) => set('artist_id', e.target.value)}
                  required
                >
                  <option value="">— Select artist —</option>
                  {artists.map((a) => (
                    <option key={a.id} value={a.id}>
                      {a.artist_code} — {a.name}
                    </option>
                  ))}
                </select>
                {fieldError('artist_id') && (
                  <div className="invalid-feedback">{fieldError('artist_id')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">Title *</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('title') ? 'is-invalid' : ''}`}
                  value={form.title}
                  onChange={(e) => set('title', e.target.value)}
                  placeholder="e.g. Exclusive Recording Agreement 2026"
                  required
                />
                {fieldError('title') && (
                  <div className="invalid-feedback">{fieldError('title')}</div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">Type *</label>
                <select
                  className={`form-select ${fieldError('type') ? 'is-invalid' : ''}`}
                  value={form.type}
                  onChange={(e) => set('type', e.target.value)}
                  required
                >
                  {TYPES.map((t) => (
                    <option key={t.value} value={t.value}>{t.label}</option>
                  ))}
                </select>
                {fieldError('type') && (
                  <div className="invalid-feedback">{fieldError('type')}</div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">Status *</label>
                <select
                  className={`form-select ${fieldError('status') ? 'is-invalid' : ''}`}
                  value={form.status}
                  onChange={(e) => set('status', e.target.value)}
                  required
                >
                  {STATUSES.map((s) => (
                    <option key={s.value} value={s.value}>{s.label}</option>
                  ))}
                </select>
                {fieldError('status') && (
                  <div className="invalid-feedback">{fieldError('status')}</div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">Signed Date</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('signed_date') ? 'is-invalid' : ''}`}
                  value={form.signed_date}
                  onChange={(e) => set('signed_date', e.target.value)}
                />
                {fieldError('signed_date') && (
                  <div className="invalid-feedback">{fieldError('signed_date')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">Start Date</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('start_date') ? 'is-invalid' : ''}`}
                  value={form.start_date}
                  onChange={(e) => set('start_date', e.target.value)}
                />
                {fieldError('start_date') && (
                  <div className="invalid-feedback">{fieldError('start_date')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">End Date</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('end_date') ? 'is-invalid' : ''}`}
                  value={form.end_date}
                  onChange={(e) => set('end_date', e.target.value)}
                />
                {fieldError('end_date') && (
                  <div className="invalid-feedback">{fieldError('end_date')}</div>
                )}
              </div>

              <div className="col-12">
                <label className="form-label">Terms</label>
                <textarea
                  rows={5}
                  className={`form-control ${fieldError('terms') ? 'is-invalid' : ''}`}
                  value={form.terms}
                  onChange={(e) => set('terms', e.target.value)}
                  placeholder="Key contract terms, notes, or summary"
                />
                {fieldError('terms') && (
                  <div className="invalid-feedback">{fieldError('terms')}</div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Financials</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-4">
                <label className="form-label">Advance Amount</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  className={`form-control ${fieldError('advance_amount') ? 'is-invalid' : ''}`}
                  value={form.advance_amount}
                  onChange={(e) => set('advance_amount', e.target.value)}
                  placeholder="0.00"
                />
                {fieldError('advance_amount') && (
                  <div className="invalid-feedback">{fieldError('advance_amount')}</div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">Currency</label>
                <select
                  className={`form-select ${fieldError('currency') ? 'is-invalid' : ''}`}
                  value={form.currency}
                  onChange={(e) => set('currency', e.target.value)}
                >
                  {CURRENCIES.map((c) => (
                    <option key={c} value={c}>{c}</option>
                  ))}
                </select>
                {fieldError('currency') && (
                  <div className="invalid-feedback">{fieldError('currency')}</div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">Royalty Rate (%)</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  className={`form-control ${fieldError('royalty_rate') ? 'is-invalid' : ''}`}
                  value={form.royalty_rate}
                  onChange={(e) => set('royalty_rate', e.target.value)}
                  placeholder="0.00"
                />
                {fieldError('royalty_rate') && (
                  <div className="invalid-feedback">{fieldError('royalty_rate')}</div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Document</strong></div>
          <div className="card-body">
            <label className="form-label">Document Path (optional)</label>
            <input
              type="text"
              className={`form-control ${fieldError('document_path') ? 'is-invalid' : ''}`}
              value={form.document_path}
              onChange={(e) => set('document_path', e.target.value)}
              placeholder="Path or URL to the contract PDF"
            />
            {fieldError('document_path') && (
              <div className="invalid-feedback">{fieldError('document_path')}</div>
            )}
            <div className="form-text">
              File upload is not yet supported. Store a reference string for now.
            </div>
          </div>
        </div>

        <div className="d-flex justify-content-end gap-2 mb-4">
          <Link to="/contracts" className="btn btn-outline-secondary">Cancel</Link>
          <button type="submit" className="btn btn-dark" disabled={submitting}>
            {submitting ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Contract')}
          </button>
        </div>
      </form>
    </>
  );
}