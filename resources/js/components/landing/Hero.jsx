import { HERO } from '../../data/landing';

export default function Hero() {
  return (
    <section id="top" className="kfm-hero">
      <div className="kfm-hero__bg">
        <img src={HERO.image} alt="" loading="eager" />
      </div>

      <div className="kfm-container kfm-hero__content">
        <div className="kfm-hero__eyebrow kfm-eyebrow">{HERO.eyebrow}</div>

        <h1 className="kfm-display kfm-hero__title">
          {HERO.titleLine1}<br />
          <em>{HERO.titleLine2}</em>
        </h1>

        <p className="kfm-hero__lede">{HERO.lede}</p>

        <div className="kfm-hero__cta">
          <a href={HERO.primaryCta.href} className="kfm-btn kfm-btn--primary">
            {HERO.primaryCta.label}
          </a>
          <a href={HERO.secondaryCta.href} className="kfm-btn kfm-btn--ghost">
            {HERO.secondaryCta.label}
          </a>
        </div>
      </div>

      <div className="kfm-hero__scroll">
        <span>Scroll</span>
        <span className="kfm-hero__scroll-line"></span>
      </div>
    </section>
  );
}