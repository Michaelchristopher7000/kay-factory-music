import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import api from '../../api';

const STATUSES = [
  { value: 'draft', label: 'Draft' },
  { value: 'issued', label: 'Issued' },
  { value: 'paid', label: 'Paid' },
  { value: 'void', label: 'Void' },
];

const CURRENCIES = ['NGN', 'USD', 'ZAR', 'GBP', 'EUR'];

const emptyForm = {
  artist_id: '',
  period_start: '',
  period_end: '',
  currency: 'NGN',
  royalty_rate: '',
  generate: true,
  notes: '',
  // edit-only
  status: 'draft',
  issued_at: '',
  regenerate: false,
};

export default function RoyaltyStatementFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = Boolean(id);

  const [form, setForm] = useState(emptyForm);
  const [artists, setArtists] = useState([]);
  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');
  const [currentStatus, setCurrentStatus] = useState('draft');

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get('/artists', { params: { per_page: 100 } });
        setArtists(data.data || []);
      } catch {}
    })();
  }, []);

  useEffect(() => {
    if (!isEdit) return;

    (async () => {
      try {
        const { data } = await api.get(`/royalty-statements/${id}`);
        const s = data.data;

        setForm({
          artist_id: s.artist_id || '',
          period_start: s.period_start || '',
          period_end: s.period_end || '',
          currency: s.currency || 'NGN',
          royalty_rate: s.royalty_rate ?? '',
          generate: false,
          notes: s.notes || '',
          status: s.status || 'draft',
          issued_at: s.issued_at || '',
          regenerate: false,
        });

        setCurrentStatus(s.status);
      } catch (err) {
        if (err.response?.status === 401) return;
        setGlobalError('Could not load statement.');
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

    let payload;

    if (isEdit) {
      payload = {
        royalty_rate: form.royalty_rate === '' ? undefined : Number(form.royalty_rate),
        status: form.status,
        issued_at: form.issued_at || null,
        notes: form.notes || null,
        regenerate: Boolean(form.regenerate),
      };
    } else {
      payload = {
        artist_id: Number(form.artist_id),
        period_start: form.period_start,
        period_end: form.period_end,
        currency: form.currency,
        royalty_rate: form.royalty_rate === '' ? undefined : Number(form.royalty_rate),
        generate: Boolean(form.generate),
        notes: form.notes || null,
      };
    }

    try {
      if (isEdit) {
        await api.patch(`/royalty-statements/${id}`, payload);
      } else {
        await api.post('/royalty-statements', payload);
      }
      navigate('/royalty-statements');
    } catch (err) {
      if (err.response?.status === 401) return;
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
        if (err.response.data.errors?.status) {
          setGlobalError(err.response.data.errors.status[0]);
        }
      } else {
        setGlobalError(err.response?.data?.message || 'Could not save statement.');
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
  const isDraft = currentStatus === 'draft';

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <Link to="/royalty-statements" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Statements
          </Link>
          <h4 className="mb-0 mt-2">{isEdit ? 'Edit Statement' : 'New Statement'}</h4>
        </div>
      </div>

      {globalError && <div className="alert alert-danger">{globalError}</div>}

      {isEdit && !isDraft && (
        <div className="alert alert-warning">
          <i className="bi bi-lock me-2"></i>
          This statement is <strong>{currentStatus}</strong>. Lines are locked and cannot be regenerated.
        </div>
      )}

      <form onSubmit={submit}>
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>{isEdit ? 'Header' : 'Statement Setup'}</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label">Artist *</label>
                <select
                  className={`form-select ${fieldError('artist_id') ? 'is-invalid' : ''}`}
                  value={form.artist_id}
                  onChange={(e) => set('artist_id', e.target.value)}
                  required
                  disabled={isEdit}
                >
                  <option value="">— Select artist —</option>
                  {artists.map((a) => (
                    <option key={a.id} value={a.id}>{a.artist_code} — {a.name}</option>
                  ))}
                </select>
                {fieldError('artist_id') && <div className="invalid-feedback">{fieldError('artist_id')}</div>}
                {isEdit && <div className="form-text">Artist cannot be changed after creation.</div>}
              </div>

              <div className="col-md-3">
                <label className="form-label">Period Start *</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('period_start') ? 'is-invalid' : ''}`}
                  value={form.period_start}
                  onChange={(e) => set('period_start', e.target.value)}
                  required
                  disabled={isEdit}
                />
                {fieldError('period_start') && <div className="invalid-feedback">{fieldError('period_start')}</div>}
              </div>

              <div className="col-md-3">
                <label className="form-label">Period End *</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('period_end') ? 'is-invalid' : ''}`}
                  value={form.period_end}
                  onChange={(e) => set('period_end', e.target.value)}
                  required
                  disabled={isEdit}
                />
                {fieldError('period_end') && <div className="invalid-feedback">{fieldError('period_end')}</div>}
              </div>

              <div className="col-md-3">
                <label className="form-label">Currency *</label>
                <select
                  className={`form-select ${fieldError('currency') ? 'is-invalid' : ''}`}
                  value={form.currency}
                  onChange={(e) => set('currency', e.target.value)}
                  required
                  disabled={isEdit}
                >
                  {CURRENCIES.map((c) => <option key={c} value={c}>{c}</option>)}
                </select>
                {fieldError('currency') && <div className="invalid-feedback">{fieldError('currency')}</div>}
                {isEdit && <div className="form-text">Currency cannot be changed.</div>}
              </div>

              <div className="col-md-3">
                <label className="form-label">Royalty Rate (%)</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  className={`form-control ${fieldError('royalty_rate') ? 'is-invalid' : ''}`}
                  value={form.royalty_rate}
                  onChange={(e) => set('royalty_rate', e.target.value)}
                  placeholder="Leave blank to use active contract rate"
                  disabled={isEdit && !isDraft}
                />
                {fieldError('royalty_rate') && <div className="invalid-feedback">{fieldError('royalty_rate')}</div>}
              </div>

              {isEdit && (
                <>
                  <div className="col-md-3">
                    <label className="form-label">Status</label>
                    <select
                      className={`form-select ${fieldError('status') ? 'is-invalid' : ''}`}
                      value={form.status}
                      onChange={(e) => set('status', e.target.value)}
                      disabled={!isDraft && form.status !== 'paid' && form.status !== 'void'}
                    >
                      {STATUSES.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
                    </select>
                    {fieldError('status') && <div className="invalid-feedback">{fieldError('status')}</div>}
                  </div>

                  <div className="col-md-3">
                    <label className="form-label">Issued At</label>
                    <input
                      type="date"
                      className="form-control"
                      value={form.issued_at}
                      onChange={(e) => set('issued_at', e.target.value)}
                    />
                  </div>
                </>
              )}

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

            {!isEdit && (
              <div className="form-check mt-3">
                <input
                  type="checkbox"
                  className="form-check-input"
                  id="generate"
                  checked={form.generate}
                  onChange={(e) => set('generate', e.target.checked)}
                />
                <label htmlFor="generate" className="form-check-label">
                  Auto-generate lines from revenue entries
                </label>
                <div className="form-text">
                  Lines are created by matching revenue entries that fall within the period, use the same currency, belong to the artist, and aren't already in a non-void statement.
                </div>
              </div>
            )}

            {isEdit && isDraft && (
              <div className="form-check mt-3">
                <input
                  type="checkbox"
                  className="form-check-input"
                  id="regenerate"
                  checked={form.regenerate}
                  onChange={(e) => set('regenerate', e.target.checked)}
                />
                <label htmlFor="regenerate" className="form-check-label">
                  Regenerate lines using current rate
                </label>
                <div className="form-text">
                  Deletes existing draft lines and re-computes from revenue entries using the rate above.
                </div>
              </div>
            )}
          </div>
        </div>

        <div className="d-flex justify-content-end gap-2 mb-4">
          <Link to="/royalty-statements" className="btn btn-outline-secondary">Cancel</Link>
          <button type="submit" className="btn btn-dark" disabled={submitting}>
            {submitting ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Statement')}
          </button>
        </div>
      </form>
    </>
  );
}