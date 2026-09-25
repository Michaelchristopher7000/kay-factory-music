/**
 * Pure idle-timer factory — no React, no DOM.
 *
 * @param {object} opts
 * @param {number} opts.warningMs   - ms of inactivity before onWarn fires
 * @param {number} opts.logoutMs    - ms of inactivity before onLogout fires
 * @param {Function} [opts.onWarn]
 * @param {Function} [opts.onLogout]
 * @param {number} [opts.throttleMs=500] - minimum gap between successful resets
 *
 * @returns {{
 *   reset: (options?: {force?: boolean}) => boolean,
 *   destroy: () => void,
 *   isWarned: () => boolean,
 *   isDestroyed: () => boolean,
 * }}
 */
export function createIdleTimer({
  warningMs,
  logoutMs,
  onWarn,
  onLogout,
  throttleMs = 500,
}) {
  if (warningMs >= logoutMs) {
    throw new Error('[idleTimeout] warningMs must be less than logoutMs');
  }

  let warningHandle = null;
  let logoutHandle = null;
  let lastResetAt = 0;
  let warned = false;
  let destroyed = false;

  const clearTimers = () => {
    if (warningHandle !== null) {
      clearTimeout(warningHandle);
      warningHandle = null;
    }
    if (logoutHandle !== null) {
      clearTimeout(logoutHandle);
      logoutHandle = null;
    }
  };

  const safeCall = (fn) => {
    if (typeof fn !== 'function') return;
    try {
      fn();
    } catch {
      // Callbacks must never crash the timer.
    }
  };

  const schedule = () => {
    clearTimers();

    warningHandle = setTimeout(() => {
      warningHandle = null;
      warned = true;
      safeCall(onWarn);
    }, warningMs);

    logoutHandle = setTimeout(() => {
      logoutHandle = null;
      safeCall(onLogout);
    }, logoutMs);
  };

  /**
   * Reset the idle timer.
   * Ordinary activity is throttled. When the warning is showing, only a
   * forced reset (the "Stay signed in" button) will clear the warning.
   */
  const reset = (options = {}) => {
    if (destroyed) return false;

    const force = options.force === true;

    if (warned && !force) return false;

    const now = Date.now();
    if (!force && now - lastResetAt < throttleMs) return false;

    lastResetAt = now;
    warned = false;
    schedule();
    return true;
  };

  const destroy = () => {
    destroyed = true;
    clearTimers();
  };

  const isWarned = () => warned;
  const isDestroyed = () => destroyed;

  schedule();

  return { reset, destroy, isWarned, isDestroyed };
}