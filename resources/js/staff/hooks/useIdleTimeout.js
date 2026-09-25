import { useEffect, useRef, useState } from 'react';
import { createIdleTimer } from '../idleTimeout';

const DEFAULT_WARNING_MIN = 25;
const DEFAULT_LOGOUT_MIN = 30;
const THROTTLE_MS = 500;

const ACTIVITY_EVENTS = [
  'mousemove',
  'mousedown',
  'keydown',
  'scroll',
  'touchstart',
  'pointerdown',
];

function readMinutes(key, fallback) {
  const raw = import.meta?.env?.[key];
  const parsed = Number.parseInt(raw ?? '', 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

/**
 * Wires the pure idle timer to the browser environment.
 *
 * @param {object} opts
 * @param {Function} opts.onLogout — called when the timer expires
 *
 * @returns {{
 *   warning: boolean,
 *   staySignedIn: () => void,
 *   logOutNow: () => void,
 * }}
 */
export function useIdleTimeout({ onLogout }) {
  const [warning, setWarning] = useState(false);
  const timerRef = useRef(null);
  const onLogoutRef = useRef(onLogout);

  useEffect(() => {
    onLogoutRef.current = onLogout;
  }, [onLogout]);

  useEffect(() => {
    const warningMin = readMinutes(
      'VITE_KFM_SESSION_IDLE_WARNING_MINUTES',
      DEFAULT_WARNING_MIN
    );
    const logoutMin = readMinutes(
      'VITE_KFM_SESSION_IDLE_LOGOUT_MINUTES',
      DEFAULT_LOGOUT_MIN
    );

    const timer = createIdleTimer({
      warningMs: warningMin * 60_000,
      logoutMs: logoutMin * 60_000,
      throttleMs: THROTTLE_MS,
      onWarn: () => setWarning(true),
      onLogout: () => {
        setWarning(false);
        try {
          onLogoutRef.current?.();
        } catch {
          // never crash the tree
        }
      },
    });

    timerRef.current = timer;

    const handleActivity = () => {
      timer.reset();
    };

    ACTIVITY_EVENTS.forEach((evt) =>
      window.addEventListener(evt, handleActivity, { passive: true })
    );

    return () => {
      ACTIVITY_EVENTS.forEach((evt) =>
        window.removeEventListener(evt, handleActivity)
      );
      timer.destroy();
      timerRef.current = null;
    };
  }, []);

  const staySignedIn = () => {
    setWarning(false);
    timerRef.current?.reset({ force: true });
  };

  const logOutNow = () => {
    setWarning(false);
    try {
      onLogoutRef.current?.();
    } catch {
      // silent
    }
  };

  return { warning, staySignedIn, logOutNow };
}