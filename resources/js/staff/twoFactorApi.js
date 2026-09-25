import api from './api';

export const twoFactorApi = {
  /** Start (or restart) an unconfirmed 2FA setup. */
  setup: () => api.post('/me/2fa/setup'),

  /** Confirm the first TOTP code — activates 2FA and returns recovery codes. */
  verify: (code) => api.post('/me/2fa/verify', { code }),

  /** Disable 2FA. Requires the current password. */
  disable: (password) => api.post('/me/2fa/disable', { password }),

  /** Regenerate recovery codes. Requires the current password. */
  regenerateRecoveryCodes: (password) =>
    api.post('/me/2fa/recovery-codes', { password }),

  /** Second login step. Public endpoint. */
  loginWith2fa: (challenge_token, code) =>
    api.post('/login/2fa', { challenge_token, code }),
};

/**
 * Challenge tokens live only in sessionStorage — never in localStorage.
 * They are single-use and short-lived.
 */
const CHALLENGE_KEY = 'kfm_2fa_challenge';

export const challengeStore = {
  set(token) {
    try {
      sessionStorage.setItem(CHALLENGE_KEY, token);
    } catch {
      // Private-mode fallbacks: keep working without persistence.
    }
  },
  get() {
    try {
      return sessionStorage.getItem(CHALLENGE_KEY);
    } catch {
      return null;
    }
  },
  clear() {
    try {
      sessionStorage.removeItem(CHALLENGE_KEY);
    } catch {
      // silent
    }
  },
};