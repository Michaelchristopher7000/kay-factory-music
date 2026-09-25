import { useEffect, useState } from 'react';
import { useNavigate, useParams, useSearchParams, Link } from 'react-router-dom';
import api from '../../api';

const TYPES = [
  { value: 'music_video', label: 'Music Video' },
  { value: 'visualizer', label: 'Visualizer' },
  { value: 'live', label: 'Live' },
  { value: 'interview', label: 'Interview' },
  { value: 'behind_the_scenes', label: 'Behind the Scenes' },
];

const emptyForm = {
  artist_id: '',
  title: '',
  description: '',
  type: 'music_video',
  source: 'youtube',
  youtube_url: '',
  video: null,
  thumbnail: null,
  published_at: '',
  is_featured: false,
  is_home_featured: false,
  sort_order: 0,
  status: 'draft',
};

export default function ArtistVideoFormPage() {
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

  const [existingVideoUrl, setExistingVideoUrl] = useState(null);
  const [existingThumbUrl, setExistingThumbUrl] = useState(null);

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
        const { data } = await api.get(`/artist-videos/${id}`);
        const v = data.data;

        setForm({
          artist_id: v.artist_id || '',
          title: v.title || '',
          description: v.description || '',
          type: v.type || 'music_video',
          source: v.source || 'youtube',
          youtube_url: v.youtube_id ? `https://www.youtube.com/watch?v=${v.youtube_id}` : '',
          video: null,
          thumbnail: null,
          published_at: v.published_at ? v.published_at.slice(0, 16) : '',
          is_featured: Boolean(v.is_featured),
          is_home_featured: Boolean(v.is_home_featured),
          sort_order: v.sort_order ?? 0,
          status: v.status || 'draft',
        });

        setExistingVideoUrl(v.video_url || null);
        setExistingThumbUrl(v.thumbnail_url || null);
      } catch (err) {
        if (err.response?.status === 401) return;
        setGlobalError('Could not load video.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id, isEdit, searchParams]);

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
    fd.append('type', form.type);
    fd.append('source', form.source);
    fd.append('status', form.status);
    fd.append('is_featured', form.is_featured ? '1' : '0');
    fd.append('is_home_featured', form.is_home_featured ? '1' : '0');
    fd.append('sort_order', String(form.sort_order || 0));

    if (form.published_at) {
      fd.append('published_at', form.published_at.replace('T', ' ') + ':00');
    }

    if (form.source === 'youtube') {
      fd.append('youtube_url', form.youtube_url);
    } else if (form.video) {
      fd.append('video', form.video);
    }

    if (form.thumbnail) {
      fd.append('thumbnail', form.thumbnail);
    }

    if (isEdit) {
      fd.append('_method', 'PUT');
    }

    try {
      if (isEdit) {
        await api.post(`/artist-videos/${id}`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      } else {
        await api.post('/artist-videos', fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
      navigate('/artist-videos');
    } catch (err) {
      if (err.response?.status === 401) return;

      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setGlobalError(err.response?.data?.message || 'Could not save video.');
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
          <Link to="/artist-videos" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Videos
          </Link>
          <h4 className="mb-0 mt-2">{isEdit ? 'Edit Video' : 'New Video'}</h4>
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

              <div className="col-md-6">
                <label className="form-label">Type *</label>
                <select
                  className={`form-select ${fieldError('type') ? 'is-invalid' : ''}`}
                  value={form.type}
                  onChange={(e) => set('type', e.target.value)}
                >
                  {TYPES.map((t) => (
                    <option key={t.value} value={t.value}>{t.label}</option>
                  ))}
                </select>
                {fieldError('type') && (
                  <div className="invalid-feedback">{fieldError('type')}</div>
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
          <div className="card-header bg-white"><strong>Source</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-4">
                <label className="form-label d-block">Source Type *</label>
                <div className="btn-group" role="group">
                  <input
                    type="radio"
                    className="btn-check"
                    name="source"
                    id="source_youtube"
                    checked={form.source === 'youtube'}
                    onChange={() => set('source', 'youtube')}
                  />
                  <label className="btn btn-outline-dark" htmlFor="source_youtube">
                    <i className="bi bi-youtube me-1"></i> YouTube
                  </label>

                  <input
                    type="radio"
                    className="btn-check"
                    name="source"
                    id="source_upload"
                    checked={form.source === 'upload'}
                    onChange={() => set('source', 'upload')}
                  />
                  <label className="btn btn-outline-dark" htmlFor="source_upload">
                    <i className="bi bi-cloud-upload me-1"></i> Upload
                  </label>
                </div>
              </div>

              {form.source === 'youtube' && (
                <div className="col-12">
                  <label className="form-label">YouTube URL *</label>
                  <input
                    type="url"
                    className={`form-control ${fieldError('youtube_url') ? 'is-invalid' : ''}`}
                    value={form.youtube_url}
                    onChange={(e) => set('youtube_url', e.target.value)}
                    placeholder="https://www.youtube.com/watch?v=..."
                  />
                  {fieldError('youtube_url') && (
                    <div className="invalid-feedback">{fieldError('youtube_url')}</div>
                  )}
                  <div className="form-text">
                    Paste any YouTube link — we'll extract the video ID automatically.
                  </div>
                </div>
              )}

              {form.source === 'upload' && (
                <div className="col-12">
                  <label className="form-label">
                    Video File {!isEdit && '*'}
                  </label>
                  <input
                    type="file"
                    accept="video/mp4,video/webm,video/quicktime"
                    className={`form-control ${fieldError('video') ? 'is-invalid' : ''}`}
                    onChange={(e) => set('video', e.target.files?.[0] || null)}
                  />
                  {fieldError('video') && (
                    <div className="invalid-feedback">{fieldError('video')}</div>
                  )}
                  <div className="form-text">MP4, WebM, or MOV. Max 500 MB.</div>

                  {existingVideoUrl && (
                    <div className="mt-2 small">
                      Current file:{' '}
                      <a href={existingVideoUrl} target="_blank" rel="noopener noreferrer">
                        View
                      </a>
                    </div>
                  )}
                </div>
              )}

              <div className="col-12">
                <label className="form-label">Thumbnail</label>
                <input
                  type="file"
                  accept="image/*"
                  className={`form-control ${fieldError('thumbnail') ? 'is-invalid' : ''}`}
                  onChange={(e) => set('thumbnail', e.target.files?.[0] || null)}
                />
                {fieldError('thumbnail') && (
                  <div className="invalid-feedback">{fieldError('thumbnail')}</div>
                )}
                <div className="form-text">
                  Optional. For YouTube videos, we'll auto-use the YouTube thumbnail if you don't upload one.
                </div>

                {existingThumbUrl && (
                  <div className="mt-2">
                    <img
                      src={existingThumbUrl}
                      alt=""
                      style={{ width: 120, borderRadius: 6 }}
                    />
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Publishing</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-4">
                <label className="form-label">Publish Date &amp; Time</label>
                <input
                  type="datetime-local"
                  className={`form-control ${fieldError('published_at') ? 'is-invalid' : ''}`}
                  value={form.published_at}
                  onChange={(e) => set('published_at', e.target.value)}
                />
                {fieldError('published_at') && (
                  <div className="invalid-feedback">{fieldError('published_at')}</div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">Sort Order</label>
                <input
                  type="number"
                  min="0"
                  className={`form-control ${fieldError('sort_order') ? 'is-invalid' : ''}`}
                  value={form.sort_order}
                  onChange={(e) => set('sort_order', e.target.value)}
                />
                {fieldError('sort_order') && (
                  <div className="invalid-feedback">{fieldError('sort_order')}</div>
                )}
              </div>

              <div className="col-md-4 d-flex align-items-center">
                <div className="form-check mt-4">
                  <input
                    type="checkbox"
                    id="is_featured"
                    className="form-check-input"
                    checked={form.is_featured}
                    onChange={(e) => set('is_featured', e.target.checked)}
                  />
                  <label htmlFor="is_featured" className="form-check-label">
                    Feature on artist page
                  </label>
                </div>
              </div>

              <div className="col-md-6 d-flex align-items-start">
                <div className="form-check mt-2">
                  <input
                    type="checkbox"
                    id="is_home_featured"
                    className="form-check-input"
                    checked={form.is_home_featured}
                    onChange={(e) => set('is_home_featured', e.target.checked)}
                  />
                  <label htmlFor="is_home_featured" className="form-check-label">
                    Feature on home page
                  </label>
                  <div className="form-text">
                    Only one video can be featured on the home page at a time.
                    Enabling this will unfeature any other video.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="d-flex justify-content-end gap-2 mb-4">
          <Link to="/artist-videos" className="btn btn-outline-secondary">Cancel</Link>
          <button type="submit" className="btn btn-dark" disabled={submitting}>
            {submitting ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Video')}
          </button>
        </div>
      </form>
    </>
  );
}