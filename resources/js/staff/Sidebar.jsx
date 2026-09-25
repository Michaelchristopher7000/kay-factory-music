import { NavLink } from 'react-router-dom';
import { useAuth } from './auth';

const navItems = [
  { to: '/',                   icon: 'bi-grid-1x2',           label: 'Dashboard',          end: true },
  { to: '/artists',            icon: 'bi-people',             label: 'Artists' },
  { to: '/talent-submissions', icon: 'bi-star',               label: 'Talent Submissions' },
  { to: '/contact-messages',   icon: 'bi-envelope',           label: 'Contact Messages' },
  { to: '/messages',           icon: 'bi-chat-dots',          label: 'Messages' },
  { to: '/contracts',          icon: 'bi-file-earmark-text',  label: 'Contracts' },
  { to: '/tracks',             icon: 'bi-music-note-list',    label: 'Catalogue' },
  { to: '/releases',           icon: 'bi-vinyl',              label: 'Releases' },
  { to: '/artist-videos',      icon: 'bi-camera-video',       label: 'Artist Videos' },
  { to: '/artist-gallery',     icon: 'bi-images',             label: 'Artist Gallery' },
  { to: '/artist-events',      icon: 'bi-calendar-event',     label: 'Artist Events' },
  { to: '/distributions',      icon: 'bi-broadcast',          label: 'Distribution' },
  { to: '/finance',            icon: 'bi-wallet2',            label: 'Finance' },
  { to: '/royalties',          icon: 'bi-cash-stack',         label: 'Royalties' },
  { to: '/reports',            icon: 'bi-bar-chart-line',     label: 'Reports' },
  { to: '/audit-logs',         icon: 'bi-shield-check',       label: 'Audit Logs' },
  { to: '/devices',            icon: 'bi-laptop',             label: 'Devices' },
];

export default function Sidebar({ onNavigate }) {
  const { user } = useAuth();

  const avatarUrl = user?.avatar_url || null;
  const initial = (user?.name || 'S').charAt(0).toUpperCase();

  return (
    <aside className="kfm-sidebar">
      <div className="kfm-sidebar__brand">
        <img
          src="/images/kfm-logo-white.png"
          alt="Kay Factory Music"
          className="kfm-sidebar__brand-logo"
        />
      </div>

      <nav className="kfm-sidebar__nav">
        {navItems.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.end}
            onClick={() => onNavigate?.()}
            className={({ isActive }) =>
              `kfm-sidebar__link ${isActive ? 'is-active' : ''}`
            }
          >
            <i className={`bi ${item.icon}`}></i>
            <span>{item.label}</span>
          </NavLink>
        ))}
      </nav>

      <div className="kfm-sidebar__footer">
        <div className="kfm-sidebar__user">
          <div className="kfm-sidebar__avatar">
            {avatarUrl ? (
              <img src={avatarUrl} alt="" />
            ) : (
              <span>{initial}</span>
            )}
          </div>
          <div className="kfm-sidebar__user-info">
            <div className="kfm-sidebar__user-name">{user?.name || 'Staff'}</div>
            <div className="kfm-sidebar__user-role">{user?.role?.name || '—'}</div>
          </div>
        </div>
      </div>
    </aside>
  );
}