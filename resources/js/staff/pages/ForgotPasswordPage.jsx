import { useState } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { settingsApi } from '../settingsApi';
import { isAuthenticated } from '../auth';

export default function ForgotPasswordPage() {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const [error, setError] = useState('');

  if (isAuthenticated()) {
    return <Navigate to="/" replace />;
  }

  const submit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await settingsApi.requestReset(email);
      setSent(true);
    } catch (err) {
      if (err.response?.status === 422) {
        setError('Please enter a valid email address.');
      } else if (!err.response) {
        setError('Cannot reach the server. Check your connection.');
      } else {
        setError('Something went wrong. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  };

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

        {sent ? (
          <>
            <h1 className="kfm-login__title">Check your inbox</h1>
            <p className="kfm-login__sub">
              If an account exists for <strong>{email}</strong>, a password reset
              link has been sent. The link expires in 60 minutes.
            </p>
            <Link to="/login" className="kfm-login__back">
              Back to sign in
            </Link>
          </>
        ) : (
          <>
            <h1 className="kfm-login__title">Forgot password?</h1>
            <p className="kfm-login__sub">
              Enter the email address associated with your account and we&apos;ll
              send you a reset link.
            </p>

            <form onSubmit={submit} className="kfm-login__form">
              <label className="kfm-login__field">
                <span>Email</span>
                <input
                  type="email"
                  required
                  autoFocus
                  autoComplete="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="you@kayfactorymusic.com"
                  disabled={loading}
                />
              </label>

              {error && <div className="kfm-login__error">{error}</div>}

              <button
                type="submit"
                className="kfm-login__submit"
                disabled={loading}
              >
                {loading ? 'Sending…' : 'Send Reset Link'}
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