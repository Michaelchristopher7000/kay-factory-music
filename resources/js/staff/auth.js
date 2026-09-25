import { useEffect, useState } from 'react';

const TOKEN_KEY = 'kfm_staff_token';
const USER_KEY = 'kfm_staff_user';

export function getToken() {
  return localStorage.getItem(TOKEN_KEY);
}

export function getStoredUser() {
  const raw = localStorage.getItem(USER_KEY);
  try {
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

export function isAuthenticated() {
  return !!getToken();
}

export function setSession(token, user) {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify(user || null));
  window.dispatchEvent(new Event('kfm:auth-changed'));
}

export function setStoredUser(user) {
  localStorage.setItem(USER_KEY, JSON.stringify(user || null));
  window.dispatchEvent(new Event('kfm:auth-changed'));
}

export function clearSession() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
  window.dispatchEvent(new Event('kfm:auth-changed'));
}

export function useAuth() {
  const [user, setUserState] = useState(getStoredUser());
  const [token, setToken] = useState(getToken());

  useEffect(() => {
    const sync = () => {
      setUserState(getStoredUser());
      setToken(getToken());
    };

    window.addEventListener('kfm:auth-changed', sync);
    window.addEventListener('storage', sync);

    return () => {
      window.removeEventListener('kfm:auth-changed', sync);
      window.removeEventListener('storage', sync);
    };
  }, []);

  // Wrapper that persists + triggers a re-render
  const setUser = (u) => {
    setStoredUser(u);
    setUserState(u);
  };

  return {
    user,
    token,
    setUser,
    isAuthenticated: !!token,
  };
}