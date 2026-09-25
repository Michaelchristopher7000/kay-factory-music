import { useEffect, useState } from 'react';
import { useNavigate, useParams, useSearchParams, Link } from 'react-router-dom';
import api from '../../api';

const emptyForm = {
  artist_id: '',
  title: '',
  description: '',
  venue: '',
  city: '',
  country: '',
  event_date: '',
  event_time: '',
  ticket_url: '',
  event_url: '',
  image: null,
  status: 'draft',
};

export default function ArtistEventFormPage() {
  const { id } = useParams();
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const isEdit = Boolean(id);

  const [form, setForm] = useState(emptyForm);
  const [artists, setArtists] = useState([]);
  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');

  const [existingImageUrl, setExistingImageUrl] = useState(null);
  const [previewUrl, setPreviewUrl] = useState(null);

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get('/artists', { params: { per_page: 100 } });
        setArtists(data.data || []);
      } catch {
        // silent
      }
    })();
  }, []);

  useEffect(() => {
    if (!isEdit) {
      const preArtist = searchParams.get('artist_id');
      if (preArtist) setForm((f) => ({ ...f, artist_id: preArtist }));
      return;
    }

    (async () => {
      try {
        const { data } = await api.get(`/artist-events/${id}`);
        const e = data.data;

        // Slice datetime to `YYYY-MM-DDTHH:MM` for <input type="datetime-local">
        const dt = e.event_date ? e.event_date.slice(0, 16) : '';
        const t = e.event_time ? e.event_time.slice(0, 5) : '';

        setForm({
          artist_id: e.artist_id || '',
          title: e.title || '',
          description: e.description || '',
          venue: e.venue || '',
          city: e.city || '',
          country: e.country || '',
          event_date: dt,
          event_time: t,
          ticket_url: e.ticket_url || '',
          event_url: e.event_url || '',
          image: null,
          status: e.status || 'draft',
        });

        setExistingImageUrl(e.image_url || null);
      } catch (err) {
        if (err.response?.status === 401) return;
        setGlobalError('Could not load event.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id, isEdit, searchParams]);

  useEffect(() => {
    if (!form.image) {
      setPreviewUrl(null);
      return;
    }
    const url = URL.createObjectURL(form.image);
    setPreviewUrl(url);
    return () => URL.revokeObjectURL(url);
  }, [form.image]);

  const set = (field, value) => setForm((f) => ({ ...f, [field]: value }));

  const submit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setErrors({});
    setGlobalError('');

    const fd = new FormData();
    fd.append('artist_id', form.artist_id);
    fd.append('title', form.title);
    if (form.description) fd.append('description', form.description);
    if (form.venue) fd.append('venue', form.venue);
    if (form.city) fd.append('city', form.city);
    if (form.country) fd.append('country', form.country);

    // Combine date+time into event_date; also send event_time separately
    if (form.event_date) {
      const combined = form.event_time
        ? `${form.event_date.replace('T', ' ')}:00`.slice(0, 19)
        : `${form.event_date}:00`.slice(0, 19);
      fd.append('event_date', combined);
    }
    if (form.event_time) fd.append('event_time', form.event_time);

    if (form.ticket_url) fd.append('ticket_url', form.ticket_url);
    if (form.event_url) fd.append('event_url', form.event_url);
    fd.append('status', form.status);

    if (form.image) fd.append('image', form.image);

    if (isEdit) fd.append('_method', 'PUT');

    try {
      if (isEdit) {
        await api.post(`/artist-events/${id}`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      } else {
        await api.post('/artist-events', fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
      navigate('/artist-events');
    } catch (err) {
      if (err.response?.status === 401) return;

      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setGlobalError(err.response?.data?.message || 'Could not save event.');
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
          <Link to="/artist-events" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Events
          </Link>
          <h4 className="mb-0 mt-2">{isEdit ? 'Edit Event' : 'New Event'}</h4>
        </div>
      </div>

      {globalError && <div className="alert alert-danger">{globalError}</div>}

      <form onSubmit={submit}>
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Event Information</strong></div>
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
                <label className="form-label">Status *</label>
                <select
                  className={`form-select ${fieldError('status') ? 'is-invalid' : ''}`}
                  value={form.status}
                  onChange={(e) => set('status', e.target.value)}
                >
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
                </select>
                {fieldError('status') && (
                  <div className="invalid-feedback">{fieldError('status')}</div>
                )}
              </div>

              <div className="col-12">
                <label className="form-label">Title *</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('title') ? 'is-invalid' : ''}`}
                  value={form.title}
                  onChange={(e) => set('title', e.target.value)}
                  required
                />
                {fieldError('title') && (
                  <div className="invalid-feedback">{fieldError('title')}</div>
                )}
              </div>

              <div className="col-12">
                <label className="form-label">Description</label>
                <textarea
                  rows={3}
                  className={`form-control ${fieldError('description') ? 'is-invalid' : ''}`}
                  value={form.description}
                  onChange={(e) => set('description', e.target.value)}
                />
                {fieldError('description') && (
                  <div className="invalid-feedback">{fieldError('description')}</div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>When &amp; Where</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label">Date &amp; Time *</label>
                <input
                  type="datetime-local"
                  className={`form-control ${fieldError('event_date') ? 'is-invalid' : ''}`}
                  value={form.event_date}
                  onChange={(e) => set('event_date', e.target.value)}
                  required
                />
                {fieldError('event_date') && (
                  <div className="invalid-feedback">{fieldError('event_date')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">Doors Time</label>
                <input
                  type="time"
                  className={`form-control ${fieldError('event_time') ? 'is-invalid' : ''}`}
                  value={form.event_time}
                  onChange={(e) => set('event_time', e.target.value)}
                />
                {fieldError('event_time') && (
                  <div className="invalid-feedback">{fieldError('event_time')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">Venue</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('venue') ? 'is-invalid' : ''}`}
                  value={form.venue}
                  onChange={(e) => set('venue', e.target.value)}
                  placeholder="e.g. Eko Convention Centre"
                />
                {fieldError('venue') && (
                  <div className="invalid-feedback">{fieldError('venue')}</div>
                )}
              </div>

              <div className="col-md-3">
                <label className="form-label">City</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('city') ? 'is-invalid' : ''}`}
                  value={form.city}
                  onChange={(e) => set('city', e.target.value)}
                />
                {fieldError('city') && (
                  <div className="invalid-feedback">{fieldError('city')}</div>
                )}
              </div>

              <div className="col-md-3">
                <label className="form-label">Country</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('country') ? 'is-invalid' : ''}`}
                  value={form.country}
                  onChange={(e) => set('country', e.target.value)}
                />
                {fieldError('country') && (
                  <div className="invalid-feedback">{fieldError('country')}</div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Links</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label">Ticket URL</label>
                <input
                  type="url"
                  className={`form-control ${fieldError('ticket_url') ? 'is-invalid' : ''}`}
                  value={form.ticket_url}
                  onChange={(e) => set('ticket_url', e.target.value)}
                  placeholder="https://tickets.example.com/..."
                />
                {fieldError('ticket_url') && (
                  <div className="invalid-feedback">{fieldError('ticket_url')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">Event URL</label>
                <input
                  type="url"
                  className={`form-control ${fieldError('event_url') ? 'is-invalid' : ''}`}
                  value={form.event_url}
                  onChange={(e) => set('event_url', e.target.value)}
                  placeholder="https://..."
                />
                {fieldError('event_url') && (
                  <div className="invalid-feedback">{fieldError('event_url')}</div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Artwork</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-12">
                <label className="form-label">Event Image</label>
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  className={`form-control ${fieldError('image') ? 'is-invalid' : ''}`}
                  onChange={(e) => set('image', e.target.files?.[0] || null)}
                />
                {fieldError('image') && (
                  <div className="invalid-feedback">{fieldError('image')}</div>
                )}
                <div className="form-text">JPG, PNG, or WebP. Max 10 MB.</div>

                {(previewUrl || existingImageUrl) && (
                  <div className="mt-3">
                    <img
                      src={previewUrl || existingImageUrl}
                      alt="Preview"
                      style={{
                        maxWidth: 320,
                        maxHeight: 200,
                        objectFit: 'cover',
                        borderRadius: 8,
                      }}
                    />
                    {previewUrl && (
                      <div className="small text-muted mt-1">New image preview</div>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="d-flex justify-content-end gap-2 mb-4">
          <Link to="/artist-events" className="btn btn-outline-secondary">Cancel</Link>
          <button type="submit" className="btn btn-dark" disabled={submitting}>
            {submitting ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Event')}
          </button>
        </div>
      </form>
    </>
  );
}