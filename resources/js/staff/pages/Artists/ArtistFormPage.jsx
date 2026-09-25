import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import api from '../../api';

const STATUSES = [
  { value: 'in_talks', label: 'In Talks' },
  { value: 'signed', label: 'Signed' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'former', label: 'Former' },
];

const SOCIAL_KEYS = ['spotify', 'apple_music', 'instagram', 'twitter', 'youtube', 'soundcloud', 'website'];

const SOCIAL_LABELS = {
  spotify: 'Spotify',
  apple_music: 'Apple Music',
  instagram: 'Instagram',
  twitter: 'Twitter / X',
  youtube: 'YouTube',
  soundcloud: 'SoundCloud',
  website: 'Website',
};

const emptyForm = {
  name: '',
  real_name: '',
  email: '',
  phone: '',
  genre: '',
  country: '',
  city: '',
  status: 'in_talks',
  manager_id: '',
  bio: '',
  avatar: '',
  social_links: SOCIAL_KEYS.reduce((acc, k) => ({ ...acc, [k]: '' }), {}),
};

export default function ArtistFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = Boolean(id);

  const [form, setForm] = useState(emptyForm);
  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');

  useEffect(() => {
    if (!isEdit) return;

    (async () => {
      try {
        const { data } = await api.get(`/artists/${id}`);
        const a = data.data;

        setForm({
          name: a.name || '',
          real_name: a.real_name || '',
          email: a.email || '',
          phone: a.phone || '',
          genre: a.genre || '',
          country: a.country || '',
          city: a.city || '',
          status: a.status || 'in_talks',
          manager_id: a.manager_id || '',
          bio: a.bio || '',
          avatar: a.avatar || '',
          social_links: SOCIAL_KEYS.reduce((acc, k) => ({
            ...acc,
            [k]: a.social_links?.[k] || '',
          }), {}),
        });
      } catch (err) {
        if (err.response?.status === 401) return;
        setGlobalError('Could not load artist.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id, isEdit]);

  const set = (field, value) => setForm((f) => ({ ...f, [field]: value }));
  const setSocial = (key, value) =>
    setForm((f) => ({ ...f, social_links: { ...f.social_links, [key]: value } }));

  const submit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});
    setGlobalError('');

    const payload = {
      name: form.name,
      real_name: form.real_name || null,
      email: form.email || null,
      phone: form.phone || null,
      genre: form.genre || null,
      country: form.country || null,
      city: form.city || null,
      status: form.status,
      manager_id: form.manager_id ? Number(form.manager_id) : null,
      bio: form.bio || null,
      avatar: form.avatar || null,
      social_links: Object.fromEntries(
        Object.entries(form.social_links).filter(([_, v]) => v && v.trim() !== '')
      ),
    };

    try {
      if (isEdit) {
        await api.patch(`/artists/${id}`, payload);
      } else {
        await api.post('/artists', payload);
      }
      navigate('/artists');
    } catch (err) {
      if (err.response?.status === 401) return;

      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setGlobalError(err.response?.data?.message || 'Could not save artist.');
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
          <Link to="/artists" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Artists
          </Link>
          <h4 className="mb-0 mt-2">{isEdit ? 'Edit Artist' : 'New Artist'}</h4>
        </div>
      </div>

      {globalError && (
        <div className="alert alert-danger">{globalError}</div>
      )}

      <form onSubmit={submit}>
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white">
            <strong>Basic Information</strong>
          </div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label">Name *</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('name') ? 'is-invalid' : ''}`}
                  value={form.name}
                  onChange={(e) => set('name', e.target.value)}
                  required
                />
                {fieldError('name') && <div className="invalid-feedback">{fieldError('name')}</div>}
              </div>

              <div className="col-md-6">
                <label className="form-label">Real Name</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('real_name') ? 'is-invalid' : ''}`}
                  value={form.real_name}
                  onChange={(e) => set('real_name', e.target.value)}
                />
                {fieldError('real_name') && <div className="invalid-feedback">{fieldError('real_name')}</div>}
              </div>

              <div className="col-md-6">
                <label className="form-label">Email</label>
                <input
                  type="email"
                  className={`form-control ${fieldError('email') ? 'is-invalid' : ''}`}
                  value={form.email}
                  onChange={(e) => set('email', e.target.value)}
                />
                {fieldError('email') && <div className="invalid-feedback">{fieldError('email')}</div>}
              </div>

              <div className="col-md-6">
                <label className="form-label">Phone</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('phone') ? 'is-invalid' : ''}`}
                  value={form.phone}
                  onChange={(e) => set('phone', e.target.value)}
                />
                {fieldError('phone') && <div className="invalid-feedback">{fieldError('phone')}</div>}
              </div>

              <div className="col-12">
                <label className="form-label">Bio</label>
                <textarea
                  rows={4}
                  className={`form-control ${fieldError('bio') ? 'is-invalid' : ''}`}
                  value={form.bio}
                  onChange={(e) => set('bio', e.target.value)}
                />
                {fieldError('bio') && <div className="invalid-feedback">{fieldError('bio')}</div>}
              </div>

              <div className="col-md-6">
                <label className="form-label">Avatar URL</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('avatar') ? 'is-invalid' : ''}`}
                  value={form.avatar}
                  onChange={(e) => set('avatar', e.target.value)}
                  placeholder="https://…"
                />
                {fieldError('avatar') && <div className="invalid-feedback">{fieldError('avatar')}</div>}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white">
            <strong>Classification</strong>
          </div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-4">
                <label className="form-label">Genre</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('genre') ? 'is-invalid' : ''}`}
                  value={form.genre}
                  onChange={(e) => set('genre', e.target.value)}
                  placeholder="Afrobeats, Amapiano, R&B…"
                />
                {fieldError('genre') && <div className="invalid-feedback">{fieldError('genre')}</div>}
              </div>

              <div className="col-md-4">
                <label className="form-label">Country</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('country') ? 'is-invalid' : ''}`}
                  value={form.country}
                  onChange={(e) => set('country', e.target.value)}
                />
                {fieldError('country') && <div className="invalid-feedback">{fieldError('country')}</div>}
              </div>

              <div className="col-md-4">
                <label className="form-label">City</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('city') ? 'is-invalid' : ''}`}
                  value={form.city}
                  onChange={(e) => set('city', e.target.value)}
                />
                {fieldError('city') && <div className="invalid-feedback">{fieldError('city')}</div>}
              </div>

              <div className="col-md-6">
                <label className="form-label">Status *</label>
                <select
                  className={`form-select ${fieldError('status') ? 'is-invalid' : ''}`}
                  value={form.status}
                  onChange={(e) => set('status', e.target.value)}
                >
                  {STATUSES.map((s) => (
                    <option key={s.value} value={s.value}>{s.label}</option>
                  ))}
                </select>
                {fieldError('status') && <div className="invalid-feedback">{fieldError('status')}</div>}
              </div>

              <div className="col-md-6">
                <label className="form-label">Manager ID (optional)</label>
                <input
                  type="number"
                  className={`form-control ${fieldError('manager_id') ? 'is-invalid' : ''}`}
                  value={form.manager_id}
                  onChange={(e) => set('manager_id', e.target.value)}
                  placeholder="Leave blank if none"
                />
                {fieldError('manager_id') && <div className="invalid-feedback">{fieldError('manager_id')}</div>}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white">
            <strong>Social Links</strong>
          </div>
          <div className="card-body">
            <div className="row g-3">
              {SOCIAL_KEYS.map((key) => (
                <div key={key} className="col-md-6">
                  <label className="form-label">{SOCIAL_LABELS[key]}</label>
                  <input
                    type="url"
                    className={`form-control ${fieldError(`social_links.${key}`) ? 'is-invalid' : ''}`}
                    value={form.social_links[key]}
                    onChange={(e) => setSocial(key, e.target.value)}
                    placeholder="https://…"
                  />
                  {fieldError(`social_links.${key}`) && (
                    <div className="invalid-feedback">{fieldError(`social_links.${key}`)}</div>
                  )}
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="d-flex justify-content-end gap-2 mb-4">
          <Link to="/artists" className="btn btn-outline-secondary">
            Cancel
          </Link>
          <button type="submit" className="btn btn-dark" disabled={submitting}>
            {submitting ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Artist')}
          </button>
        </div>
      </form>
    </>
  );
}