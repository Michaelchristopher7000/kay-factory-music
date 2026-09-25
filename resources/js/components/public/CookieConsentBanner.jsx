import { useEffect, useState } from 'react';

const STORAGE_KEY = 'kfm_cookie_consent';

export default function CookieConsentBanner() {
  const [choice, setChoice] = useState(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    let stored = null;
    try {
      stored = localStorage.getItem(STORAGE_KEY);
    } catch {
      // localStorage may be blocked (privacy mode, etc.) — show banner anyway
    }

    if (stored !== 'accepted' && stored !== 'declined') {
      // Slight delay so it doesn't fight the page's initial paint
      const t = setTimeout(() => setVisible(true), 600);
      return () => clearTimeout(t);
    }

    setChoice(stored);
  }, []);

  const decide = (value) => {
    try {
      localStorage.setItem(STORAGE_KEY, value);
    } catch {
      // ignore — banner still hides for this session
    }
    setChoice(value);
    setVisible(false);
  };

  if (choice !== null) return null;
  if (!visible) return null;

  return (
    <div className="kfm-cookie-banner" role="region" aria-label="Cookie consent">
      <div className="kfm-container kfm-cookie-banner__inner">
        <div className="kfm-cookie-banner__content">
          <div className="kfm-cookie-banner__title">We use cookies</div>
          <p className="kfm-cookie-banner__text">
            Kay Factory Music uses necessary cookies to keep the website working
            properly. We currently do not use analytics or optional tracking cookies.
          </p>
        </div>

        <div className="kfm-cookie-banner__actions">
          <a
            href="/cookie-policy"
            className="kfm-btn kfm-btn--ghost kfm-cookie-banner__btn"
          >
            Cookie Policy
          </a>
          <button
            type="button"
            className="kfm-btn kfm-btn--ghost kfm-cookie-banner__btn"
            onClick={() => decide('declined')}
          >
            Decline
          </button>
          <button
            type="button"
            className="kfm-btn kfm-btn--primary kfm-cookie-banner__btn"
            onClick={() => decide('accepted')}
          >
            Accept
          </button>
        </div>
      </div>
    </div>
  );
}