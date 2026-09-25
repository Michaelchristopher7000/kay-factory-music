import { useEffect, useState } from 'react';
import { NAV_LINKS } from '../../data/landing';

export default function Navbar() {
  const [scrolled, setScrolled] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 20);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  useEffect(() => {
    document.body.style.overflow = menuOpen ? 'hidden' : '';
    return () => { document.body.style.overflow = ''; };
  }, [menuOpen]);

  const close = () => setMenuOpen(false);

  return (
    <>
      <nav className={`kfm-nav ${scrolled ? 'is-scrolled' : ''}`}>
        <div className="kfm-container kfm-nav__inner">
          <a
            href="/"
            className="kfm-logo"
            onClick={close}
            aria-label="Kay Factory Music — Home"
          >
            <img
              src="/images/kfm-logo-white.png"
              alt="Kay Factory Music"
              className="kfm-logo__img"
            />
          </a>

          <ul className="kfm-nav__links">
            {NAV_LINKS.map((link) => (
              <li key={link.href}>
                <a href={link.href} className="kfm-nav__link">
                  {link.label}
                </a>
              </li>
            ))}
          </ul>

          <div className="kfm-nav__actions">
            <a href="/submit-demo" className="kfm-nav__cta">
              Submit Demo
            </a>

            <button
              type="button"
              className="kfm-nav__toggle"
              aria-label="Toggle menu"
              aria-expanded={menuOpen}
              onClick={() => setMenuOpen((v) => !v)}
            >
              <i className={`bi ${menuOpen ? 'bi-x-lg' : 'bi-list'}`}></i>
            </button>
          </div>
        </div>
      </nav>

      <div className={`kfm-mobile-menu ${menuOpen ? 'is-open' : ''}`}>
        {NAV_LINKS.map((link) => (
          <a
            key={link.href}
            href={link.href}
            className="kfm-mobile-menu__link"
            onClick={close}
          >
            {link.label}
          </a>
        ))}
        <a
          href="/submit-demo"
          className="kfm-mobile-menu__link"
          onClick={close}
        >
          Submit Demo
        </a>
      </div>
    </>
  );
}