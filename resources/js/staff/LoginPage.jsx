import { useEffect, useState } from 'react';
import { useNavigate, useLocation, Navigate, Link } from 'react-router-dom';
import api from './api';
import { twoFactorApi, challengeStore } from './twoFactorApi';
import { setSession, isAuthenticated } from './auth';

export default function LoginPage() {
  const navigate = useNavigate();
  const location = useLocation();

  // Capture ONCE — if the user got bounced here after an idle timeout,
  // show a friendly explanation on the credentials step.
  const [idleExpired] = useState(
    () => location.state?.reason === 'idle_timeout'
  );

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);

  // Step: 'credentials' | 'totp'
  const [step, setStep] = useState('credentials');
  const [challengeToken, setChallengeToken] = useState('');
  const [totpCode, setTotpCode] = useState('');

  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // Restore an in-flight challenge after a page refresh
  useEffect(() => {
    const saved = challengeStore.get();

    if (saved) {
      setChallengeToken(saved);
      setStep('totp');
    }
  }, []);

  if (isAuthenticated()) {
    return <Navigate to="/" replace />;
  }

  const finishLogin = (data) => {
    challengeStore.clear();
    setSession(data.token, data.user);

    const redirectTo = location.state?.from?.pathname || '/';

    navigate(redirectTo, {
      replace: true,
    });
  };

  /* ============================================================
     STEP 1 — EMAIL + PASSWORD
     ============================================================ */

  const submitCredentials = async (e) => {
    e.preventDefault();

    setError('');
    setLoading(true);

    try {
      const { data } = await api.post('/login', {
        email,
        password,
      });

      if (data?.requires_2fa && data?.challenge_token) {
        challengeStore.set(data.challenge_token);

        setChallengeToken(data.challenge_token);
        setPassword('');
        setTotpCode('');
        setStep('totp');

        return;
      }

      finishLogin(data);
    } catch (err) {
      const status = err.response?.status;
      const apiMessage = err.response?.data?.message;
      const emailError = err.response?.data?.errors?.email?.[0];

      if (status === 429) {
        setError(
          apiMessage ||
            'Too many login attempts. Please try again later.'
        );
      } else if (status === 401 || status === 422) {
        setError(
          emailError ||
            'Invalid email or password.'
        );
      } else if (!err.response) {
        setError(
          'Cannot reach the server. Check your connection.'
        );
      } else {
        setError(
          apiMessage ||
            'Something went wrong. Please try again.'
        );
      }
    } finally {
      setLoading(false);
    }
  };

  /* ============================================================
     STEP 2 — TOTP / RECOVERY CODE
     ============================================================ */

  const submitTotp = async (e) => {
    e.preventDefault();

    setError('');
    setLoading(true);

    try {
      const { data } = await twoFactorApi.loginWith2fa(
        challengeToken,
        totpCode.trim()
      );

      finishLogin(data);
    } catch (err) {
      const status = err.response?.status;
      const apiMessage = err.response?.data?.message;
      const challengeError =
        err.response?.data?.errors?.challenge_token?.[0];
      const codeError =
        err.response?.data?.errors?.code?.[0];

      // Challenge expired / consumed
      if (status === 422 && challengeError) {
        challengeStore.clear();

        setChallengeToken('');
        setTotpCode('');
        setStep('credentials');

        setError(
          'Your verification session expired. Please sign in again.'
        );

        return;
      }

      if (status === 422) {
        setError(
          codeError ||
            'The verification code is invalid or has expired.'
        );
      } else if (status === 429) {
        setError(
          apiMessage ||
            'Too many verification attempts. Please try again later.'
        );
      } else if (!err.response) {
        setError(
          'Cannot reach the server. Check your connection.'
        );
      } else {
        setError(
          apiMessage ||
            'Something went wrong. Please try again.'
        );
      }
    } finally {
      setLoading(false);
    }
  };

  /* ============================================================
     BACK TO CREDENTIALS
     ============================================================ */

  const backToCredentials = () => {
    challengeStore.clear();

    setChallengeToken('');
    setTotpCode('');
    setError('');
    setStep('credentials');
  };

  /* ============================================================
     RENDER
     ============================================================ */

  return (
    <div className="kfm-login">

      {/* ========================================================
          BACKGROUND VIDEO
         ======================================================== */}

      <div
        className="kfm-login-bg"
        aria-hidden="true"
      >
        <video
          className="kfm-login-bg__video"
          autoPlay
          muted
          loop
          playsInline
          preload="auto"
        >
          <source
            src="/videos/kfm-login.mp4"
            type="video/mp4"
          />
        </video>

        <div className="kfm-login-bg__overlay"></div>
      </div>


      {/* ========================================================
          LOGIN PANEL
         ======================================================== */}

      <div className="kfm-login__panel">

        {/* ======================================================
            BRAND / LOGO
           ====================================================== */}

        <div className="kfm-login__brand">

          <img
            src="/images/kfm-logo-white.png"
            alt="Kay Factory Music"
            className="kfm-login__logo"
          />

         

        </div>

        <div className="kfm-login__tagline">
          Where Sound Becomes Legacy
        </div>


        {/* ======================================================
            CREDENTIALS
           ====================================================== */}

        {step === 'credentials' && (
          <>
            <h1 className="kfm-login__title">
              Welcome back.
            </h1>

            <p className="kfm-login__sub">
              Sign in to access your dashboard.
            </p>


            {/* IDLE TIMEOUT MESSAGE */}

            {idleExpired && (
              <div
                className="alert alert-warning kfm-login__info"
                role="alert"
              >
                <i className="bi bi-clock-history me-2"></i>

                Your session expired due to inactivity.
                Please sign in again.
              </div>
            )}


            {/* LOGIN FORM */}

            <form
              onSubmit={submitCredentials}
              className="kfm-login__form"
            >

              {/* EMAIL */}

              <label className="kfm-login__field">
                <span>Email</span>

                <input
                  type="email"
                  required
                  autoComplete="email"
                  autoFocus
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="you@kayfactorymusic.com"
                  disabled={loading}
                />
              </label>


              {/* PASSWORD */}

              <label className="kfm-login__field">
                <span>Password</span>

                <div className="kfm-password-field">

                  <input
                    type={
                      showPassword
                        ? 'text'
                        : 'password'
                    }
                    required
                    autoComplete="current-password"
                    value={password}
                    onChange={(e) =>
                      setPassword(e.target.value)
                    }
                    placeholder="••••••••"
                    disabled={loading}
                  />

                  {/* PASSWORD EYE */}

                  <button
                    type="button"
                    className="kfm-password-toggle"
                    onClick={() =>
                      setShowPassword((value) => !value)
                    }
                    disabled={loading}
                    aria-label={
                      showPassword
                        ? 'Hide password'
                        : 'Show password'
                    }
                    aria-pressed={showPassword}
                  >
                    <i
                      className={
                        showPassword
                          ? 'bi bi-eye-slash'
                          : 'bi bi-eye'
                      }
                      aria-hidden="true"
                    ></i>
                  </button>

                </div>
              </label>


              {/* FORGOT PASSWORD */}

              <div className="kfm-login__forgot">
                <Link to="/forgot-password">
                  Forgot password?
                </Link>
              </div>


              {/* ERROR */}

              {error && (
                <div
                  className="kfm-login__error"
                  role="alert"
                >
                  {error}
                </div>
              )}


              {/* SUBMIT */}

              <button
                type="submit"
                className="kfm-login__submit"
                disabled={loading}
              >
                {loading
                  ? 'Signing in…'
                  : 'Sign In'}
              </button>

            </form>
          </>
        )}


        {/* ======================================================
            TWO FACTOR
           ====================================================== */}

        {step === 'totp' && (
          <>
            <h1 className="kfm-login__title">
              Verify your identity.
            </h1>

            <p className="kfm-login__sub">
              Enter the 6-digit code from your authenticator
              app, or use one of your recovery codes.
            </p>


            <form
              onSubmit={submitTotp}
              className="kfm-login__form"
            >

              <label className="kfm-login__field">
                <span>
                  Verification code
                </span>

                <input
                  type="text"
                  required
                  autoFocus
                  autoComplete="one-time-code"
                  inputMode="numeric"
                  maxLength={20}
                  value={totpCode}
                  onChange={(e) =>
                    setTotpCode(e.target.value)
                  }
                  placeholder="123456"
                  disabled={loading}
                />
              </label>


              {error && (
                <div
                  className="kfm-login__error"
                  role="alert"
                >
                  {error}
                </div>
              )}


              <button
                type="submit"
                className="kfm-login__submit"
                disabled={
                  loading ||
                  !totpCode.trim()
                }
              >
                {loading
                  ? 'Verifying…'
                  : 'Verify & Sign In'}
              </button>

            </form>


            {/* BACK */}

            <button
              type="button"
              className="kfm-login__back"
              onClick={backToCredentials}
            >
              ← Back to sign in
            </button>
          </>
        )}


        {/* ======================================================
            PUBLIC SITE
           ====================================================== */}

        <a
          href="/"
          className="kfm-login__back"
        >
          Back to public site
        </a>

      </div>
    </div>
  );
}