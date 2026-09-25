import api from './api';

export const securityApi = {
  /**
   * Fetch the current user's own security activity timeline.
   * @param {{page?: number, per_page?: number}} params
   */
  activity: (params = {}) => api.get('/me/security/activity', { params }),
};