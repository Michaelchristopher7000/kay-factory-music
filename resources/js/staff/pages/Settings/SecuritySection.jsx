import { useState } from 'react';
import { useAuth } from '../../auth';
import { settingsApi } from '../../settingsApi';
import PasswordStrengthMeter from '../../components/PasswordStrengthMeter';
import SecurityActivityCard from './SecurityActivityCard';
import TwoFactorCard from './TwoFactorCard';

export default function SecuritySection() {
  const { user } = useAuth();

  const [current, setCurrent] = useState('');
  const [next, setNext] = useState('');
  const [confirm, setConfirm] = useState('');
  const [show, setShow] = useState({ current: false, next: false, confirm: false });

  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});
  const [success, setSuccess] = useState('');
  const [globalError, setGlobalError] = useState('');

  const isSuperAdmin = user?.role?.slug === 'super-admin';

  const toggle = (key) => setShow((s) => ({ ...s, [key]: !s[key] }));

  const submit = async (e) => {
    e.preventDefault();
    setErrors({});
    setGlobalError('');
    setSuccess('');
    setSaving(true);

    try {
      const res = await settingsApi.changePassword({
        current_password: current,
        password: next,
        password_confirmation: confirm,
      });

      const revoked = res.data?.sessions_revoked ?? 0;

      setCurrent('');
      setNext('');
      setConfirm('');
      setSuccess(
        revoked > 0
          ? `Password updated. ${revoked} other session${revoked > 1 ? 's were' : ' was'} signed out.`
          : 'Password updated successfully.'
      );
    } catch (err) {
      if (err.response?.status === 401) return;
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors || {});
      } else {
        setGlobalError('Could not change password. Please try again.');
      }
    } finally {
      setSaving(false);
    }
  };

  const fieldError = (key) => errors[key]?.[0];

  const renderField = (key, label, value, setter, autoComplete) => (
    <div className="col-12">
      <label className="form-label">{label}</label>
      <div className="kfm-password-field">
        <input
          type={show[key] ? 'text' : 'password'}
          className={`form-control ${fieldError(key) ? 'is-invalid' : ''}`}
          value={value}
          onChange={(e) => setter(e.target.value)}
          autoComplete={autoComplete}
          required
        />
        <button
          type="button"
          className="kfm-password-toggle"
          onClick={() => toggle(key)}
          tabIndex={-1}
          aria-label={show[key] ? 'Hide password' : 'Show password'}
        >
          <i className={`bi ${show[key] ? 'bi-eye-slash' : 'bi-eye'}`}></i>
        </button>
        {fieldError(key) && <div className="invalid-feedback d-block">{fieldError(key)}</div>}
      </div>
    </div>
  );

  return (
    <>
      <div className="kfm-settings-card">
        <div className="kfm-settings-card__header">
          <div>
            <h5 className="kfm-settings-card__title">Security</h5>
            <p className="kfm-settings-card__sub">
              Change your password. Use at least 12 characters.
            </p>
          </div>
        </div>

        <div className="kfm-settings-card__body">
          {globalError && <div className="alert alert-danger mb-3">{globalError}</div>}
          {success && <div className="alert alert-success mb-3">{success}</div>}

          <form onSubmit={submit} className="kfm-settings-form">
            <div className="row g-3">
              {renderField('current_password', 'Current password', current, setCurrent, 'current-password')}

              <div className="col-12">
                <label className="form-label">New password</label>
                <div className="kfm-password-field">
                  <input
                    type={show.next ? 'text' : 'password'}
                    className={`form-control ${fieldError('password') ? 'is-invalid' : ''}`}
                    value={next}
                    onChange={(e) => setNext(e.target.value)}
                    autoComplete="new-password"
                    required
                  />
                  <button
                    type="button"
                    className="kfm-password-toggle"
                    onClick={() => toggle('next')}
                    tabIndex={-1}
                    aria-label={show.next ? 'Hide password' : 'Show password'}
                  >
                    <i className={`bi ${show.next ? 'bi-eye-slash' : 'bi-eye'}`}></i>
                  </button>
                  {fieldError('password') && (
                    <div className="invalid-feedback d-block">{fieldError('password')}</div>
                  )}
                </div>
                <PasswordStrengthMeter password={next} />
              </div>

              {renderField('password_confirmation', 'Confirm new password', confirm, setConfirm, 'new-password')}
            </div>

            <div className="kfm-settings-form__actions">
              <button type="submit" className="btn btn-dark" disabled={saving}>
                {saving ? 'Updating…' : 'Update Password'}
              </button>
            </div>
          </form>
        </div>
      </div>

      {isSuperAdmin && <TwoFactorCard />}

      <SecurityActivityCard />
    </>
  );
}