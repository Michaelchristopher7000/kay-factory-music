import api from './api';

export const settingsApi = {
  // Profile
  updateProfile: (payload) => api.patch('/me', payload),
  uploadAvatar: (file) => {
    const fd = new FormData();
    fd.append('avatar', file);
    return api.post('/me/avatar', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  removeAvatar: () => api.delete('/me/avatar'),

  // Password
  changePassword: (payload) => api.post('/me/password', payload),

  // Forgot / reset
  requestReset: (email) => api.post('/forgot-password', { email }),
  resetPassword: (payload) => api.post('/reset-password', payload),
};