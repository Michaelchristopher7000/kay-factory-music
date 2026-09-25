import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import api from '../../api';

const PLATFORMS = [
  { value: 'spotify', label: 'Spotify' },
  { value: 'apple_music', label: 'Apple Music' },
  { value: 'youtube_music', label: 'YouTube Music' },
  { value: 'amazon_music', label: 'Amazon Music' },
  { value: 'deezer', label: 'Deezer' },
  { value: 'tidal', label: 'Tidal' },
  { value: 'audiomack', label: 'Audiomack' },
  { value: 'boomplay', label: 'Boomplay' },
  { value: 'soundcloud', label: 'SoundCloud' },
  { value: 'pandora', label: 'Pandora' },
  { value: 'other', label: 'Other' },
];

const STATUSES = [
  { value: 'pending', label: 'Pending' },
  { value: 'submitted', label: 'Submitted' },
  { value: 'live', label: 'Live' },
  { value: 'takedown', label: 'Takedown' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'failed', label: 'Failed' },
];

const TERRITORIES = ['worldwide', 'africa', 'eu', 'us', 'uk', 'ng', 'za'];

const emptyForm = {
  release_id: '',
  platform: 'spotify',
  status: 'pending',
  distributor: '',
  territory: '',
  scheduled_for: '',
  submitted_at: '',
  live_at: '',
  takedown_at: '',
  platform_release_id: '',
  platform_url: '',
  notes: '',
};

