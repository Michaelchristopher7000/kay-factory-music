import { useState } from 'react';
import { Link, Navigate, useParams, useSearchParams, useNavigate } from 'react-router-dom';
import { settingsApi } from '../settingsApi';
import { isAuthenticated } from '../auth';

export default function ResetPasswordPage() {
  const { token } = useParams();
  const [params] = useSearchParams();
  const navigate = useNavigate();

  const [email, setEmail] = useState(params.get('email') || '');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [show, setShow] = useState({ password: false, confirm: false });

  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);
  const [errors, setErrors] = useState({});
  const [globalError, setGlobalError] = useState('');

  if (isAuthenticated()) {
    return <Navigate to="/" replace />;
  }

  if (!token) {
    return (
      <div className="kfm-login">
        <div className="kfm-login__panel">
          <h1 className="kfm-login__title">Invalid reset link</h1>
          <p className="kfm-login__sub">
            This password reset link is missing or malformed.
          </p>
          <Link to="/forgot-password" className="kfm-login__back">
            Request a new link
          </Link>
        </div>
      </div>
    );
  }

  const submit = async (e) => {
    e.preventDefault();
    setErrors({});
    setGlobalError('');
    setLoading(true);

    try {
      await settingsApi.resetPassword({
        token,
        email,
        password,
        password_confirmation: confirm,
      });

      setDone(true);
      setTimeout(() => navigate('/login', { replace: true }), 2500);
    } catch (err) {
      if (err.response?.status === 422) {
        const data = err.response.data;
        if (data.errors) setErrors(data.errors);
        if (data.message) setGlobalError(data.message);
      } else {
        setGlobalError('Could not reset password. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  const toggle = (key) => setShow((s) => ({ ...s, [key]: !s[key] }));
  const fieldError = (key) => errors[key]?.[0];

  return (
    <div className="kfm-login">
      <div className="kfm-login-bg" aria-hidden="true">
        <video
          className="kfm-login-bg__video"
          autoPlay
          muted
          loop
          playsInline
          preload="auto"
          poster="https://picsum.photos/seed/kfm-login-poster/1920/1080?grayscale&blur=4"
        >
          <source src="/videos/kfm-login.mp4" type="video/mp4" />
        </video>
        <div className="kfm-login-bg__overlay"></div>
      </div>

      <div className="kfm-login__panel">
        <div className="kfm-login__brand">
          <i className="bi bi-vinyl-fill"></i>
          <span>Kay Factory Music</span>
        </div>
        <div className="kfm-login__tagline">Where Sound Becomes Legacy</div>

        {done ? (
          <>
            <h1 className="kfm-login__title">Password updated</h1>
            <p className="kfm-login__sub">
              Your password has been reset. Redirecting you to sign in…
            </p>
            <Link to="/login" className="kfm-login__back">
              Go to sign in
            </Link>
          </>
        ) : (
          <>
            <h1 className="kfm-login__title">Set a new password</h1>
            <p className="kfm-login__sub">
              Choose a new password for <strong>{email || 'your account'}</strong>.
            </p>

            <form onSubmit={submit} className="kfm-login__form">
              {!params.get('email') && (
                <label className="kfm-login__field">
                  <span>Email</span>
                  <input
                    type="email"
                    required
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    autoComplete="email"
                    disabled={loading}
                  />
                </label>
              )}

              <label className="kfm-login__field">
                <span>New password</span>
                <div className="kfm-password-field">
                  <input
                    type={show.password ? 'text' : 'password'}
                    required
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    autoComplete="new-password"
                    disabled={loading}
                  />
                  <button
                    type="button"
                    className="kfm-password-toggle"
                    onClick={() => toggle('password')}
                    tabIndex={-1}
                  >
                    <i className={`bi ${show.password ? 'bi-eye-slash' : 'bi-eye'}`}></i>
                  </button>
                </div>
                {fieldError('password') && (
                  <div className="text-danger small mt-1">{fieldError('password')}</div>
                )}
              </label>

              <label className="kfm-login__field">
                <span>Confirm new password</span>
                <div className="kfm-password-field">
                  <input
                    type={show.confirm ? 'text' : 'password'}
                    required
                    value={confirm}
                    onChange={(e) => setConfirm(e.target.value)}
                    autoComplete="new-password"
                    disabled={loading}
                  />
                  <button
                    type="button"
                    className="kfm-password-toggle"
                    onClick={() => toggle('confirm')}
                    tabIndex={-1}
                  >
                    <i className={`bi ${show.confirm ? 'bi-eye-slash' : 'bi-eye'}`}></i>
                  </button>
                </div>
              </label>

              {globalError && <div className="kfm-login__error">{globalError}</div>}

              <button
                type="submit"
                className="kfm-login__submit"
                disabled={loading}
              >
                {loading ? 'Updating…' : 'Reset Password'}
              </button>
            </form>

            <Link to="/login" className="kfm-login__back">
              Back to sign in
            </Link>
          </>
        )}
      </div>
    </div>
  );
}