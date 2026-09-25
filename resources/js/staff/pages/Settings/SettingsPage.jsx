import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import ProfileSection from './ProfileSection';
import SecuritySection from './SecuritySection';

const TABS = [
  { key: 'profile', label: 'Profile', icon: 'bi-person' },
  { key: 'security', label: 'Security', icon: 'bi-shield-lock' },
];

export default function SettingsPage() {
  useEffect(() => { window.scrollTo(0, 0); }, []);

  const [params, setParams] = useSearchParams();
  const initialTab = params.get('tab') === 'security' ? 'security' : 'profile';
  const [tab, setTab] = useState(initialTab);

  useEffect(() => {
    setParams({ tab }, { replace: true });
  }, [tab, setParams]);

  return (
    <>
      <div className="mb-4">
        <h4 className="mb-1">Settings</h4>
        <p className="text-muted mb-0 small">
          Manage your profile and account security.
        </p>
      </div>

      <div className="kfm-settings-tabs">
        {TABS.map((t) => (
          <button
            key={t.key}
            type="button"
            className={`kfm-settings-tab ${tab === t.key ? 'is-active' : ''}`}
            onClick={() => setTab(t.key)}
          >
            <i className={`bi ${t.icon}`}></i>
            {t.label}
          </button>
        ))}
      </div>

      <div className="kfm-settings-body">
        {tab === 'profile' && <ProfileSection />}
        {tab === 'security' && <SecuritySection />}
      </div>
    </>
  );
}