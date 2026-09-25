import { useEffect, useMemo, useState } from 'react';
import { useAuth } from '../../auth';
import { twoFactorApi } from '../../twoFactorApi';

/* ============================================================
   Simple modal — no Bootstrap JS required
   ============================================================ */
function Modal({ open, onClose, title, children, footer, size = 'md' }) {
  if (!open) return null;

  return (
    <div className="kfm-modal-overlay" role="dialog" aria-modal="true">
      <div className={`kfm-modal kfm-modal--${size}`}>
        <div className="kfm-modal__header">
          <h5 className="mb-0">{title}</h5>
          <button
            type="button"
            className="kfm-modal__close"
            onClick={onClose}
            aria-label="Close"
          >
            <i className="bi bi-x-lg"></i>
          </button>
        </div>
        <div className="kfm-modal__body">{children}</div>
        {footer && <div className="kfm-modal__footer">{footer}</div>}
      </div>
    </div>
  );
}

/* ============================================================
   Recovery codes panel — reuse for both initial and regeneration
   ============================================================ */
function RecoveryCodesPanel({ codes, onAcknowledge }) {
  const [copied, setCopied] = useState(false);

  const asText = useMemo(() => {
    const lines = [
      'Kay Factory Music — Two-Factor Recovery Codes',
      `Generated: ${new Date().toLocaleString()}`,
      '',
      'Keep these somewhere safe. Each code can be used only once.',
      '',
      ...codes,
      '',
      'If you lose access to your authenticator AND your recovery codes,',
      'you will need a Super Admin to reset your 2FA manually.',
    ];
    return lines.join('\n');
  }, [codes]);

  const copyAll = async () => {
    try {
      await navigator.clipboard.writeText(codes.join('\n'));
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      // Clipboard blocked — fall through
    }
  };

  const download = () => {
    const blob = new Blob([asText], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `kfm-2fa-recovery-codes-${Date.now()}.txt`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  return (
    <>
      <div className="alert alert-warning mb-3">
        <i className="bi bi-exclamation-triangle me-2"></i>
        <strong>Save these now.</strong> They will not be shown again.
      </div>

      <div className="kfm-2fa-codes-grid">
        {codes.map((code) => (
          <div key={code} className="kfm-2fa-code">{code}</div>
        ))}
      </div>

      <div className="d-flex gap-2 mt-3">
        <button type="button" className="btn btn-outline-secondary btn-sm" onClick={copyAll}>
          <i className={`bi ${copied ? 'bi-check2' : 'bi-clipboard'} me-1`}></i>
          {copied ? 'Copied' : 'Copy'}
        </button>
        <button type="button" className="btn btn-outline-secondary btn-sm" onClick={download}>
          <i className="bi bi-download me-1"></i>
          Download .txt
        </button>
      </div>

      <hr className="my-3" />

      <button
        type="button"
        className="btn btn-dark w-100"
        onClick={onAcknowledge}
      >
        <i className="bi bi-check2-circle me-2"></i>
        I&apos;ve saved my recovery codes
      </button>
    </>
  );
}

/* ============================================================
   Main card
   ============================================================ */
export default function TwoFactorCard() {
  const { user, setUser } = useAuth();

  const [enabled, setEnabled] = useState(!!user?.two_factor_enabled);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Setup flow state
  const [setupData, setSetupData] = useState(null); // { secret, qr_code, otpauth_url }
  const [setupCode, setSetupCode] = useState('');
  const [setupDialogOpen, setSetupDialogOpen] = useState(false);

  // Recovery codes modal
  const [recoveryCodes, setRecoveryCodes] = useState(null);
  const [codesDialogOpen, setCodesDialogOpen] = useState(false);

  // Disable / regenerate dialogs
  const [disableDialogOpen, setDisableDialogOpen] = useState(false);
  const [disablePassword, setDisablePassword] = useState('');
  const [regenerateDialogOpen, setRegenerateDialogOpen] = useState(false);
  const [regeneratePassword, setRegeneratePassword] = useState('');

  useEffect(() => {
    setEnabled(!!user?.two_factor_enabled);
  }, [user?.two_factor_enabled]);

  const flash = (msg) => {
    setSuccess(msg);
    setTimeout(() => setSuccess(''), 4000);
  };

  const clearMessages = () => {
    setError('');
    setSuccess('');
  };

  const refreshUser = (patch) => {
    if (setUser && user) {
      setUser({ ...user, ...patch });
    }
  };

  /* ============================================================
     Setup flow
     ============================================================ */
  const startSetup = async () => {
    clearMessages();
    setBusy(true);
    try {
      const res = await twoFactorApi.setup();
      setSetupData(res.data?.data || null);
      setSetupCode('');
      setSetupDialogOpen(true);
    } catch (err) {
      const msg = err.response?.data?.message;
      setError(msg || 'Could not start two-factor setup. Please try again.');
    } finally {
      setBusy(false);
    }
  };

  const cancelSetup = () => {
    setSetupDialogOpen(false);
    setSetupData(null);
    setSetupCode('');
  };

  const submitSetupCode = async (e) => {
    e.preventDefault();
    clearMessages();
    setBusy(true);
    try {
      const res = await twoFactorApi.verify(setupCode.trim());
      const codes = res.data?.recovery_codes || [];
      setSetupDialogOpen(false);
      setSetupData(null);
      setSetupCode('');
      setRecoveryCodes(codes);
      setCodesDialogOpen(true);
    } catch (err) {
      const msg =
        err.response?.data?.errors?.code?.[0] ||
        err.response?.data?.message ||
        'The verification code is invalid or has expired.';
      setError(msg);
    } finally {
      setBusy(false);
    }
  };

  const acknowledgeCodes = () => {
    setCodesDialogOpen(false);
    setRecoveryCodes(null);
    setEnabled(true);
    refreshUser({ two_factor_enabled: true });
    flash('Two-factor authentication is now enabled.');
  };

  /* ============================================================
     Disable flow
     ============================================================ */
  const openDisable = () => {
    clearMessages();
    setDisablePassword('');
    setDisableDialogOpen(true);
  };

  const submitDisable = async (e) => {
    e.preventDefault();
    clearMessages();
    setBusy(true);
    try {
      const res = await twoFactorApi.disable(disablePassword);
      setDisableDialogOpen(false);
      setDisablePassword('');
      setEnabled(false);
      refreshUser({ two_factor_enabled: false });

      const revoked = res.data?.sessions_revoked ?? 0;
      flash(
        revoked > 0
          ? `Two-factor authentication disabled. ${revoked} other session${revoked > 1 ? 's were' : ' was'} signed out.`
          : 'Two-factor authentication disabled.'
      );
    } catch (err) {
      const msg =
        err.response?.data?.errors?.password?.[0] ||
        err.response?.data?.message ||
        'Could not disable two-factor authentication.';
      setError(msg);
    } finally {
      setBusy(false);
    }
  };

  /* ============================================================
     Regenerate flow
     ============================================================ */
  const openRegenerate = () => {
    clearMessages();
    setRegeneratePassword('');
    setRegenerateDialogOpen(true);
  };

  const submitRegenerate = async (e) => {
    e.preventDefault();
    clearMessages();
    setBusy(true);
    try {
      const res = await twoFactorApi.regenerateRecoveryCodes(regeneratePassword);
      const codes = res.data?.recovery_codes || [];
      setRegenerateDialogOpen(false);
      setRegeneratePassword('');
      setRecoveryCodes(codes);
      setCodesDialogOpen(true);
    } catch (err) {
      const msg =
        err.response?.data?.errors?.password?.[0] ||
        err.response?.data?.message ||
        'Could not regenerate recovery codes.';
      setError(msg);
    } finally {
      setBusy(false);
    }
  };

  /* ============================================================
     Render
     ============================================================ */
  return (
    <>
      <div className="kfm-settings-card">
        <div className="kfm-settings-card__header">
          <div>
            <h5 className="kfm-settings-card__title">
              Two-Factor Authentication
              {enabled && (
                <span className="kfm-2fa-status kfm-2fa-status--on">
                  <i className="bi bi-shield-check me-1"></i> Enabled
                </span>
              )}
            </h5>
            <p className="kfm-settings-card__sub">
              Add an extra layer of protection. Requires an authenticator app
              such as Google Authenticator, Authy, or 1Password.
            </p>
          </div>
        </div>

        <div className="kfm-settings-card__body">
          {error && <div className="alert alert-danger mb-3">{error}</div>}
          {success && <div className="alert alert-success mb-3">{success}</div>}

          {!enabled && (
            <>
              <p className="text-muted mb-3">
                Two-factor authentication is currently <strong>disabled</strong> on your account.
              </p>
              <button
                type="button"
                className="btn btn-dark"
                onClick={startSetup}
                disabled={busy}
              >
                {busy ? (
                  <><span className="spinner-border spinner-border-sm me-2"></span>Starting…</>
                ) : (
                  <><i className="bi bi-shield-lock me-2"></i>Enable two-factor authentication</>
                )}
              </button>
            </>
          )}

          {enabled && (
            <>
              <div className="kfm-2fa-actions">
                <button
                  type="button"
                  className="btn btn-outline-secondary btn-sm"
                  onClick={openRegenerate}
                  disabled={busy}
                >
                  <i className="bi bi-arrow-clockwise me-1"></i>
                  Regenerate recovery codes
                </button>
                <button
                  type="button"
                  className="btn btn-outline-danger btn-sm"
                  onClick={openDisable}
                  disabled={busy}
                >
                  <i className="bi bi-shield-slash me-1"></i>
                  Disable 2FA
                </button>
              </div>
              <p className="text-muted small mt-3 mb-0">
                Your account is protected by a one-time code from your authenticator app.
              </p>
            </>
          )}
        </div>
      </div>

      {/* ---------- Setup dialog ---------- */}
      <Modal
        open={setupDialogOpen}
        onClose={cancelSetup}
        title="Set up two-factor authentication"
        size="lg"
      >
        {setupData && (
          <>
            <p className="mb-3">
              <strong>Step 1.</strong> Scan this QR code with your authenticator app.
            </p>

            <div className="kfm-2fa-qr">
              <img src={setupData.qr_code} alt="2FA QR code" />
            </div>

            <p className="mt-3 mb-1 text-muted small">
              Can&apos;t scan? Enter this secret manually:
            </p>
            <div className="kfm-2fa-secret">{setupData.secret}</div>

            <hr className="my-3" />

            <p className="mb-2">
              <strong>Step 2.</strong> Enter the 6-digit code from your app to finish setup.
            </p>

            <form onSubmit={submitSetupCode}>
              <div className="input-group mb-3">
                <input
                  type="text"
                  inputMode="numeric"
                  autoComplete="one-time-code"
                  autoFocus
                  pattern="[0-9A-Za-z\- ]*"
                  maxLength={20}
                  className="form-control kfm-2fa-code-input"
                  placeholder="123456"
                  value={setupCode}
                  onChange={(e) => setSetupCode(e.target.value)}
                  disabled={busy}
                  required
                />
                <button
                  type="submit"
                  className="btn btn-dark"
                  disabled={busy || !setupCode.trim()}
                >
                  {busy ? 'Verifying…' : 'Verify & Enable'}
                </button>
              </div>
              <p className="text-muted small mb-0">
                If you lose access to your authenticator, you&apos;ll be able to sign in with
                one of the recovery codes we generate next.
              </p>
            </form>
          </>
        )}
      </Modal>

      {/* ---------- Recovery codes dialog ---------- */}
      <Modal
        open={codesDialogOpen}
        onClose={acknowledgeCodes}
        title="Your recovery codes"
        size="lg"
      >
        {recoveryCodes && (
          <RecoveryCodesPanel codes={recoveryCodes} onAcknowledge={acknowledgeCodes} />
        )}
      </Modal>

      {/* ---------- Disable dialog ---------- */}
      <Modal
        open={disableDialogOpen}
        onClose={() => setDisableDialogOpen(false)}
        title="Disable two-factor authentication"
      >
        <p className="mb-3">
          Disabling 2FA removes the extra layer of protection from your account.
          Other sessions will be signed out. Enter your current password to confirm.
        </p>

        <form onSubmit={submitDisable}>
          <label className="form-label">Current password</label>
          <input
            type="password"
            autoComplete="current-password"
            className="form-control mb-3"
            value={disablePassword}
            onChange={(e) => setDisablePassword(e.target.value)}
            required
            disabled={busy}
          />

          <div className="d-flex gap-2 justify-content-end">
            <button
              type="button"
              className="btn btn-outline-secondary"
              onClick={() => setDisableDialogOpen(false)}
              disabled={busy}
            >
              Cancel
            </button>
            <button type="submit" className="btn btn-danger" disabled={busy}>
              {busy ? 'Disabling…' : 'Disable 2FA'}
            </button>
          </div>
        </form>
      </Modal>

      {/* ---------- Regenerate dialog ---------- */}
      <Modal
        open={regenerateDialogOpen}
        onClose={() => setRegenerateDialogOpen(false)}
        title="Regenerate recovery codes"
      >
        <p className="mb-3">
          This will invalidate your existing recovery codes. You will receive a new set.
          Enter your current password to confirm.
        </p>

        <form onSubmit={submitRegenerate}>
          <label className="form-label">Current password</label>
          <input
            type="password"
            autoComplete="current-password"
            className="form-control mb-3"
            value={regeneratePassword}
            onChange={(e) => setRegeneratePassword(e.target.value)}
            required
            disabled={busy}
          />

          <div className="d-flex gap-2 justify-content-end">
            <button
              type="button"
              className="btn btn-outline-secondary"
              onClick={() => setRegenerateDialogOpen(false)}
              disabled={busy}
            >
              Cancel
            </button>
            <button type="submit" className="btn btn-dark" disabled={busy}>
              {busy ? 'Regenerating…' : 'Regenerate codes'}
            </button>
          </div>
        </form>
      </Modal>
    </>
  );
}