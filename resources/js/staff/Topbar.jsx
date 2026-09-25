import { useLocation, useNavigate } from 'react-router-dom';
import NotificationsDropdown from './components/NotificationsDropdown';
import UserMenu from './components/UserMenu';
import TopbarSearch from './components/TopbarSearch';

export default function Topbar({ onToggleDrawer, drawerOpen }) {
  const location = useLocation();
  const navigate = useNavigate();

  // Show back arrow on any page except the dashboard index
  const showBack = location.pathname !== '/' && location.pathname !== '';

  return (
    <header className="kfm-topbar">
      {/* Left cluster: hamburger (mobile) + back (optional) */}
      <div className="kfm-topbar__left">
        <button
          type="button"
          className="kfm-topbar__icon-btn kfm-topbar__burger"
          onClick={onToggleDrawer}
          aria-label={drawerOpen ? 'Close menu' : 'Open menu'}
          aria-expanded={drawerOpen}
        >
          <i className={`bi ${drawerOpen ? 'bi-x-lg' : 'bi-list'}`}></i>
        </button>

        {showBack && (
          <button
            type="button"
            className="kfm-topbar__icon-btn kfm-topbar__back"
            onClick={() => navigate(-1)}
            aria-label="Go back"
          >
            <i className="bi bi-arrow-left"></i>
          </button>
        )}
      </div>

      {/* Center: search (hidden on mobile) */}
      <div className="kfm-topbar__center">
        <TopbarSearch />
      </div>

      {/* Right cluster: notifications + user menu */}
      <div className="kfm-topbar__actions">
        <NotificationsDropdown />
        <UserMenu />
      </div>
    </header>
  );
}