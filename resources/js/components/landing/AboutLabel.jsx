import { ABOUT } from '../../data/landing';
import Reveal from '../Reveal';

export default function AboutLabel() {
  return (
    <section id="about" className="kfm-section" style={{ background: 'var(--kfm-ink)' }}>
      <div className="kfm-container">
        <div className="kfm-about">
          <Reveal>
            <div className="kfm-about__image">
              <img src={ABOUT.image} alt="" loading="lazy" />
            </div>
          </Reveal>

          <Reveal delay={120}>
            <div>
              <div className="kfm-eyebrow">{ABOUT.eyebrow}</div>
              <h2 className="kfm-display kfm-about__title">{ABOUT.title}</h2>
              <p className="kfm-about__copy">{ABOUT.copy}</p>

              <ul className="kfm-about__pillars">
                {ABOUT.pillars.map((p, i) => (
                  <li key={p.title} className="kfm-about__pillar">
                    <span className="kfm-about__pillar-num">
                      {String(i + 1).padStart(2, '0')}
                    </span>
                    <h3 className="kfm-about__pillar-title">{p.title}</h3>
                    <p className="kfm-about__pillar-text">{p.text}</p>
                  </li>
                ))}
              </ul>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  );
}