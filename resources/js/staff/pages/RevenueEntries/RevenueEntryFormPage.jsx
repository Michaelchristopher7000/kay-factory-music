import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import api from '../../api';

const SOURCES = [
  { value: 'streaming', label: 'Streaming' },
  { value: 'sync', label: 'Sync' },
  { value: 'physical', label: 'Physical' },
  { value: 'youtube_content_id', label: 'YouTube Content ID' },
  { value: 'publishing', label: 'Publishing' },
  { value: 'merch', label: 'Merch' },
  { value: 'other', label: 'Other' },
];

const CURRENCIES = ['NGN', 'USD', 'ZAR', 'GBP', 'EUR'];

const emptyForm = {
  source: 'streaming',
  platform: '',
  artist_id: '',
  release_id: '',
  track_id: '',
  distribution_id: '',
  amount: '',
  currency: 'NGN',
  period_start: '',
  period_end: '',
  received_at: '',
  reference: '',
  notes: '',
};

export default function RevenueEntryFormPage() {
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

  // Load reference data
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
        // silent — dropdowns just stay empty
      }
    })();
  }, []);

  // Load existing for edit
  useEffect(() => {
    if (!isEdit) return;

    (async () => {
      try {
        const { data } = await api.get(`/revenue-entries/${id}`);
        const e = data.data;

        setForm({
          source: e.source || 'streaming',
          platform: e.platform || '',
          artist_id: e.artist_id || '',
          release_id: e.release_id || '',
          track_id: e.track_id || '',
          distribution_id: e.distribution_id || '',
          amount: e.amount ?? '',
          currency: e.currency || 'NGN',
          period_start: e.period_start || '',
          period_end: e.period_end || '',
          received_at: e.received_at || '',
          reference: e.reference || '',
          notes: e.notes || '',
        });
      } catch (err) {
        if (err.response?.status === 401) return;
        setGlobalError('Could not load revenue entry.');
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
      source: form.source,
      platform: form.platform || null,
      artist_id: form.artist_id ? Number(form.artist_id) : null,
      release_id: form.release_id ? Number(form.release_id) : null,
      track_id: form.track_id ? Number(form.track_id) : null,
      distribution_id: form.distribution_id ? Number(form.distribution_id) : null,
      amount: Number(form.amount),
      currency: form.currency,
      period_start: form.period_start || null,
      period_end: form.period_end || null,
      received_at: form.received_at || null,
      reference: form.reference || null,
      notes: form.notes || null,
    };

    try {
      if (isEdit) {
        await api.patch(`/revenue-entries/${id}`, payload);
      } else {
        await api.post('/revenue-entries', payload);
      }
      navigate('/revenue-entries');
    } catch (err) {
      if (err.response?.status === 401) return;

      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setGlobalError(err.response?.data?.message || 'Could not save entry.');
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
          <Link to="/revenue-entries" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Revenue
          </Link>
          <h4 className="mb-0 mt-2">{isEdit ? 'Edit Revenue Entry' : 'New Revenue Entry'}</h4>
        </div>
      </div>

      {globalError && <div className="alert alert-danger">{globalError}</div>}

      <form onSubmit={submit}>
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Entry</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-4">
                <label className="form-label">Source *</label>
                <select
                  className={`form-select ${fieldError('source') ? 'is-invalid' : ''}`}
                  value={form.source}
                  onChange={(e) => set('source', e.target.value)}
                  required
                >
                  {SOURCES.map((s) => (
                    <option key={s.value} value={s.value}>{s.label}</option>
                  ))}
                </select>
                {fieldError('source') && <div className="invalid-feedback">{fieldError('source')}</div>}
              </div>

              <div className="col-md-4">
                <label className="form-label">Platform</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('platform') ? 'is-invalid' : ''}`}
                  value={form.platform}
                  onChange={(e) => set('platform', e.target.value)}
                  placeholder="spotify, apple_music, youtube…"
                />
                {fieldError('platform') && <div className="invalid-feedback">{fieldError('platform')}</div>}
              </div>

              <div className="col-md-2">
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

              <div className="col-md-2">
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
                <label className="form-label">Period Start</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('period_start') ? 'is-invalid' : ''}`}
                  value={form.period_start}
                  onChange={(e) => set('period_start', e.target.value)}
                />
                {fieldError('period_start') && <div className="invalid-feedback">{fieldError('period_start')}</div>}
              </div>

              <div className="col-md-4">
                <label className="form-label">Period End</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('period_end') ? 'is-invalid' : ''}`}
                  value={form.period_end}
                  onChange={(e) => set('period_end', e.target.value)}
                />
                {fieldError('period_end') && <div className="invalid-feedback">{fieldError('period_end')}</div>}
              </div>

              <div className="col-md-4">
                <label className="form-label">Received At</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('received_at') ? 'is-invalid' : ''}`}
                  value={form.received_at}
                  onChange={(e) => set('received_at', e.target.value)}
                />
                {fieldError('received_at') && <div className="invalid-feedback">{fieldError('received_at')}</div>}
              </div>

              <div className="col-md-4">
                <label className="form-label">Reference</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('reference') ? 'is-invalid' : ''}`}
                  value={form.reference}
                  onChange={(e) => set('reference', e.target.value)}
                  placeholder="Platform statement ID, invoice, etc."
                />
                {fieldError('reference') && <div className="invalid-feedback">{fieldError('reference')}</div>}
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
          <Link to="/revenue-entries" className="btn btn-outline-secondary">Cancel</Link>
          <button type="submit" className="btn btn-dark" disabled={submitting}>
            {submitting ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Revenue Entry')}
          </button>
        </div>
      </form>
    </>
  );
}