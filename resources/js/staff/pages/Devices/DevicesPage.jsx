import { useEffect, useState, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { devicesApi, relativeTime, deviceIcon } from '../../devicesApi';
import { clearSession } from '../../auth';

export default function DevicesPage() {
  const navigate = useNavigate();

  const [devices, setDevices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [revokingId, setRevokingId] = useState(null);
  const [bulkAction, setBulkAction] = useState(null); // 'others' | 'all' | null

  const fetchDevices = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const res = await devicesApi.list();
      setDevices(res.data?.data || []);
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Unable to load devices. Please try again.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchDevices(); }, [fetchDevices]);

  const revoke = async (device) => {
    const confirmed = window.confirm(
      device.is_current
        ? 'This is your current device. Revoking it will sign you out. Continue?'
        : `Revoke access for ${device.browser} on ${device.platform}?`
    );
    if (!confirmed) return;

    setRevokingId(device.id);
    try {
      const res = await devicesApi.revoke(device.id);
      if (res.data?.was_current) {
        clearSession();
        navigate('/staff/login', { replace: true });
        return;
      }
      setDevices((prev) => prev.filter((d) => d.id !== device.id));
    } catch {
      alert('Could not revoke this device. Please try again.');
    } finally {
      setRevokingId(null);
    }
  };

  const revokeOthers = async () => {
    const others = devices.filter((d) => !d.is_current).length;
    if (others === 0) {
      alert('There are no other sessions to revoke.');
      return;
    }
    if (!window.confirm(`Sign out of ${others} other session${others > 1 ? 's' : ''}?`)) return;

    setBulkAction('others');
    try {
      await devicesApi.revokeOthers();
      setDevices((prev) => prev.filter((d) => d.is_current));
    } catch {
      alert('Could not revoke other sessions.');
    } finally {
      setBulkAction(null);
    }
  };

  const revokeAll = async () => {
    if (!window.confirm('Sign out of ALL devices, including this one? You will need to sign in again.')) return;

    setBulkAction('all');
    try {
      await devicesApi.revokeAll();
      clearSession();
      navigate('/staff/login', { replace: true });
    } catch {
      alert('Could not revoke all sessions.');
      setBulkAction(null);
    }
  };

  const otherCount = devices.filter((d) => !d.is_current).length;

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h4 className="mb-1">Devices & Sessions</h4>
          <p className="text-muted mb-0 small">
            {devices.length} {devices.length === 1 ? 'device' : 'devices'} with access
          </p>
        </div>

        {devices.length > 0 && (
          <div className="d-flex gap-2 flex-wrap">
            <button
              type="button"
              className="btn btn-sm btn-outline-secondary"
              onClick={revokeOthers}
              disabled={bulkAction !== null || otherCount === 0}
            >
              {bulkAction === 'others' ? (
                <><span className="spinner-border spinner-border-sm me-2"></span>Revoking…</>
              ) : (
                <><i className="bi bi-shield-x me-1"></i>Sign out other sessions</>
              )}
            </button>

            <button
              type="button"
              className="btn btn-sm btn-outline-danger"
              onClick={revokeAll}
              disabled={bulkAction !== null}
            >
              {bulkAction === 'all' ? (
                <><span className="spinner-border spinner-border-sm me-2"></span>Signing out…</>
              ) : (
                <><i className="bi bi-box-arrow-right me-1"></i>Sign out everywhere</>
              )}
            </button>
          </div>
        )}
      </div>

      {loading && (
        <div className="text-center py-5 text-muted">
          <div className="spinner-border spinner-border-sm me-2"></div>
          Loading…
        </div>
      )}

      {!loading && error && <div className="alert alert-danger">{error}</div>}

      {!loading && !error && devices.length === 0 && (
        <div className="card border-0 shadow-sm">
          <div className="card-body text-center text-muted py-5">
            <i className="bi bi-laptop fs-1 d-block mb-3 opacity-50"></i>
            No devices on record.
          </div>
        </div>
      )}

      {!loading && !error && devices.length > 0 && (
        <div className="card border-0 shadow-sm">
          <ul className="list-unstyled mb-0">
            {devices.map((d) => (
              <li key={d.id} className="kfm-device-row">
                <div className="kfm-device-row__icon">
                  <i className={`bi ${deviceIcon(d.device)}`}></i>
                </div>

                <div className="kfm-device-row__body">
                  <div className="kfm-device-row__title">
                    {d.browser} on {d.platform}
                    {d.is_current && (
                      <span className="kfm-device-row__badge">This device</span>
                    )}
                  </div>

                  <div className="kfm-device-row__meta">
                    {d.location && (
                      <>
                        <i className="bi bi-geo-alt me-1"></i>
                        {d.location}
                        <span className="mx-2">·</span>
                      </>
                    )}
                    <i className="bi bi-hdd-network me-1"></i>
                    {d.ip_address}
                  </div>

                  <div className="kfm-device-row__meta text-muted">
                    <i className="bi bi-clock me-1"></i>
                    Last active {relativeTime(d.last_seen_at)}
                    <span className="mx-2">·</span>
                    First seen {relativeTime(d.first_seen_at)}
                  </div>
                </div>

                <div className="kfm-device-row__actions">
                  <button
                    type="button"
                    className="btn btn-sm btn-outline-danger"
                    onClick={() => revoke(d)}
                    disabled={revokingId === d.id || bulkAction !== null}
                  >
                    {revokingId === d.id ? (
                      <><span className="spinner-border spinner-border-sm me-2"></span>Revoking…</>
                    ) : (
                      <><i className="bi bi-box-arrow-right me-1"></i>Revoke</>
                    )}
                  </button>
                </div>
              </li>
            ))}
          </ul>
        </div>
      )}
    </>
  );
}