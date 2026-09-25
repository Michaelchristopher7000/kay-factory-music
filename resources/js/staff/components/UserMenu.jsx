import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../api';
import { useAuth, clearSession } from '../auth';

export default function UserMenu() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);
  const wrapperRef = useRef(null);

  useEffect(() => {
    const onClick = (e) => {
      if (!wrapperRef.current) return;
      if (!wrapperRef.current.contains(e.target)) setOpen(false);
    };
    document.addEventListener('mousedown', onClick);
    return () => document.removeEventListener('mousedown', onClick);
  }, []);

  const logout = async () => {
    try {
      await api.post('/logout');
    } catch {
      // ignore
    }
    clearSession();
    navigate('/login', { replace: true });
  };

  const initial = (user?.name || 'S').charAt(0).toUpperCase();
  const avatarUrl = user?.avatar_url;

  return (
    <div className="kfm-usermenu" ref={wrapperRef}>
      <button
        type="button"
        className="kfm-usermenu__trigger"
        onClick={() => setOpen((v) => !v)}
        aria-expanded={open}
      >
        {avatarUrl ? (
          <img src={avatarUrl} alt={user?.name || ''} className="kfm-usermenu__avatar" />
        ) : (
          <span className="kfm-usermenu__avatar kfm-usermenu__avatar--initial">
            {initial}
          </span>
        )}
        <span className="kfm-usermenu__meta">
          <span className="kfm-usermenu__name">{user?.name || 'Staff'}</span>
          <span className="kfm-usermenu__role">{user?.role?.name || '—'}</span>
        </span>
        <i className={`bi bi-chevron-${open ? 'up' : 'down'} kfm-usermenu__chev`}></i>
      </button>

      {open && (
        <div className="kfm-usermenu__panel">
          <div className="kfm-usermenu__head">
            {avatarUrl ? (
              <img src={avatarUrl} alt={user?.name || ''} className="kfm-usermenu__head-avatar" />
            ) : (
              <span className="kfm-usermenu__head-avatar kfm-usermenu__head-avatar--initial">
                {initial}
              </span>
            )}
            <div className="kfm-usermenu__head-info">
              <div className="kfm-usermenu__head-name">{user?.name || 'Staff'}</div>
              <div className="kfm-usermenu__head-email">{user?.email || ''}</div>
              <div className="kfm-usermenu__head-role">{user?.role?.name || ''}</div>
            </div>
          </div>

          <div className="kfm-usermenu__links">
            <Link
              to="/settings"
              className="kfm-usermenu__link"
              onClick={() => setOpen(false)}
            >
              <i className="bi bi-person"></i>
              Profile
            </Link>
            <Link
              to="/settings"
              className="kfm-usermenu__link"
              onClick={() => setOpen(false)}
            >
              <i className="bi bi-gear"></i>
              Settings
            </Link>
          </div>

          <div className="kfm-usermenu__footer">
            <button
              type="button"
              className="kfm-usermenu__signout"
              onClick={logout}
            >
              <i className="bi bi-box-arrow-right"></i>
              Sign out
            </button>
          </div>
        </div>
      )}
    </div>
  );
}