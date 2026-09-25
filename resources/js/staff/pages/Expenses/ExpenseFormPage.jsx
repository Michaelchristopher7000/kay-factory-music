import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import api from '../../api';

const CATEGORIES = [
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

const CURRENCIES = ['NGN', 'USD', 'ZAR', 'GBP', 'EUR'];

const emptyForm = {
  category: 'studio',
  artist_id: '',
  release_id: '',
  track_id: '',
  distribution_id: '',
  amount: '',
  currency: 'NGN',
  incurred_at: '',
  paid_at: '',
  reference: '',
  description: '',
  notes: '',
};

export default function ExpenseFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = Boolean(id);

  const [form, setForm] = useState(emptyForm);
  const [artists, setArtists] = useState([]);
  const [releases, setReleases] = useState([]);
  const [tracks, setTracks] = useState([]);
  const [distributions, setDistributions] = useState([]);
  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');

  useEffect(() => {
    (async () => {
      try {
        const [a, r, t, d] = await Promise.all([
          api.get('/artists', { params: { per_page: 100 } }),
          api.get('/releases', { params: { per_page: 100 } }),
          api.get('/tracks', { params: { per_page: 100 } }),
          api.get('/distributions', { params: { per_page: 100 } }),
        ]);
        setArtists(a.data.data || []);
        setReleases(r.data.data || []);
        setTracks(t.data.data || []);
        setDistributions(d.data.data || []);
      } catch {
        // silent
      }
    })();
  }, []);

  useEffect(() => {
    if (!isEdit) return;

    (async () => {
      try {
        const { data } = await api.get(`/expenses/${id}`);
        const x = data.data;

        setForm({
          category: x.category || 'studio',
          artist_id: x.artist_id || '',
          release_id: x.release_id || '',
          track_id: x.track_id || '',
          distribution_id: x.distribution_id || '',
          amount: x.amount ?? '',
          currency: x.currency || 'NGN',
          incurred_at: x.incurred_at || '',
          paid_at: x.paid_at || '',
          reference: x.reference || '',
          description: x.description || '',
          notes: x.notes || '',
        });
      } catch (err) {
        if (err.response?.status === 401) return;
        setGlobalError('Could not load expense.');
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
      category: form.category,
      artist_id: form.artist_id ? Number(form.artist_id) : null,
      release_id: form.release_id ? Number(form.release_id) : null,
      track_id: form.track_id ? Number(form.track_id) : null,
      distribution_id: form.distribution_id ? Number(form.distribution_id) : null,
      amount: Number(form.amount),
      currency: form.currency,
      incurred_at: form.incurred_at || null,
      paid_at: form.paid_at || null,
      reference: form.reference || null,
      description: form.description || null,
      notes: form.notes || null,
    };

    try {
      if (isEdit) {
        await api.patch(`/expenses/${id}`, payload);
      } else {
        await api.post('/expenses', payload);
      }
      navigate('/expenses');
    } catch (err) {
      if (err.response?.status === 401) return;

      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setGlobalError(err.response?.data?.message || 'Could not save expense.');
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
          <Link to="/expenses" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Expenses
          </Link>
          <h4 className="mb-0 mt-2">{isEdit ? 'Edit Expense' : 'New Expense'}</h4>
        </div>
      </div>

      {globalError && <div className="alert alert-danger">{globalError}</div>}

      <form onSubmit={submit}>
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Expense</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-4">
                <label className="form-label">Category *</label>
                <select
                  className={`form-select ${fieldError('category') ? 'is-invalid' : ''}`}
                  value={form.category}
                  onChange={(e) => set('category', e.target.value)}
                  required
                >
                  {CATEGORIES.map((c) => (
                    <option key={c.value} value={c.value}>{c.label}</option>
                  ))}
                </select>
                {fieldError('category') && <div className="invalid-feedback">{fieldError('category')}</div>}
              </div>

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
                <select
                  className={`form-select ${fieldError('currency') ? 'is-invalid' : ''}`}
                  value={form.currency}
                  onChange={(e) => set('currency', e.target.value)}
                  required
                >
                  {CURRENCIES.map((c) => (
                    <option key={c} value={c}>{c}</option>
                  ))}
                </select>
                {fieldError('currency') && <div className="invalid-feedback">{fieldError('currency')}</div>}
              </div>

              <div className="col-md-4">
                <label className="form-label">Incurred At</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('incurred_at') ? 'is-invalid' : ''}`}
                  value={form.incurred_at}
                  onChange={(e) => set('incurred_at', e.target.value)}
                />
                {fieldError('incurred_at') && <div className="invalid-feedback">{fieldError('incurred_at')}</div>}
              </div>

              <div className="col-md-4">
                <label className="form-label">Paid At</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('paid_at') ? 'is-invalid' : ''}`}
                  value={form.paid_at}
                  onChange={(e) => set('paid_at', e.target.value)}
                />
                {fieldError('paid_at') && <div className="invalid-feedback">{fieldError('paid_at')}</div>}
              </div>

              <div className="col-md-4">
                <label className="form-label">Reference</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('reference') ? 'is-invalid' : ''}`}
                  value={form.reference}
                  onChange={(e) => set('reference', e.target.value)}
                  placeholder="Invoice or receipt number"
                />
                {fieldError('reference') && <div className="invalid-feedback">{fieldError('reference')}</div>}
              </div>

              <div className="col-12">
                <label className="form-label">Description</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('description') ? 'is-invalid' : ''}`}
                  value={form.description}
                  onChange={(e) => set('description', e.target.value)}
                  placeholder="Short description of the expense"
                />
                {fieldError('description') && <div className="invalid-feedback">{fieldError('description')}</div>}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Attribution (all optional)</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-3">
                <label className="form-label">Artist</label>
                <select
                  className="form-select"
                  value={form.artist_id}
                  onChange={(e) => set('artist_id', e.target.value)}
                >
                  <option value="">— None —</option>
                  {artists.map((a) => (
                    <option key={a.id} value={a.id}>{a.name}</option>
                  ))}
                </select>
              </div>

              <div className="col-md-3">
                <label className="form-label">Release</label>
                <select
                  className="form-select"
                  value={form.release_id}
                  onChange={(e) => set('release_id', e.target.value)}
                >
                  <option value="">— None —</option>
                  {releases.map((r) => (
                    <option key={r.id} value={r.id}>{r.title}</option>
                  ))}
                </select>
              </div>

              <div className="col-md-3">
                <label className="form-label">Track</label>
                <select
                  className="form-select"
                  value={form.track_id}
                  onChange={(e) => set('track_id', e.target.value)}
                >
                  <option value="">— None —</option>
                  {tracks.map((t) => (
                    <option key={t.id} value={t.id}>{t.title}</option>
                  ))}
                </select>
              </div>

              <div className="col-md-3">
                <label className="form-label">Distribution</label>
                <select
                  className="form-select"
                  value={form.distribution_id}
                  onChange={(e) => set('distribution_id', e.target.value)}
                >
                  <option value="">— None —</option>
                  {distributions.map((d) => (
                    <option key={d.id} value={d.id}>
                      {d.distribution_code} ({d.platform})
                    </option>
                  ))}
                </select>
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
          <Link to="/expenses" className="btn btn-outline-secondary">Cancel</Link>
          <button type="submit" className="btn btn-dark" disabled={submitting}>
            {submitting ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Expense')}
          </button>
        </div>
      </form>
    </>
  );
}