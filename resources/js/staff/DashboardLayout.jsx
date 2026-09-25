import { useEffect, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import Sidebar from './Sidebar';
import Topbar from './Topbar';

export default function DashboardLayout() {
  const [drawerOpen, setDrawerOpen] = useState(false);
  const location = useLocation();

  // Close drawer whenever the route changes
  useEffect(() => {
    setDrawerOpen(false);
  }, [location.pathname]);

  // Lock body scroll when drawer is open on mobile
  useEffect(() => {
    if (drawerOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [drawerOpen]);

  // Close on Escape key
  useEffect(() => {
    const onKey = (e) => {
      if (e.key === 'Escape') setDrawerOpen(false);
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, []);

  return (
    <div className={`kfm-shell ${drawerOpen ? 'is-drawer-open' : ''}`}>
      <Sidebar onNavigate={() => setDrawerOpen(false)} />

      {/* Backdrop — only rendered when drawer is open, for tap-to-close on mobile */}
      {drawerOpen && (
        <div
          className="kfm-shell__backdrop"
          onClick={() => setDrawerOpen(false)}
          aria-hidden="true"
        />
      )}

      <div className="kfm-main">
        <Topbar
          onToggleDrawer={() => setDrawerOpen((v) => !v)}
          drawerOpen={drawerOpen}
        />
        <div className="kfm-content">
          <Outlet />
        </div>
      </div>
    </div>
  );
}