export default function DistributionFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = Boolean(id);

  const [form, setForm] = useState(emptyForm);
  const [releases, setReleases] = useState([]);
  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get('/releases', { params: { per_page: 100 } });
        setReleases(data.data || []);
      } catch {
        // silent
      }
    })();
  }, []);

  useEffect(() => {
    if (!isEdit) return;

    (async () => {
      try {
        const { data } = await api.get(`/distributions/${id}`);
        const d = data.data;

        setForm({
          release_id: d.release_id || '',
          platform: d.platform || 'spotify',
          status: d.status || 'pending',
          distributor: d.distributor || '',
          territory: d.territory || '',
          scheduled_for: d.scheduled_for || '',
          submitted_at: d.submitted_at || '',
          live_at: d.live_at || '',
          takedown_at: d.takedown_at || '',
          platform_release_id: d.platform_release_id || '',
          platform_url: d.platform_url || '',
          notes: d.notes || '',
        });
      } catch (err) {
        if (err.response?.status === 401) return;
        setGlobalError('Could not load distribution.');
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
      release_id: form.release_id ? Number(form.release_id) : null,
      platform: form.platform,
      status: form.status,
      distributor: form.distributor || null,
      territory: form.territory || null,
      scheduled_for: form.scheduled_for || null,
      submitted_at: form.submitted_at || null,
      live_at: form.live_at || null,
      takedown_at: form.takedown_at || null,
      platform_release_id: form.platform_release_id || null,
      platform_url: form.platform_url || null,
      notes: form.notes || null,
    };

    try {
      if (isEdit) {
        await api.patch(`/distributions/${id}`, payload);
      } else {
        await api.post('/distributions', payload);
      }
      navigate('/distributions');
    } catch (err) {
      if (err.response?.status === 401) return;

      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setGlobalError(err.response?.data?.message || 'Could not save distribution.');
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
          <Link to="/distributions" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Distribution
          </Link>
          <h4 className="mb-0 mt-2">{isEdit ? 'Edit Distribution' : 'New Distribution'}</h4>
        </div>
      </div>

      {globalError && <div className="alert alert-danger">{globalError}</div>}

      <form onSubmit={submit}>
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Delivery</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label">Release *</label>
                <select
                  className={`form-select ${fieldError('release_id') ? 'is-invalid' : ''}`}
                  value={form.release_id}
                  onChange={(e) => set('release_id', e.target.value)}
                  required
                >
                  <option value="">— Select release —</option>
                  {releases.map((r) => (
                    <option key={r.id} value={r.id}>
                      {r.release_code} — {r.title}
                    </option>
                  ))}
                </select>
                {fieldError('release_id') && (
                  <div className="invalid-feedback">{fieldError('release_id')}</div>
                )}
                <div className="form-text">
                  Each release can only have one distribution row per platform.
                </div>
              </div>

              <div className="col-md-6">
                <label className="form-label">Platform *</label>
                <select
                  className={`form-select ${fieldError('platform') ? 'is-invalid' : ''}`}
                  value={form.platform}
                  onChange={(e) => set('platform', e.target.value)}
                  required
                >
                  {PLATFORMS.map((p) => (
                    <option key={p.value} value={p.value}>{p.label}</option>
                  ))}
                </select>
                {fieldError('platform') && (
                  <div className="invalid-feedback">{fieldError('platform')}</div>
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
                <label className="form-label">Distributor</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('distributor') ? 'is-invalid' : ''}`}
                  value={form.distributor}
                  onChange={(e) => set('distributor', e.target.value)}
                  placeholder="Empire, The Orchard, Believe…"
                />
                {fieldError('distributor') && (
                  <div className="invalid-feedback">{fieldError('distributor')}</div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">Territory</label>
                <input
                  type="text"
                  list="territory-options"
                  className={`form-control ${fieldError('territory') ? 'is-invalid' : ''}`}
                  value={form.territory}
                  onChange={(e) => set('territory', e.target.value)}
                  placeholder="worldwide"
                />
                <datalist id="territory-options">
                  {TERRITORIES.map((t) => (
                    <option key={t} value={t} />
                  ))}
                </datalist>
                {fieldError('territory') && (
                  <div className="invalid-feedback">{fieldError('territory')}</div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Timeline</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-3">
                <label className="form-label">Scheduled For</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('scheduled_for') ? 'is-invalid' : ''}`}
                  value={form.scheduled_for}
                  onChange={(e) => set('scheduled_for', e.target.value)}
                />
                {fieldError('scheduled_for') && (
                  <div className="invalid-feedback">{fieldError('scheduled_for')}</div>
                )}
              </div>

              <div className="col-md-3">
                <label className="form-label">Submitted At</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('submitted_at') ? 'is-invalid' : ''}`}
                  value={form.submitted_at}
                  onChange={(e) => set('submitted_at', e.target.value)}
                />
                {fieldError('submitted_at') && (
                  <div className="invalid-feedback">{fieldError('submitted_at')}</div>
                )}
              </div>

              <div className="col-md-3">
                <label className="form-label">Live At</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('live_at') ? 'is-invalid' : ''}`}
                  value={form.live_at}
                  onChange={(e) => set('live_at', e.target.value)}
                />
                {fieldError('live_at') && (
                  <div className="invalid-feedback">{fieldError('live_at')}</div>
                )}
              </div>

              <div className="col-md-3">
                <label className="form-label">Takedown At</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('takedown_at') ? 'is-invalid' : ''}`}
                  value={form.takedown_at}
                  onChange={(e) => set('takedown_at', e.target.value)}
                />
                {fieldError('takedown_at') && (
                  <div className="invalid-feedback">{fieldError('takedown_at')}</div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Platform Reference</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label">Platform Release ID</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('platform_release_id') ? 'is-invalid' : ''}`}
                  value={form.platform_release_id}
                  onChange={(e) => set('platform_release_id', e.target.value)}
                  placeholder="ID assigned by the platform"
                />
                {fieldError('platform_release_id') && (
                  <div className="invalid-feedback">{fieldError('platform_release_id')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">Platform URL</label>
                <input
                  type="url"
                  className={`form-control ${fieldError('platform_url') ? 'is-invalid' : ''}`}
                  value={form.platform_url}
                  onChange={(e) => set('platform_url', e.target.value)}
                  placeholder="https://open.spotify.com/album/…"
                />
                {fieldError('platform_url') && (
                  <div className="invalid-feedback">{fieldError('platform_url')}</div>
                )}
              </div>

              <div className="col-12">
                <label className="form-label">Notes</label>
                <textarea
                  rows={3}
                  className={`form-control ${fieldError('notes') ? 'is-invalid' : ''}`}
                  value={form.notes}
                  onChange={(e) => set('notes', e.target.value)}
                  placeholder="Internal notes about this delivery"
                />
                {fieldError('notes') && (
                  <div className="invalid-feedback">{fieldError('notes')}</div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="d-flex justify-content-end gap-2 mb-4">
          <Link to="/distributions" className="btn btn-outline-secondary">Cancel</Link>
          <button type="submit" className="btn btn-dark" disabled={submitting}>
            {submitting ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Distribution')}
          </button>
        </div>
      </form>
    </>
  );
}