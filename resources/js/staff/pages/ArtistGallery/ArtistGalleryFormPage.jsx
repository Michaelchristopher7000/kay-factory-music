import { useEffect, useState } from 'react';
import { useNavigate, useParams, useSearchParams, Link } from 'react-router-dom';
import api from '../../api';

const emptyForm = {
  artist_id: '',
  image: null,
  caption: '',
  alt_text: '',
  sort_order: 0,
  is_featured: false,
  status: 'published',
};

export default function ArtistGalleryFormPage() {
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
        const { data } = await api.get(`/artist-gallery/${id}`);
        const g = data.data;

        setForm({
          artist_id: g.artist_id || '',
          image: null,
          caption: g.caption || '',
          alt_text: g.alt_text || '',
          sort_order: g.sort_order ?? 0,
          is_featured: Boolean(g.is_featured),
          status: g.status || 'published',
        });

        setExistingImageUrl(g.image_url || null);
      } catch (err) {
        if (err.response?.status === 401) return;
        setGlobalError('Could not load image.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id, isEdit, searchParams]);

  // Live preview of the picked file
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
    if (form.caption) fd.append('caption', form.caption);
    if (form.alt_text) fd.append('alt_text', form.alt_text);
    fd.append('sort_order', String(form.sort_order || 0));
    fd.append('is_featured', form.is_featured ? '1' : '0');
    fd.append('status', form.status);

    if (form.image) {
      fd.append('image', form.image);
    }

    if (isEdit) {
      fd.append('_method', 'PUT');
    }

    try {
      if (isEdit) {
        await api.post(`/artist-gallery/${id}`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      } else {
        await api.post('/artist-gallery', fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
      navigate('/artist-gallery');
    } catch (err) {
      if (err.response?.status === 401) return;

      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setGlobalError(err.response?.data?.message || 'Could not save image.');
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
          <Link to="/artist-gallery" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Gallery
          </Link>
          <h4 className="mb-0 mt-2">{isEdit ? 'Edit Image' : 'Upload Image'}</h4>
        </div>
      </div>

      {globalError && <div className="alert alert-danger">{globalError}</div>}

      <form onSubmit={submit}>
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Image</strong></div>
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
                  <option value="published">Published</option>
                  <option value="draft">Draft</option>
                </select>
                {fieldError('status') && (
                  <div className="invalid-feedback">{fieldError('status')}</div>
                )}
              </div>

              <div className="col-12">
                <label className="form-label">
                  Image File {!isEdit && '*'}
                </label>
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
                        maxWidth: 240,
                        maxHeight: 240,
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

        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Details</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-12">
                <label className="form-label">Caption</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('caption') ? 'is-invalid' : ''}`}
                  value={form.caption}
                  onChange={(e) => set('caption', e.target.value)}
                  placeholder="e.g. Studio session, Lagos 2026"
                />
                {fieldError('caption') && (
                  <div className="invalid-feedback">{fieldError('caption')}</div>
                )}
              </div>

              <div className="col-12">
                <label className="form-label">Alt Text</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('alt_text') ? 'is-invalid' : ''}`}
                  value={form.alt_text}
                  onChange={(e) => set('alt_text', e.target.value)}
                  placeholder="Describe the image for accessibility"
                />
                {fieldError('alt_text') && (
                  <div className="invalid-feedback">{fieldError('alt_text')}</div>
                )}
              </div>

              <div className="col-md-6">
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
                <div className="form-text">Lower numbers appear first.</div>
              </div>

              <div className="col-md-6 d-flex align-items-center">
                <div className="form-check mt-4">
                  <input
                    type="checkbox"
                    id="is_featured"
                    className="form-check-input"
                    checked={form.is_featured}
                    onChange={(e) => set('is_featured', e.target.checked)}
                  />
                  <label htmlFor="is_featured" className="form-check-label">
                    Feature this photo
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="d-flex justify-content-end gap-2 mb-4">
          <Link to="/artist-gallery" className="btn btn-outline-secondary">Cancel</Link>
          <button type="submit" className="btn btn-dark" disabled={submitting}>
            {submitting ? 'Saving…' : (isEdit ? 'Save Changes' : 'Upload Image')}
          </button>
        </div>
      </form>
    </>
  );
}