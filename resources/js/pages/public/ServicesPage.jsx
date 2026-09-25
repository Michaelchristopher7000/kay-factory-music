import { useEffect } from 'react';
import Navbar from '../../components/public/Navbar';
import Footer from '../../components/public/Footer';
import Reveal from '../../components/Reveal';
import { SERVICES_PAGE } from '../../data/landing';

export default function ServicesPage() {
  useEffect(() => { window.scrollTo(0, 0); }, []);

  return (
    <>
      <Navbar />
      <main>
        {/* Hero */}
        <section className="kfm-page-hero">
          <div className="kfm-page-hero__bg">
            <img src={SERVICES_PAGE.hero.image} alt="" />
          </div>
          <div className="kfm-container kfm-page-hero__content">
            <a href="/" className="kfm-back">Back home</a>
            <div className="kfm-page-hero__eyebrow">{SERVICES_PAGE.hero.eyebrow}</div>
            <h1 className="kfm-display kfm-page-hero__title">
              {SERVICES_PAGE.hero.title}
            </h1>
            <p className="kfm-page-hero__lede">{SERVICES_PAGE.hero.lede}</p>
          </div>
        </section>

        {/* Services grid */}
        <section className="kfm-section">
          <div className="kfm-container">
            <div className="kfm-services">
              {SERVICES_PAGE.services.map((s, i) => (
                <Reveal key={s.number} delay={i * 60}>
                  <div className="kfm-service">
                    <span className="kfm-service__num">{s.number}</span>
                    <h3 className="kfm-service__title">{s.title}</h3>
                    <p className="kfm-service__text">{s.text}</p>
                    <ul className="kfm-service__points">
                      {s.points.map((p) => (
                        <li key={p}>{p}</li>
                      ))}
                    </ul>
                  </div>
                </Reveal>
              ))}
            </div>
          </div>
        </section>

        {/* Process */}
        <section className="kfm-section" style={{ background: 'var(--kfm-ink)' }}>
          <div className="kfm-container">
            <div className="kfm-section__head">
              <div>
                <div className="kfm-eyebrow">{SERVICES_PAGE.process.eyebrow}</div>
                <h2 className="kfm-display kfm-section__title">
                  {SERVICES_PAGE.process.title}
                </h2>
              </div>
            </div>
            <div className="kfm-process">
              {SERVICES_PAGE.process.steps.map((s, i) => (
                <Reveal key={s.number} delay={i * 80}>
                  <div className="kfm-process__item">
                    <span className="kfm-process__num">{s.number}</span>
                    <h3 className="kfm-process__title">{s.title}</h3>
                    <p className="kfm-process__text">{s.text}</p>
                  </div>
                </Reveal>
              ))}
            </div>
          </div>
        </section>

        {/* CTA */}
        <section className="kfm-section">
          <div className="kfm-container kfm-about-cta">
            <Reveal>
              <h2 className="kfm-display kfm-about-cta__title">
                {SERVICES_PAGE.cta.title}
              </h2>
              <p className="kfm-about-cta__lede">{SERVICES_PAGE.cta.lede}</p>
              <div className="kfm-about-cta__actions">
                <a href={SERVICES_PAGE.cta.primary.href} className="kfm-btn kfm-btn--primary">
                  {SERVICES_PAGE.cta.primary.label}
                </a>
              </div>
            </Reveal>
          </div>
        </section>
      </main>
      <Footer />
    </>
  );
}
