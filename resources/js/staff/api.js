import axios from 'axios';
import { getToken, clearSession } from './auth';

const api = axios.create({
  baseURL: '/api',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
  },
  withCredentials: true,
});

// Request — attach Bearer token if present
api.interceptors.request.use((config) => {
  const token = getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response — handle 401 (token expired / invalid)
api.interceptors.response.use(
  (res) => res,
  (err) => {
    const status = err.response?.status;

    if (status === 401) {
      const path = window.location.pathname;
      const onLoginPage = path.startsWith('/staff/login');

      clearSession();

      if (!onLoginPage) {
        window.location.href = '/staff/login';
      }
    }

    return Promise.reject(err);
  }
);

export default api;