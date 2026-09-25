import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import api from '../../api';

const LANGUAGES = ['en', 'yo', 'ig', 'zu', 'pcm', 'fr', 'sw'];

const arrayToText = (arr) => Array.isArray(arr) ? arr.join('\n') : '';
const textToArray = (text) =>
  text.split('\n').map((s) => s.trim()).filter((s) => s.length > 0);

const emptyForm = {
  artist_id: '',
  title: '',
  isrc: '',
  duration_seconds: '',
  genre: '',
  language: 'en',
  bpm: '',
  key: '',
  is_explicit: false,
  composer: '',
  writersText: '',
  producersText: '',
  featuredArtistsText: '',
  recorded_date: '',
  lyrics: '',
  audio_path: '',
  notes: '',
};

export default function TrackFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = Boolean(id);

  const [form, setForm] = useState(emptyForm);
  const [artists, setArtists] = useState([]);
  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');

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
    if (!isEdit) return;

    (async () => {
      try {
        const { data } = await api.get(`/tracks/${id}`);
        const t = data.data;

        setForm({
          artist_id: t.artist_id || '',
          title: t.title || '',
          isrc: t.isrc || '',
          duration_seconds: t.duration_seconds ?? '',
          genre: t.genre || '',
          language: t.language || 'en',
          bpm: t.bpm ?? '',
          key: t.key || '',
          is_explicit: Boolean(t.is_explicit),
          composer: t.composer || '',
          writersText: arrayToText(t.writers),
          producersText: arrayToText(t.producers),
          featuredArtistsText: arrayToText(t.featured_artists),
          recorded_date: t.recorded_date || '',
          lyrics: t.lyrics || '',
          audio_path: t.audio_path || '',
          notes: t.notes || '',
        });
      } catch (err) {
        if (err.response?.status === 401) return;
        setGlobalError('Could not load track.');
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

    const writers = textToArray(form.writersText);
    const producers = textToArray(form.producersText);
    const featuredArtists = textToArray(form.featuredArtistsText);

    const payload = {
      artist_id: form.artist_id ? Number(form.artist_id) : null,
      title: form.title,
      isrc: form.isrc || null,
      duration_seconds: form.duration_seconds === '' ? null : Number(form.duration_seconds),
      genre: form.genre || null,
      language: form.language || null,
      bpm: form.bpm === '' ? null : Number(form.bpm),
      key: form.key || null,
      is_explicit: Boolean(form.is_explicit),
      composer: form.composer || null,
      writers: writers.length ? writers : null,
      producers: producers.length ? producers : null,
      featured_artists: featuredArtists.length ? featuredArtists : null,
      recorded_date: form.recorded_date || null,
      lyrics: form.lyrics || null,
      audio_path: form.audio_path || null,
      notes: form.notes || null,
    };

    try {
      if (isEdit) {
        await api.patch(`/tracks/${id}`, payload);
      } else {
        await api.post('/tracks', payload);
      }
      navigate('/tracks');
    } catch (err) {
      if (err.response?.status === 401) return;

      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setGlobalError(err.response?.data?.message || 'Could not save track.');
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
          <Link to="/tracks" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Catalogue
          </Link>
          <h4 className="mb-0 mt-2">{isEdit ? 'Edit Track' : 'New Track'}</h4>
        </div>
      </div>

      {globalError && <div className="alert alert-danger">{globalError}</div>}

      <form onSubmit={submit}>
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Basic Information</strong></div>
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
                  required
                />
                {fieldError('title') && (
                  <div className="invalid-feedback">{fieldError('title')}</div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">ISRC</label>
                <input
                  type="text"
                  maxLength={12}
                  className={`form-control ${fieldError('isrc') ? 'is-invalid' : ''}`}
                  value={form.isrc}
                  onChange={(e) => set('isrc', e.target.value)}
                  placeholder="USRC17607839"
                />
                {fieldError('isrc') && (
                  <div className="invalid-feedback">{fieldError('isrc')}</div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">Genre</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('genre') ? 'is-invalid' : ''}`}
                  value={form.genre}
                  onChange={(e) => set('genre', e.target.value)}
                  placeholder="Afrobeats, Amapiano, R&B…"
                />
                {fieldError('genre') && (
                  <div className="invalid-feedback">{fieldError('genre')}</div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">Language</label>
                <select
                  className={`form-select ${fieldError('language') ? 'is-invalid' : ''}`}
                  value={form.language}
                  onChange={(e) => set('language', e.target.value)}
                >
                  {LANGUAGES.map((l) => (
                    <option key={l} value={l}>{l}</option>
                  ))}
                </select>
                {fieldError('language') && (
                  <div className="invalid-feedback">{fieldError('language')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">Duration (seconds)</label>
                <input
                  type="number"
                  min="0"
                  max="86400"
                  className={`form-control ${fieldError('duration_seconds') ? 'is-invalid' : ''}`}
                  value={form.duration_seconds}
                  onChange={(e) => set('duration_seconds', e.target.value)}
                  placeholder="e.g. 195 for 3:15"
                />
                {fieldError('duration_seconds') && (
                  <div className="invalid-feedback">{fieldError('duration_seconds')}</div>
                )}
              </div>

              <div className="col-md-3">
                <label className="form-label">BPM</label>
                <input
                  type="number"
                  min="1"
                  max="400"
                  className={`form-control ${fieldError('bpm') ? 'is-invalid' : ''}`}
                  value={form.bpm}
                  onChange={(e) => set('bpm', e.target.value)}
                />
                {fieldError('bpm') && (
                  <div className="invalid-feedback">{fieldError('bpm')}</div>
                )}
              </div>

              <div className="col-md-3">
                <label className="form-label">Key</label>
                <input
                  type="text"
                  maxLength={10}
                  className={`form-control ${fieldError('key') ? 'is-invalid' : ''}`}
                  value={form.key}
                  onChange={(e) => set('key', e.target.value)}
                  placeholder="C#m"
                />
                {fieldError('key') && (
                  <div className="invalid-feedback">{fieldError('key')}</div>
                )}
              </div>

              <div className="col-md-6">
                <div className="form-check mt-4">
                  <input
                    type="checkbox"
                    id="is_explicit"
                    className="form-check-input"
                    checked={form.is_explicit}
                    onChange={(e) => set('is_explicit', e.target.checked)}
                  />
                  <label htmlFor="is_explicit" className="form-check-label">
                    Explicit content
                  </label>
                </div>
              </div>

              <div className="col-md-6">
                <label className="form-label">Composer</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('composer') ? 'is-invalid' : ''}`}
                  value={form.composer}
                  onChange={(e) => set('composer', e.target.value)}
                />
                {fieldError('composer') && (
                  <div className="invalid-feedback">{fieldError('composer')}</div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Contributors</strong></div>
          <div className="card-body">
            <p className="text-muted small mb-3">
              One name per line. These are stored as JSON arrays.
            </p>
            <div className="row g-3">
              <div className="col-md-4">
                <label className="form-label">Writers</label>
                <textarea
                  rows={4}
                  className={`form-control ${fieldError('writers') ? 'is-invalid' : ''}`}
                  value={form.writersText}
                  onChange={(e) => set('writersText', e.target.value)}
                  placeholder="One per line"
                />
                {fieldError('writers') && (
                  <div className="invalid-feedback">{fieldError('writers')}</div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">Producers</label>
                <textarea
                  rows={4}
                  className={`form-control ${fieldError('producers') ? 'is-invalid' : ''}`}
                  value={form.producersText}
                  onChange={(e) => set('producersText', e.target.value)}
                  placeholder="One per line"
                />
                {fieldError('producers') && (
                  <div className="invalid-feedback">{fieldError('producers')}</div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">Featured Artists</label>
                <textarea
                  rows={4}
                  className={`form-control ${fieldError('featured_artists') ? 'is-invalid' : ''}`}
                  value={form.featuredArtistsText}
                  onChange={(e) => set('featuredArtistsText', e.target.value)}
                  placeholder="One per line"
                />
                {fieldError('featured_artists') && (
                  <div className="invalid-feedback">{fieldError('featured_artists')}</div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Content</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label">Recorded Date</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('recorded_date') ? 'is-invalid' : ''}`}
                  value={form.recorded_date}
                  onChange={(e) => set('recorded_date', e.target.value)}
                />
                {fieldError('recorded_date') && (
                  <div className="invalid-feedback">{fieldError('recorded_date')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">Audio Path (optional)</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('audio_path') ? 'is-invalid' : ''}`}
                  value={form.audio_path}
                  onChange={(e) => set('audio_path', e.target.value)}
                  placeholder="Path or URL to the audio file"
                />
                {fieldError('audio_path') && (
                  <div className="invalid-feedback">{fieldError('audio_path')}</div>
                )}
                <div className="form-text">File upload not yet supported.</div>
              </div>

              <div className="col-12">
                <label className="form-label">Lyrics</label>
                <textarea
                  rows={6}
                  className={`form-control ${fieldError('lyrics') ? 'is-invalid' : ''}`}
                  value={form.lyrics}
                  onChange={(e) => set('lyrics', e.target.value)}
                />
                {fieldError('lyrics') && (
                  <div className="invalid-feedback">{fieldError('lyrics')}</div>
                )}
              </div>

              <div className="col-12">
                <label className="form-label">Notes</label>
                <textarea
                  rows={3}
                  className={`form-control ${fieldError('notes') ? 'is-invalid' : ''}`}
                  value={form.notes}
                  onChange={(e) => set('notes', e.target.value)}
                  placeholder="Internal notes"
                />
                {fieldError('notes') && (
                  <div className="invalid-feedback">{fieldError('notes')}</div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="d-flex justify-content-end gap-2 mb-4">
          <Link to="/tracks" className="btn btn-outline-secondary">Cancel</Link>
          <button type="submit" className="btn btn-dark" disabled={submitting}>
            {submitting ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Track')}
          </button>
        </div>
      </form>
    </>
  );
}