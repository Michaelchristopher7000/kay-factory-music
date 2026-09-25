import React from 'react';
import { createRoot } from 'react-dom/client';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '../css/public.css';
import CookieConsentBanner from './components/public/CookieConsentBanner';

const PAGES = {
  home:          React.lazy(() => import('./pages/public/HomePage')),
  artists:       React.lazy(() => import('./pages/public/ArtistsPage')),
  artist:        React.lazy(() => import('./pages/public/ArtistPage')),
  music:         React.lazy(() => import('./pages/public/MusicPage')),
  release:       React.lazy(() => import('./pages/public/ReleasePage')),
  about:         React.lazy(() => import('./pages/public/AboutPage')),
  services:      React.lazy(() => import('./pages/public/ServicesPage')),
  contact:       React.lazy(() => import('./pages/public/ContactPage')),
  'submit-demo': React.lazy(() => import('./pages/public/SubmitDemoPage')),

  // Legal
  privacy:       React.lazy(() => import('./pages/public/PrivacyPolicyPage')),
  terms:         React.lazy(() => import('./pages/public/TermsPage')),
  cookies:       React.lazy(() => import('./pages/public/CookiePolicyPage')),
};

const root = document.getElementById('app');
const dataPage = root?.dataset.page || 'home';
const rawProps = root?.dataset.props || '{}';

let props = {};
try { props = JSON.parse(rawProps); } catch { props = {}; }

const Page = PAGES[dataPage] || PAGES.home;

createRoot(root).render(
  <React.Suspense fallback={<div className="kfm-loading">Loading…</div>}>
    <Page {...props} />
    <CookieConsentBanner />
  </React.Suspense>
);