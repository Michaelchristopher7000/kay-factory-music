import { useEffect, useState, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { notificationsApi, relativeTime } from '../../notificationsApi';

const typeIcon = (type) => {
  if (!type) return 'bi-bell';
  if (type.includes('SuspiciousLogin')) return 'bi-exclamation-triangle';
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
  if (type.includes('Login')) return '#22D3EE';
  if (type.includes('Artist')) return '#F59E0B';
  if (type.includes('Contract')) return '#60A5FA';
  if (type.includes('Release')) return '#A78BFA';
  if (type.includes('Distribution')) return '#4ADE80';
  if (type.includes('RoyaltyStatement')) return '#E4B84C';
  if (type.includes('RoyaltyPayment')) return '#F472B6';
  return '#E4B84C';
};

export default function NotificationsPage() {
  const navigate = useNavigate();

  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [filter, setFilter] = useState('all'); // all | unread
  const [page, setPage] = useState(1);

  const fetchList = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const params = { per_page: 25, page };
      if (filter === 'unread') params.unread = true;

      const res = await notificationsApi.list(params);
      setItems(res.data?.data || []);
      setMeta(res.data?.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Unable to load notifications. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [filter, page]);

  useEffect(() => { fetchList(); }, [fetchList]);
  useEffect(() => { setPage(1); }, [filter]);

  const markRead = async (n) => {
    if (n.is_read) return;
    try {
      await notificationsApi.markAsRead(n.id);
      setItems((prev) =>
        prev.map((x) => (x.id === n.id ? { ...x, is_read: true, read_at: new Date().toISOString() } : x))
      );
    } catch {
      // silent
    }
  };

  const handleOpen = async (n) => {
    await markRead(n);
    if (n.action_url) {
      const target = n.action_url.startsWith('/staff')
        ? n.action_url
        : `/staff${n.action_url}`;
      navigate(target);
    }
  };

  const removeOne = async (n) => {
    if (!window.confirm('Delete this notification?')) return;
    try {
      await notificationsApi.destroy(n.id);
      setItems((prev) => prev.filter((x) => x.id !== n.id));
    } catch {
      alert('Could not delete notification.');
    }
  };

  const markAll = async () => {
    try {
      await notificationsApi.markAllAsRead();
      fetchList();
    } catch {
      alert('Could not mark all as read.');
    }
  };

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h4 className="mb-1">Notifications</h4>
          <p className="text-muted mb-0 small">
            {meta.total || 0} total
          </p>
        </div>

        <div className="d-flex gap-2">
          <button
            type="button"
            className="btn btn-outline-secondary"
            onClick={markAll}
          >
            <i className="bi bi-check2-all me-2"></i>
            Mark All Read
          </button>
        </div>
      </div>

      <div className="kfm-filters mb-3">
        <button
          type="button"
          className={`btn btn-sm ${filter === 'all' ? 'btn-dark' : 'btn-outline-secondary'}`}
          onClick={() => setFilter('all')}
        >
          All
        </button>
        <button
          type="button"
          className={`btn btn-sm ${filter === 'unread' ? 'btn-dark' : 'btn-outline-secondary'}`}
          onClick={() => setFilter('unread')}
        >
          Unread
        </button>
      </div>

      {loading && (
        <div className="text-center py-5 text-muted">
          <div className="spinner-border spinner-border-sm me-2"></div>
          Loading…
        </div>
      )}

      {!loading && error && <div className="alert alert-danger">{error}</div>}

      {!loading && !error && items.length === 0 && (
        <div className="card border-0 shadow-sm">
          <div className="card-body text-center text-muted py-5">
            <i className="bi bi-bell-slash fs-1 d-block mb-3 opacity-50"></i>
            {filter === 'unread' ? 'No unread notifications.' : 'No notifications yet.'}
          </div>
        </div>
      )}

      {!loading && !error && items.length > 0 && (
        <div className="card border-0 shadow-sm">
          <ul className="list-unstyled mb-0">
            {items.map((n) => {
              const tone = typeTone(n.type);

              return (
                <li
                  key={n.id}
                  className={`kfm-notif-row ${n.is_read ? 'is-read' : 'is-unread'}`}
                >
                  <button
                    type="button"
                    className="kfm-notif-row__main"
                    onClick={() => handleOpen(n)}
                  >
                    <div
                      className="kfm-notif-row__icon"
                      style={{ color: tone, background: `${tone}1A` }}
                    >
                      <i className={`bi ${typeIcon(n.type)}`}></i>
                    </div>
                    <div className="kfm-notif-row__body">
                      <div className="kfm-notif-row__title">
                        {n.title || 'Notification'}
                        {!n.is_read && <span className="kfm-notif-row__dot"></span>}
                      </div>
                      <div className="kfm-notif-row__message">{n.message}</div>
                      <div className="kfm-notif-row__meta">
                        {n.entity_code && <span>{n.entity_code}</span>}
                        {n.entity_code && <span className="mx-2">·</span>}
                        <span>{relativeTime(n.created_at)}</span>
                      </div>
                    </div>
                  </button>

                  <div className="kfm-notif-row__actions">
                    <button
                      type="button"
                      className="btn btn-sm btn-outline-danger"
                      title="Delete"
                      onClick={() => removeOne(n)}
                    >
                      <i className="bi bi-trash"></i>
                    </button>
                  </div>
                </li>
              );
            })}
          </ul>

          {meta.last_page > 1 && (
            <div className="card-footer bg-white d-flex justify-content-between align-items-center">
              <div className="text-muted small">
                Page {meta.current_page} of {meta.last_page}
              </div>
              <div>
                <button
                  type="button"
                  className="btn btn-sm btn-outline-secondary me-1"
                  disabled={meta.current_page <= 1}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                >
                  <i className="bi bi-chevron-left"></i> Prev
                </button>
                <button
                  type="button"
                  className="btn btn-sm btn-outline-secondary"
                  disabled={meta.current_page >= meta.last_page}
                  onClick={() => setPage((p) => p + 1)}
                >
                  Next <i className="bi bi-chevron-right"></i>
                </button>
              </div>
            </div>
          )}
        </div>
      )}
    </>
  );
}