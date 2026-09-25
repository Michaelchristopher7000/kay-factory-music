import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { notificationsApi, relativeTime } from '../notificationsApi';
import { test } from 'vitest';

const typeIcon = (type) => {
  if (!type) return 'bi-bell';
  if (type.includes('SuspiciousLogin')) return 'bi-exclamation-triangle';
  if (type.includes('TalentSubmission')) return 'bi-star';
  if (type.includes('ContactMessage')) return 'bi-envelope';
  if (type.includes('StaffMessage')) return 'bi-chat-dots';
  if (type.includes('Login')) return 'bi-shield-lock';
  if (type.includes('Artist')) return 'bi-person-badge';
  if (type.includes('Contract')) return 'bi-file-earmark-text';
  if (type.includes('Release')) return 'bi-vinyl';
  if (type.includes('Distribution')) return 'bi-broadcast';
  if (type.includes('RoyaltyStatement')) return 'bi-file-earmark-ruled';
  if (type.includes('RoyaltyPayment')) return 'bi-cash-coin';
  return 'bi-bell';
};

const typeTone = (type) => {
  if (!type) return '#E4B84C';
  if (type.includes('SuspiciousLogin')) return '#ef4444';
  if (type.includes('TalentSubmission')) return '#E4B84C';
  if (type.includes('ContactMessage')) return '#10B981';
  if (type.includes('StaffMessage')) return '#A78BFA';
  if (type.includes('Login')) return '#22D3EE';
  if (type.includes('Artist')) return '#F59E0B';
  if (type.includes('Contract')) return '#60A5FA';
  if (type.includes('Release')) return '#A78BFA';
  if (type.includes('Distribution')) return '#4ADE80';
  if (type.includes('RoyaltyStatement')) return '#E4B84C';
  if (type.includes('RoyaltyPayment')) return '#F472B6';
  return '#E4B84C';
};

export default function NotificationsDropdown() {
  const [open, setOpen] = useState(false);
  const [items, setItems] = useState([]);
  const [unread, setUnread] = useState(0);
  const [loading, setLoading] = useState(false);
  const [busyId, setBusyId] = useState(null);
  const wrapperRef = useRef(null);
  const navigate = useNavigate();

  // Poll unread count every 30s
  useEffect(() => {
    let alive = true;

    const tick = async () => {
      try {
        const res = await notificationsApi.unreadCount();
        if (alive) setUnread(res.data?.data?.unread_count ?? 0);
      } catch {
        // silent — badge just stays stale
      }
    };

    tick();
    const interval = setInterval(tick, 30000);
    return () => { alive = false; clearInterval(interval); };
  }, []);

  // Close on outside click
  useEffect(() => {
    const onClick = (e) => {
      if (!wrapperRef.current) return;
      if (!wrapperRef.current.contains(e.target)) setOpen(false);
    };
    document.addEventListener('mousedown', onClick);
    return () => document.removeEventListener('mousedown', onClick);
  }, []);

  // Load items when opened
  useEffect(() => {
    if (!open) return;
    let alive = true;

    (async () => {
      setLoading(true);
      try {
        const res = await notificationsApi.list({ per_page: 8 });
        if (alive) setItems(res.data?.data || []);
      } catch {
        if (alive) setItems([]);
      } finally {
        if (alive) setLoading(false);
      }
    })();

    return () => { alive = false; };
  }, [open]);

  const handleClick = async (n) => {
    setBusyId(n.id);

    try {
      if (!n.is_read) {
        await notificationsApi.markAsRead(n.id);
        setItems((prev) =>
          prev.map((x) => (x.id === n.id ? { ...x, is_read: true, read_at: new Date().toISOString() } : x))
        );
        setUnread((u) => Math.max(0, u - 1));
      }

      setOpen(false);

      if (n.action_url) {
        const target = n.action_url.startsWith('/staff')
          ? n.action_url
          : `/staff${n.action_url}`;
        navigate(target);
      }
    } catch {
      // silent
    } finally {
      setBusyId(null);
    }
  };

  const handleMarkAll = async () => {
    try {
      await notificationsApi.markAllAsRead();
      setItems((prev) =>
        prev.map((x) => ({ ...x, is_read: true, read_at: new Date().toISOString() }))
      );
      setUnread(0);
    } catch {
      // silent
    }
  };

  return (
    <div className="kfm-notif" ref={wrapperRef}>
      <button
        type="button"
        className="kfm-topbar__icon-btn"
        title="Notifications"
        onClick={() => setOpen((v) => !v)}
        aria-expanded={open}
      >
        <i className="bi bi-bell"></i>
        {unread > 0 && (
          <span className="kfm-notif__badge">
            {unread > 99 ? '99+' : unread}
          </span>
        )}
      </button>

      {open && (
        <div className="kfm-notif__panel">
          <div className="kfm-notif__header">
            <div>
              <div className="kfm-notif__title">Notifications</div>
              <div className="kfm-notif__subtitle">
                {unread > 0 ? `${unread} unread` : 'All caught up'}
              </div>
            </div>
            {unread > 0 && (
              <button
                type="button"
                className="kfm-notif__mark-all"
                onClick={handleMarkAll}
              >
                Mark all read
              </button>
            )}
          </div>

          <div className="kfm-notif__list">
            {loading && (
              <div className="kfm-notif__state">
                <div className="spinner-border spinner-border-sm me-2"></div>
                Loading…
              </div>
            )}

            {!loading && items.length === 0 && (
              <div className="kfm-notif__state">
                <i className="bi bi-bell-slash me-2"></i>
                No notifications yet.
              </div>
            )}

            {!loading && items.map((n) => {
              const tone = typeTone(n.type);
              const isBusy = busyId === n.id;

              return (
                <button
                  key={n.id}
                  type="button"
                  className={`kfm-notif__item ${n.is_read ? 'is-read' : 'is-unread'}`}
                  onClick={() => handleClick(n)}
                  disabled={isBusy}
                >
                  <div
                    className="kfm-notif__icon"
                    style={{ color: tone, background: `${tone}1A` }}
                  >
                    <i className={`bi ${typeIcon(n.type)}`}></i>
                  </div>
                  <div className="kfm-notif__body">
                    <div className="kfm-notif__item-title">
                      {n.title || 'Notification'}
                    </div>
                    <div className="kfm-notif__item-message">{n.message}</div>
                    <div className="kfm-notif__item-time">
                      {relativeTime(n.created_at)}
                    </div>
                  </div>
                  {!n.is_read && <span className="kfm-notif__dot"></span>}
                </button>
              );
            })}
          </div>

          <div className="kfm-notif__footer">
            <Link
              to="/notifications"
              className="kfm-notif__view-all"
              onClick={() => setOpen(false)}
            >
              View all notifications →
            </Link>
          </div>
        </div>
      )}
    </div>
  );
}