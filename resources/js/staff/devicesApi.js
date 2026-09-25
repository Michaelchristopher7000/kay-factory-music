import api from './api';

export const devicesApi = {
  list:         () => api.get('/devices'),
  revoke:       (id) => api.delete(`/devices/${id}`),
  revokeOthers: () => api.post('/devices/revoke-others'),
  revokeAll:    () => api.post('/devices/revoke-all'),
};

export function relativeTime(input) {
  if (!input) return '';
  const d = new Date(input);
  const now = new Date();
  const diff = Math.floor((now - d) / 1000);

  if (diff < 60) return 'just now';
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;

  return d.toLocaleDateString(undefined, {
    year: 'numeric', month: 'short', day: 'numeric',
  });
}

export function deviceIcon(device) {
  const d = (device || '').toLowerCase();
  if (d.includes('mobile')) return 'bi-phone';
  if (d.includes('tablet')) return 'bi-tablet';
  if (d.includes('desktop')) return 'bi-display';
  return 'bi-laptop';
}