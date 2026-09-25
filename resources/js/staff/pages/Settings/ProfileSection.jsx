import { useEffect, useRef, useState } from 'react';
import { settingsApi } from '../../settingsApi';
import { useAuth } from '../../auth';

export default function ProfileSection() {
  const { user, setUser } = useAuth();

  const [name, setName] = useState(user?.name || '');
  const [email, setEmail] = useState(user?.email || '');
  const [currentPassword, setCurrentPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);

  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [removing, setRemoving] = useState(false);
  const [errors, setErrors] = useState({});
  const [success, setSuccess] = useState('');
  const [globalError, setGlobalError] = useState('');

  const fileRef = useRef(null);

  // Keep form in sync if user updates elsewhere
  useEffect(() => {
    setName(user?.name || '');
    setEmail(user?.email || '');
  }, [user?.name, user?.email]);

  const emailChanged = email.trim().toLowerCase() !== (user?.email || '').toLowerCase();
  const avatarUrl = user?.avatar_url;
  const initial = (user?.name || 'S').charAt(0).toUpperCase();

  const memberSince = user?.created_at
    ? new Date(user.created_at).toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
    : '—';

  const handleSave = async (e) => {
    e.preventDefault();
    setErrors({});
    setGlobalError('');
    setSuccess('');
    setSaving(true);

    const payload = { name };
    if (emailChanged) {
      payload.email = email;
      payload.current_password = currentPassword;
    }

    try {
      const { data } = await settingsApi.updateProfile(payload);
      // Server returns { data: UserResource } for this endpoint
      const updated = data.data || data.user || data;
      if (setUser) setUser(updated);
      setCurrentPassword('');
      setSuccess('Profile updated successfully.');
    } catch (err) {
      if (err.response?.status === 401) return;
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setGlobalError('Could not save changes. Please try again.');
      }
    } finally {
      setSaving(false);
    }
  };

  const handleAvatarChange = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setErrors({});
    setGlobalError('');
    setSuccess('');
    setUploading(true);

    try {
      const { data } = await settingsApi.uploadAvatar(file);
      const updated = data.user || data.data || data;
      if (setUser) setUser(updated);
      setSuccess('Profile picture updated.');
    } catch (err) {
      if (err.response?.status === 401) return;
      if (err.response?.status === 422) {
        const first = Object.values(err.response.data.errors || {})[0]?.[0];
        setGlobalError(first || 'Invalid image.');
      } else {
        setGlobalError('Could not upload image.');
      }
    } finally {
      setUploading(false);
      if (fileRef.current) fileRef.current.value = '';
    }
  };

  const handleAvatarRemove = async () => {
    if (!avatarUrl) return;
    if (!window.confirm('Remove your profile picture?')) return;

    setGlobalError('');
    setSuccess('');
    setRemoving(true);

    try {
      const { data } = await settingsApi.removeAvatar();
      const updated = data.user || data.data || data;
      if (setUser) setUser(updated);
      setSuccess('Profile picture removed.');
    } catch (err) {
      if (err.response?.status === 401) return;
      setGlobalError('Could not remove image.');
    } finally {
      setRemoving(false);
    }
  };

  const fieldError = (key) => errors[key]?.[0];

  return (
    <div className="kfm-settings-card">
      <div className="kfm-settings-card__header">
        <div>
          <h5 className="kfm-settings-card__title">Profile</h5>
          <p className="kfm-settings-card__sub">Your personal details on the platform.</p>
        </div>
      </div>

      <div className="kfm-settings-card__body">
        {/* Avatar block */}
        <div className="kfm-profile-avatar-block">
          <div className="kfm-profile-avatar">
            {avatarUrl ? (
              <img src={avatarUrl} alt={user?.name || ''} />
            ) : (
              <span className="kfm-profile-avatar__initial">{initial}</span>
            )}
          </div>
          <div className="kfm-profile-avatar__actions">
            <div className="kfm-profile-avatar__buttons">
              <button
                type="button"
                className="btn btn-sm btn-outline-secondary"
                onClick={() => fileRef.current?.click()}
                disabled={uploading || removing}
              >
                {uploading ? 'Uploading…' : (avatarUrl ? 'Change photo' : 'Upload photo')}
              </button>
              {avatarUrl && (
                <button
                  type="button"
                  className="btn btn-sm btn-outline-danger"
                  onClick={handleAvatarRemove}
                  disabled={removing || uploading}
                >
                  {removing ? 'Removing…' : 'Remove photo'}
                </button>
              )}
            </div>
            <div className="kfm-profile-avatar__hint">
              JPEG, PNG or WebP · max 2 MB
            </div>
          </div>
          <input
            type="file"
            ref={fileRef}
            accept="image/jpeg,image/png,image/webp"
            style={{ display: 'none' }}
            onChange={handleAvatarChange}
          />
        </div>

        {/* Feedback */}
        {globalError && <div className="alert alert-danger mb-3">{globalError}</div>}
        {success && <div className="alert alert-success mb-3">{success}</div>}

        <form onSubmit={handleSave} className="kfm-settings-form">
          <div className="row g-3">
            <div className="col-md-6">
              <label className="form-label">Full name</label>
              <input
                type="text"
                className={`form-control ${fieldError('name') ? 'is-invalid' : ''}`}
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
              />
              {fieldError('name') && <div className="invalid-feedback">{fieldError('name')}</div>}
            </div>

            <div className="col-md-6">
              <label className="form-label">Email</label>
              <input
                type="email"
                className={`form-control ${fieldError('email') ? 'is-invalid' : ''}`}
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
              {fieldError('email') && <div className="invalid-feedback">{fieldError('email')}</div>}
            </div>

            {emailChanged && (
              <div className="col-12">
                <div className="kfm-settings-note">
                  <i className="bi bi-info-circle me-2"></i>
                  You&apos;re changing your email. Enter your current password to confirm.
                </div>

                <label className="form-label mt-3">Current password</label>
                <div className="kfm-password-field">
                  <input
                    type={showPassword ? 'text' : 'password'}
                    className={`form-control ${fieldError('current_password') ? 'is-invalid' : ''}`}
                    value={currentPassword}
                    onChange={(e) => setCurrentPassword(e.target.value)}
                    autoComplete="current-password"
                    required
                  />
                  <button
                    type="button"
                    className="kfm-password-toggle"
                    onClick={() => setShowPassword((v) => !v)}
                    tabIndex={-1}
                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                  >
                    <i className={`bi ${showPassword ? 'bi-eye-slash' : 'bi-eye'}`}></i>
                  </button>
                  {fieldError('current_password') && (
                    <div className="invalid-feedback d-block">{fieldError('current_password')}</div>
                  )}
                </div>
              </div>
            )}

            <div className="col-md-6">
              <label className="form-label">Role</label>
              <input
                type="text"
                className="form-control"
                value={user?.role?.name || '—'}
                disabled
                readOnly
              />
              <div className="form-text">Role is assigned by a Super Admin.</div>
            </div>

            <div className="col-md-6">
              <label className="form-label">Member since</label>
              <input
                type="text"
                className="form-control"
                value={memberSince}
                disabled
                readOnly
              />
            </div>
          </div>

          <div className="kfm-settings-form__actions">
            <button type="submit" className="btn btn-dark" disabled={saving}>
              {saving ? 'Saving…' : 'Save Changes'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}