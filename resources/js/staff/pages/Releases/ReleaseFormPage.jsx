import { useEffect, useState, useCallback } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import api from '../../api';

const TYPES = [
  { value: 'single', label: 'Single' },
  { value: 'ep', label: 'EP' },
  { value: 'album', label: 'Album' },
  { value: 'mixtape', label: 'Mixtape' },
  { value: 'compilation', label: 'Compilation' },
  { value: 'other', label: 'Other' },
];

const STATUSES = [
  { value: 'draft', label: 'Draft' },
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'released', label: 'Released' },
  { value: 'archived', label: 'Archived' },
  { value: 'cancelled', label: 'Cancelled' },
];

const CURRENCIES = ['NGN', 'USD', 'ZAR', 'GBP', 'EUR'];

const emptyForm = {
  artist_id: '',
  title: '',
  type: 'single',
  status: 'draft',
  release_date: '',
  pre_save_date: '',
  upc: '',
  description: '',
  cover_art_path: '',
  label_copy: '',
};

export default function ReleaseFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = Boolean(id);

  const [form, setForm] = useState(emptyForm);
  const [artists, setArtists] = useState([]);
  const [availableTracks, setAvailableTracks] = useState([]);
  const [selectedTrackIds, setSelectedTrackIds] = useState([]);

  const [originalArtistId, setOriginalArtistId] = useState(null);
  const [originalTrackIds, setOriginalTrackIds] = useState([]);

  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');
  const [artistChangedWithoutTracks, setArtistChangedWithoutTracks] = useState(false);

  // Load all artists for the dropdown
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

  // Load existing release for edit
  useEffect(() => {
    if (!isEdit) return;

    (async () => {
      try {
        const { data } = await api.get(`/releases/${id}`);
        const r = data.data;

        setForm({
          artist_id: r.artist_id || '',
          title: r.title || '',
          type: r.type || 'single',
          status: r.status || 'draft',
          release_date: r.release_date || '',
          pre_save_date: r.pre_save_date || '',
          upc: r.upc || '',
          description: r.description || '',
          cover_art_path: r.cover_art_path || '',
          label_copy: r.label_copy || '',
        });

        setOriginalArtistId(r.artist_id);
        const currentTrackIds = (r.tracks || []).map((t) => t.id);
        setSelectedTrackIds(currentTrackIds);
        setOriginalTrackIds(currentTrackIds);
      } catch (err) {
        if (err.response?.status === 401) return;
        setGlobalError('Could not load release.');
      } finally {
        setLoading(false);
      }
    })();
  }, [id, isEdit]);

  // Load tracks for the selected artist
  const loadTracksForArtist = useCallback(async (artistId) => {
    if (!artistId) {
      setAvailableTracks([]);
      return;
    }

    try {
      const { data } = await api.get('/tracks', {
        params: { artist_id: artistId, per_page: 100 },
      });
      setAvailableTracks(data.data || []);
    } catch {
      setAvailableTracks([]);
    }
  }, []);

  useEffect(() => {
    loadTracksForArtist(form.artist_id);
  }, [form.artist_id, loadTracksForArtist]);

  // Detect artist change in edit mode — requires re-selecting tracks
  useEffect(() => {
    if (!isEdit) return;
    const changed = form.artist_id && Number(form.artist_id) !== Number(originalArtistId);
    setArtistChangedWithoutTracks(Boolean(changed));
  }, [form.artist_id, originalArtistId, isEdit]);

  const set = (field, value) => setForm((f) => ({ ...f, [field]: value }));

  const toggleTrack = (trackId) => {
    setSelectedTrackIds((prev) =>
      prev.includes(trackId)
        ? prev.filter((x) => x !== trackId)
        : [...prev, trackId]
    );
  };

  const moveTrack = (index, direction) => {
    setSelectedTrackIds((prev) => {
      const next = [...prev];
      const target = index + direction;
      if (target < 0 || target >= next.length) return prev;
      [next[index], next[target]] = [next[target], next[index]];
      return next;
    });
  };

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
      release_date: form.release_date || null,
      pre_save_date: form.pre_save_date || null,
      upc: form.upc || null,
      description: form.description || null,
      cover_art_path: form.cover_art_path || null,
      label_copy: form.label_copy || null,
      track_ids: selectedTrackIds,
    };

    try {
      if (isEdit) {
        await api.patch(`/releases/${id}`, payload);
      } else {
        await api.post('/releases', payload);
      }
      navigate('/releases');
    } catch (err) {
      if (err.response?.status === 401) return;

      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
        // If the artist-change guard fired, show a targeted message
        if (err.response.data.errors?.track_ids) {
          setGlobalError(err.response.data.errors.track_ids[0]);
        }
      } else {
        setGlobalError(err.response?.data?.message || 'Could not save release.');
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
  const selectedSet = new Set(selectedTrackIds);

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <Link to="/releases" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Releases
          </Link>
          <h4 className="mb-0 mt-2">{isEdit ? 'Edit Release' : 'New Release'}</h4>
        </div>
      </div>

      {globalError && <div className="alert alert-danger">{globalError}</div>}

      {artistChangedWithoutTracks && (
        <div className="alert alert-warning">
          <i className="bi bi-exclamation-triangle me-2"></i>
          You changed the release artist. Please re-select tracks below — all tracks must
          belong to the new artist.
        </div>
      )}

      <form onSubmit={submit}>
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white"><strong>Release Details</strong></div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label">Artist *</label>
                <select
                  className={`form-select ${fieldError('artist_id') ? 'is-invalid' : ''}`}
                  value={form.artist_id}
                  onChange={(e) => {
                    set('artist_id', e.target.value);
                    // Reset track selection when artist changes
                    if (isEdit) {
                      setSelectedTrackIds([]);
                    }
                  }}
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
                <label className="form-label">UPC</label>
                <input
                  type="text"
                  maxLength={14}
                  className={`form-control ${fieldError('upc') ? 'is-invalid' : ''}`}
                  value={form.upc}
                  onChange={(e) => set('upc', e.target.value)}
                  placeholder="12–14 digits"
                />
                {fieldError('upc') && (
                  <div className="invalid-feedback">{fieldError('upc')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">Release Date</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('release_date') ? 'is-invalid' : ''}`}
                  value={form.release_date}
                  onChange={(e) => set('release_date', e.target.value)}
                />
                {fieldError('release_date') && (
                  <div className="invalid-feedback">{fieldError('release_date')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">Pre-Save Date</label>
                <input
                  type="date"
                  className={`form-control ${fieldError('pre_save_date') ? 'is-invalid' : ''}`}
                  value={form.pre_save_date}
                  onChange={(e) => set('pre_save_date', e.target.value)}
                />
                {fieldError('pre_save_date') && (
                  <div className="invalid-feedback">{fieldError('pre_save_date')}</div>
                )}
              </div>

              <div className="col-12">
                <label className="form-label">Description</label>
                <textarea
                  rows={3}
                  className={`form-control ${fieldError('description') ? 'is-invalid' : ''}`}
                  value={form.description}
                  onChange={(e) => set('description', e.target.value)}
                  placeholder="Short description of the release"
                />
                {fieldError('description') && (
                  <div className="invalid-feedback">{fieldError('description')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">Cover Art Path (optional)</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('cover_art_path') ? 'is-invalid' : ''}`}
                  value={form.cover_art_path}
                  onChange={(e) => set('cover_art_path', e.target.value)}
                  placeholder="Path or URL to cover image"
                />
                {fieldError('cover_art_path') && (
                  <div className="invalid-feedback">{fieldError('cover_art_path')}</div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">Label Copy</label>
                <input
                  type="text"
                  className={`form-control ${fieldError('label_copy') ? 'is-invalid' : ''}`}
                  value={form.label_copy}
                  onChange={(e) => set('label_copy', e.target.value)}
                  placeholder="℗ 2026 Kay Factory Music"
                />
                {fieldError('label_copy') && (
                  <div className="invalid-feedback">{fieldError('label_copy')}</div>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Track selection */}
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Tracks on this Release</strong>
            <span className="text-muted small">
              {selectedTrackIds.length} selected
            </span>
          </div>
          <div className="card-body">
            {!form.artist_id ? (
              <div className="text-muted small py-3">
                Select an artist above to see available tracks.
              </div>
            ) : availableTracks.length === 0 ? (
              <div className="text-muted small py-3">
                No tracks found for this artist.{' '}
                <Link to="/tracks/new">Add a track first →</Link>
              </div>
            ) : (
              <div className="row g-3">
                <div className="col-lg-6">
                  <div className="form-label">Available Tracks</div>
                  <div className="border rounded p-2" style={{ maxHeight: 340, overflowY: 'auto' }}>
                    {availableTracks.map((t) => {
                      const isSelected = selectedSet.has(t.id);
                      return (
                        <div
                          key={t.id}
                          className={`d-flex align-items-center gap-2 p-2 rounded ${isSelected ? 'bg-light' : ''}`}
                          style={{ cursor: 'pointer' }}
                          onClick={() => toggleTrack(t.id)}
                        >
                          <input
                            type="checkbox"
                            className="form-check-input mt-0"
                            checked={isSelected}
                            onChange={() => toggleTrack(t.id)}
                            onClick={(e) => e.stopPropagation()}
                          />
                          <div className="flex-grow-1">
                            <div className="fw-semibold small">{t.title}</div>
                            <div className="text-muted" style={{ fontSize: '0.75rem' }}>
                              {t.track_code}
                              {t.isrc && ` · ${t.isrc}`}
                            </div>
                          </div>
                          {t.is_explicit && (
                            <span className="badge bg-danger-subtle text-danger-emphasis">E</span>
                          )}
                        </div>
                      );
                    })}
                  </div>
                </div>

                <div className="col-lg-6">
                  <div className="form-label">Selected Tracks (in order)</div>
                  <div className="border rounded p-2" style={{ maxHeight: 340, overflowY: 'auto' }}>
                    {selectedTrackIds.length === 0 ? (
                      <div className="text-muted small text-center py-4">
                        Select tracks on the left.
                      </div>
                    ) : (
                      selectedTrackIds.map((trackId, index) => {
                        const track = availableTracks.find((t) => t.id === trackId);
                        if (!track) return null;

                        return (
                          <div
                            key={trackId}
                            className="d-flex align-items-center gap-2 p-2 border-bottom"
                          >
                            <span
                              className="badge bg-dark"
                              style={{ minWidth: 28 }}
                            >
                              {index + 1}
                            </span>
                            <div className="flex-grow-1">
                              <div className="fw-semibold small">{track.title}</div>
                              <div className="text-muted" style={{ fontSize: '0.75rem' }}>
                                {track.track_code}
                              </div>
                            </div>
                            <button
                              type="button"
                              className="btn btn-sm btn-outline-secondary py-0 px-1"
                              title="Move up"
                              disabled={index === 0}
                              onClick={() => moveTrack(index, -1)}
                            >
                              <i className="bi bi-arrow-up"></i>
                            </button>
                            <button
                              type="button"
                              className="btn btn-sm btn-outline-secondary py-0 px-1"
                              title="Move down"
                              disabled={index === selectedTrackIds.length - 1}
                              onClick={() => moveTrack(index, 1)}
                            >
                              <i className="bi bi-arrow-down"></i>
                            </button>
                            <button
                              type="button"
                              className="btn btn-sm btn-outline-danger py-0 px-1"
                              title="Remove"
                              onClick={() => toggleTrack(trackId)}
                            >
                              <i className="bi bi-x"></i>
                            </button>
                          </div>
                        );
                      })
                    )}
                  </div>
                </div>
              </div>
            )}

            {fieldError('track_ids') && (
              <div className="text-danger small mt-2">{fieldError('track_ids')}</div>
            )}
            {fieldError('track_ids.0') && (
              <div className="text-danger small mt-2">{fieldError('track_ids.0')}</div>
            )}
          </div>
        </div>

        <div className="d-flex justify-content-end gap-2 mb-4">
          <Link to="/releases" className="btn btn-outline-secondary">Cancel</Link>
          <button type="submit" className="btn btn-dark" disabled={submitting}>
            {submitting ? 'Saving…' : (isEdit ? 'Save Changes' : 'Create Release')}
          </button>
        </div>
      </form>
    </>
  );
}