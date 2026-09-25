import { useEffect, useState, useCallback } from 'react';
import { securityApi } from '../../securityApi';
import { relativeTime } from '../../notificationsApi';

const EVENT_META = {
  login_success:                         { icon: 'bi-box-arrow-in-right', tone: '#4ade80', title: 'Successful sign-in' },
  login_failed:                          { icon: 'bi-shield-exclamation', tone: '#ef4444', title: 'Failed sign-in attempt' },
  logout:                                { icon: 'bi-box-arrow-right',    tone: '#94a3b8', title: 'Signed out' },
  password_changed:                      { icon: 'bi-key',                tone: '#E4B84C', title: 'Password changed' },
  password_reset:                        { icon: 'bi-key-fill',           tone: '#E4B84C', title: 'Password reset' },
  password_reset_requested:              { icon: 'bi-envelope-paper',     tone: '#94a3b8', title: 'Password reset requested' },
  session_revoked:                       { icon: 'bi-shield-x',           tone: '#22D3EE', title: 'Session revoked' },
  sessions_revoked:                      { icon: 'bi-shield-x',           tone: '#22D3EE', title: 'Sessions revoked' },
  profile_updated:                       { icon: 'bi-person-gear',        tone: '#A78BFA', title: 'Profile updated' },
  avatar_updated:                        { icon: 'bi-person-badge',       tone: '#A78BFA', title: 'Avatar updated' },
  avatar_removed:                        { icon: 'bi-person-dash',        tone: '#A78BFA', title: 'Avatar removed' },
  two_factor_setup_started:              { icon: 'bi-shield-lock',        tone: '#E4B84C', title: '2FA setup started' },
  two_factor_enabled:                    { icon: 'bi-shield-check',       tone: '#4ade80', title: '2FA enabled' },
  two_factor_disabled:                   { icon: 'bi-shield-slash',       tone: '#ef4444', title: '2FA disabled' },
  two_factor_recovery_codes_regenerated: { icon: 'bi-arrow-clockwise',    tone: '#E4B84C', title: 'Recovery codes regenerated' },
  recovery_code_used:                    { icon: 'bi-key-fill',           tone: '#E4B84C', title: 'Recovery code used' },
  two_factor_challenge_failed:           { icon: 'bi-shield-exclamation', tone: '#ef4444', title: '2FA challenge failed' },
  suspicious_login_detected:             { icon: 'bi-exclamation-triangle', tone: '#ef4444', title: 'Suspicious login detected' },
  account_temporarily_locked:            { icon: 'bi-lock',               tone: '#ef4444', title: 'Account temporarily locked' },
};

function getMeta(action) {
  return (
    EVENT_META[action] || {
      icon: 'bi-shield',
      tone: '#94a3b8',
      title: String(action || '').replace(/_/g, ' '),
    }
  );
}

function describe(entry) {
  const ctx = entry.context || {};
  const parts = [];

  if (ctx.browser && ctx.platform) {
    parts.push(`${ctx.browser} on ${ctx.platform}`);
  } else if (ctx.browser) {
    parts.push(ctx.browser);
  }

  if (ctx.location) parts.push(ctx.location);

  if (ctx.revoked_count) {
    if (ctx.scope === 'others') parts.push(`${ctx.revoked_count} other session(s) signed out`);
    else if (ctx.scope === 'all') parts.push(`${ctx.revoked_count} session(s) signed out`);
  }

  if (ctx.reason === 'password_changed') parts.push('Triggered by password change');
  if (ctx.reason === 'password_reset')   parts.push('Triggered by password reset');

  if (ctx.new_device === true) parts.push('New device');

  return parts.join(' · ');
}

export default function SecurityActivityCard() {
  const [items, setItems] = useState([]);
  const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [page, setPage] = useState(1);

  const fetchActivity = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const res = await securityApi.activity({ page, per_page: 25 });
      setItems(res.data?.data || []);
      setPagination(res.data?.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Unable to load security activity. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => { fetchActivity(); }, [fetchActivity]);

  return (
    <div className="kfm-settings-card">
      <div className="kfm-settings-card__header">
        <div>
          <h5 className="kfm-settings-card__title">Recent Security Activity</h5>
          <p className="kfm-settings-card__sub">
            Sign-ins, password changes, and session events on your account.
          </p>
        </div>
      </div>

      <div className="kfm-settings-card__body">
        {loading && (
          <div className="text-center py-4 text-muted">
            <div className="spinner-border spinner-border-sm me-2"></div>
            Loading…
          </div>
        )}

        {!loading && error && <div className="alert alert-danger mb-0">{error}</div>}

        {!loading && !error && items.length === 0 && (
          <div className="text-center text-muted py-4">
            <i className="bi bi-shield fs-2 d-block mb-2 opacity-50"></i>
            No recent security activity.
          </div>
        )}

        {!loading && !error && items.length > 0 && (
          <>
            <ul className="list-unstyled mb-0">
              {items.map((entry) => {
                const meta = getMeta(entry.action);
                const details = describe(entry);

                return (
                  <li key={entry.id} className="kfm-activity-row">
                    <div
                      className="kfm-activity-row__icon"
                      style={{ color: meta.tone, background: `${meta.tone}1A` }}
                    >
                      <i className={`bi ${meta.icon}`}></i>
                    </div>

                    <div className="kfm-activity-row__body">
                      <div className="kfm-activity-row__title">{meta.title}</div>
                      {details && <div className="kfm-activity-row__meta">{details}</div>}
                      <div className="kfm-activity-row__time">
                        <i className="bi bi-clock me-1"></i>
                        {relativeTime(entry.created_at)}
                        {entry.ip_address && (
                          <>
                            <span className="mx-2">·</span>
                            <i className="bi bi-hdd-network me-1"></i>
                            {entry.ip_address}
                          </>
                        )}
                      </div>
                    </div>
                  </li>
                );
              })}
            </ul>

            {pagination.last_page > 1 && (
              <div className="d-flex justify-content-between align-items-center mt-3">
                <div className="text-muted small">
                  Page {pagination.current_page} of {pagination.last_page}
                </div>
                <div>
                  <button
                    type="button"
                    className="btn btn-sm btn-outline-secondary me-1"
                    disabled={pagination.current_page <= 1}
                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                  >
                    <i className="bi bi-chevron-left"></i> Prev
                  </button>
                  <button
                    type="button"
                    className="btn btn-sm btn-outline-secondary"
                    disabled={pagination.current_page >= pagination.last_page}
                    onClick={() => setPage((p) => p + 1)}
                  >
                    Next <i className="bi bi-chevron-right"></i>
                  </button>
                </div>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}