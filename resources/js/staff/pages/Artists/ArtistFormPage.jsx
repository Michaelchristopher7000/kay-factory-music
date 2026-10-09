
import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import api from '../../api';

const STATUSES = [
  { value: 'in_talks', label: 'In Talks' },
  { value: 'signed', label: 'Signed' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'former', label: 'Former' },
];

const SOCIAL_KEYS = [
  'spotify',
  'apple_music',
  'instagram',
  'twitter',
  'youtube',
  'soundcloud',
  'website',
];

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
  social_links: SOCIAL_KEYS.reduce((acc, key) => {
    acc[key] = '';
    return acc;
  }, {}),
};

const MAX_IMAGE_SIZE = 5 * 1024 * 1024;

const ALLOWED_IMAGE_TYPES = [
  'image/jpeg',
  'image/png',
  'image/webp',
  'image/gif',
];

export default function ArtistFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const isEdit = Boolean(id);

  const [form, setForm] = useState(emptyForm);
  const [avatarFile, setAvatarFile] = useState(null);
  const [avatarPreview, setAvatarPreview] = useState('');

  const [loading, setLoading] = useState(isEdit);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');

  // Load existing artist details when editing.
  useEffect(() => {
    if (!isEdit) {
      setLoading(false);
      return;
    }

    let cancelled = false;

    const loadArtist = async () => {
      try {
        const { data } = await api.get(`/artists/${id}`);

        if (cancelled) return;

        const artist = data.data;

        setForm({
          name: artist.name || '',
          real_name: artist.real_name || '',
          email: artist.email || '',
          phone: artist.phone || '',
          genre: artist.genre || '',
          country: artist.country || '',
          city: artist.city || '',
          status: artist.status || 'in_talks',
          manager_id: artist.manager_id || artist.manager?.id || '',
          bio: artist.bio || '',
          avatar: artist.avatar || '',
          social_links: SOCIAL_KEYS.reduce((acc, key) => {
            acc[key] = artist.social_links?.[key] || '';
            return acc;
          }, {}),
        });
      } catch (err) {
        if (cancelled) return;

        if (err.response?.status !== 401) {
          setGlobalError(
            err.response?.data?.message || 'Could not load artist.'
          );
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    };

    loadArtist();

    return () => {
      cancelled = true;
    };
  }, [id, isEdit]);

  // Release temporary browser image URLs when they are replaced or removed.
  useEffect(() => {
    return () => {
      if (avatarPreview.startsWith('blob:')) {
        URL.revokeObjectURL(avatarPreview);
      }
    };
  }, [avatarPreview]);

  const set = (field, value) => {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));
  };

  const setSocial = (key, value) => {
    setForm((current) => ({
      ...current,
      social_links: {
        ...current.social_links,
        [key]: value,
      },
    }));
  };

  const fieldError = (key) => errors[key]?.[0];

  const handleAvatarChange = (event) => {
    const file = event.target.files?.[0] || null;

    setErrors((current) => ({
      ...current,
      avatar: undefined,
    }));

    setGlobalError('');

    if (!file) {
      setAvatarFile(null);
      setAvatarPreview('');
      return;
    }

    if (!ALLOWED_IMAGE_TYPES.includes(file.type)) {
      setAvatarFile(null);
      setAvatarPreview('');
      event.target.value = '';

      setErrors((current) => ({
        ...current,
        avatar: [
          'Choose a JPG, PNG, WebP, or GIF image.',
        ],
      }));

      return;
    }

    if (file.size > MAX_IMAGE_SIZE) {
      setAvatarFile(null);
      setAvatarPreview('');
      event.target.value = '';

      setErrors((current) => ({
        ...current,
        avatar: ['Image size must not exceed 5 MB.'],
      }));

      return;
    }

    setAvatarFile(file);
    setAvatarPreview(URL.createObjectURL(file));
  };

  const removeSelectedAvatar = () => {
    setAvatarFile(null);
    setAvatarPreview('');

    const input = document.getElementById('artist-avatar-input');

    if (input) {
      input.value = '';
    }
  };

  const submit = async (event) => {
    event.preventDefault();

    setSubmitting(true);
    setErrors({});
    setGlobalError('');

    const payload = new FormData();

    payload.append('name', form.name.trim());
    payload.append('real_name', form.real_name);
    payload.append('email', form.email);
    payload.append('phone', form.phone);
    payload.append('genre', form.genre);
    payload.append('country', form.country);
    payload.append('city', form.city);
    payload.append('status', form.status);
    payload.append('manager_id', form.manager_id);
    payload.append('bio', form.bio);

    // Send social links in the structure Laravel expects.
    SOCIAL_KEYS.forEach((key) => {
      const value = form.social_links[key]?.trim() || '';

      if (value) {
        payload.append(`social_links[${key}]`, value);
      }
    });

    // Only send an avatar when the user selects a new image.
    // Leaving this out preserves the current image during editing.
    if (avatarFile) {
      payload.append('avatar', avatarFile);
    }

    // Laravel method spoofing allows multipart artist updates.
    if (isEdit) {
      payload.append('_method', 'PATCH');
    }

    try {
      await api.post(
        isEdit ? `/artists/${id}` : '/artists',
        payload
      );

      navigate('/artists');
    } catch (err) {
      if (err.response?.status === 401) {
        return;
      }

      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setGlobalError(
          err.response?.data?.message || 'Could not save artist.'
        );
      }
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="text-center py-5 text-muted">
        <div
          className="spinner-border spinner-border-sm me-2"
          role="status"
        />
        Loading...
      </div>
    );
  }

  const displayedAvatar = avatarPreview || form.avatar;

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <Link
            to="/artists"
            className="text-muted text-decoration-none small"
          >
            <i className="bi bi-arrow-left me-1" />
            Back to Artists
          </Link>

          <h4 className="mb-0 mt-2">
            {isEdit ? 'Edit Artist' : 'New Artist'}
          </h4>
        </div>
      </div>

      {globalError && (
        <div className="alert alert-danger" role="alert">
          {globalError}
        </div>
      )}

      <form onSubmit={submit}>
        {/* Basic Information */}
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white">
            <strong>Basic Information</strong>
          </div>

          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-6">
                <label className="form-label">
                  Name *
                </label>

                <input
                  type="text"
                  className={`form-control ${
                    fieldError('name') ? 'is-invalid' : ''
                  }`}
                  value={form.name}
                  onChange={(event) => set('name', event.target.value)}
                  required
                />

                {fieldError('name') && (
                  <div className="invalid-feedback">
                    {fieldError('name')}
                  </div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">
                  Real Name
                </label>

                <input
                  type="text"
                  className={`form-control ${
                    fieldError('real_name') ? 'is-invalid' : ''
                  }`}
                  value={form.real_name}
                  onChange={(event) => set('real_name', event.target.value)}
                />

                {fieldError('real_name') && (
                  <div className="invalid-feedback">
                    {fieldError('real_name')}
                  </div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">
                  Email
                </label>

                <input
                  type="email"
                  className={`form-control ${
                    fieldError('email') ? 'is-invalid' : ''
                  }`}
                  value={form.email}
                  onChange={(event) => set('email', event.target.value)}
                />

                {fieldError('email') && (
                  <div className="invalid-feedback">
                    {fieldError('email')}
                  </div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">
                  Phone
                </label>

                <input
                  type="text"
                  className={`form-control ${
                    fieldError('phone') ? 'is-invalid' : ''
                  }`}
                  value={form.phone}
                  onChange={(event) => set('phone', event.target.value)}
                />

                {fieldError('phone') && (
                  <div className="invalid-feedback">
                    {fieldError('phone')}
                  </div>
                )}
              </div>

              <div className="col-12">
                <label className="form-label">
                  Bio
                </label>

                <textarea
                  rows={4}
                  className={`form-control ${
                    fieldError('bio') ? 'is-invalid' : ''
                  }`}
                  value={form.bio}
                  onChange={(event) => set('bio', event.target.value)}
                />

                {fieldError('bio') && (
                  <div className="invalid-feedback">
                    {fieldError('bio')}
                  </div>
                )}
              </div>

              {/* Artist Image Upload */}
              <div className="col-12">
                <label
                  htmlFor="artist-avatar-input"
                  className="form-label"
                >
                  Artist Image
                </label>

                <div className="border rounded-3 p-3">
                  <div className="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                    {displayedAvatar ? (
                      <img
                        src={displayedAvatar}
                        alt="Artist preview"
                        className="rounded border"
                        style={{
                          width: '120px',
                          height: '120px',
                          objectFit: 'cover',
                          backgroundColor: '#f8f9fa',
                        }}
                        onError={(event) => {
                          event.currentTarget.style.display = 'none';
                        }}
                      />
                    ) : (
                      <div
                        className="rounded border d-flex flex-column align-items-center justify-content-center text-muted"
                        style={{
                          width: '120px',
                          height: '120px',
                          backgroundColor: '#f8f9fa',
                          flexShrink: 0,
                        }}
                      >
                        <i className="bi bi-person-circle fs-1" />
                        <small>No image</small>
                      </div>
                    )}

                    <div className="flex-grow-1">
                      <input
                        id="artist-avatar-input"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        className={`form-control ${
                          fieldError('avatar') ? 'is-invalid' : ''
                        }`}
                        onChange={handleAvatarChange}
                      />

                      <div className="form-text">
                        JPG, PNG, WebP, or GIF. Maximum size: 5 MB.
                      </div>

                      {avatarFile && (
                        <div className="small text-success mt-2">
                          <i className="bi bi-check-circle me-1" />
                          {avatarFile.name}
                        </div>
                      )}

                      {fieldError('avatar') && (
                        <div className="text-danger small mt-2">
                          {fieldError('avatar')}
                        </div>
                      )}

                      {(avatarFile || avatarPreview) && (
                        <button
                          type="button"
                          className="btn btn-sm btn-outline-danger mt-2"
                          onClick={removeSelectedAvatar}
                        >
                          <i className="bi bi-x-circle me-1" />
                          Remove selected image
                        </button>
                      )}

                      {!avatarFile && form.avatar && (
                        <div className="form-text mt-2">
                          Leave this field unchanged to keep the current image.
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Classification */}
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white">
            <strong>Classification</strong>
          </div>

          <div className="card-body">
            <div className="row g-3">
              <div className="col-md-4">
                <label className="form-label">
                  Genre
                </label>

                <input
                  type="text"
                  className={`form-control ${
                    fieldError('genre') ? 'is-invalid' : ''
                  }`}
                  value={form.genre}
                  onChange={(event) => set('genre', event.target.value)}
                  placeholder="Afrobeats, Amapiano, R&B..."
                />

                {fieldError('genre') && (
                  <div className="invalid-feedback">
                    {fieldError('genre')}
                  </div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">
                  Country
                </label>

                <input
                  type="text"
                  className={`form-control ${
                    fieldError('country') ? 'is-invalid' : ''
                  }`}
                  value={form.country}
                  onChange={(event) => set('country', event.target.value)}
                />

                {fieldError('country') && (
                  <div className="invalid-feedback">
                    {fieldError('country')}
                  </div>
                )}
              </div>

              <div className="col-md-4">
                <label className="form-label">
                  City
                </label>

                <input
                  type="text"
                  className={`form-control ${
                    fieldError('city') ? 'is-invalid' : ''
                  }`}
                  value={form.city}
                  onChange={(event) => set('city', event.target.value)}
                />

                {fieldError('city') && (
                  <div className="invalid-feedback">
                    {fieldError('city')}
                  </div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">
                  Status *
                </label>

                <select
                  className={`form-select ${
                    fieldError('status') ? 'is-invalid' : ''
                  }`}
                  value={form.status}
                  onChange={(event) => set('status', event.target.value)}
                >
                  {STATUSES.map((status) => (
                    <option
                      key={status.value}
                      value={status.value}
                    >
                      {status.label}
                    </option>
                  ))}
                </select>

                {fieldError('status') && (
                  <div className="invalid-feedback">
                    {fieldError('status')}
                  </div>
                )}
              </div>

              <div className="col-md-6">
                <label className="form-label">
                  Manager ID (optional)
                </label>

                <input
                  type="number"
                  min="1"
                  className={`form-control ${
                    fieldError('manager_id') ? 'is-invalid' : ''
                  }`}
                  value={form.manager_id}
                  onChange={(event) => set('manager_id', event.target.value)}
                  placeholder="Leave blank if none"
                />

                {fieldError('manager_id') && (
                  <div className="invalid-feedback">
                    {fieldError('manager_id')}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Social Links */}
        <div className="card border-0 shadow-sm mb-3">
          <div className="card-header bg-white">
            <strong>Social Links</strong>
          </div>

          <div className="card-body">
            <div className="row g-3">
              {SOCIAL_KEYS.map((key) => (
                <div key={key} className="col-md-6">
                  <label className="form-label">
                    {SOCIAL_LABELS[key]}
                  </label>

                  <input
                    type="url"
                    className={`form-control ${
                      fieldError(`social_links.${key}`)
                        ? 'is-invalid'
                        : ''
                    }`}
                    value={form.social_links[key]}
                    onChange={(event) => setSocial(key, event.target.value)}
                    placeholder="https://..."
                  />

                  {fieldError(`social_links.${key}`) && (
                    <div className="invalid-feedback">
                      {fieldError(`social_links.${key}`)}
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Form Actions */}
        <div className="d-flex justify-content-end gap-2 mb-4">
          <Link
            to="/artists"
            className="btn btn-outline-secondary"
          >
            Cancel
          </Link>

          <button
            type="submit"
            className="btn btn-dark"
            disabled={submitting}
          >
            {submitting ? (
              <>
                <span
                  className="spinner-border spinner-border-sm me-2"
                  role="status"
                />
                Saving...
              </>
            ) : (
              isEdit ? 'Save Changes' : 'Create Artist'
            )}
          </button>
        </div>
      </form>
    </>
  );
}
