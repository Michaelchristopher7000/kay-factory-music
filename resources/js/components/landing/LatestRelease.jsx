import { LATEST_RELEASE } from '../../data/landing';
import Reveal from '../Reveal';

export default function LatestRelease() {
  return (
    <section className="kfm-section" style={{ background: 'var(--kfm-ink)' }}>
      <div className="kfm-container">
        <div className="kfm-latest">
          <Reveal>
            <div className="kfm-latest__cover">
              <span className="kfm-latest__badge">{LATEST_RELEASE.badge}</span>
              <img src={LATEST_RELEASE.cover} alt={LATEST_RELEASE.title} loading="lazy" />
            </div>
          </Reveal>

          <Reveal delay={120}>
            <div>
              <div className="kfm-eyebrow">{LATEST_RELEASE.type}</div>
              <h2 className="kfm-display kfm-latest__title">{LATEST_RELEASE.title}</h2>
              <p className="kfm-latest__artist">{LATEST_RELEASE.artist}</p>

              <div className="kfm-latest__meta">
                {LATEST_RELEASE.meta.map((m) => (
                  <div key={m.label} className="kfm-latest__meta-item">
                    <span className="kfm-latest__meta-label">{m.label}</span>
                    <span className="kfm-latest__meta-value">{m.value}</span>
                  </div>
                ))}
              </div>

              <div className="kfm-latest__streams">
                {LATEST_RELEASE.streams.map((s) => (
                  <a key={s.platform} href={s.url} className="kfm-stream-btn">
                    <i className={`bi ${s.icon}`}></i>
                    {s.platform}
                  </a>
                ))}
              </div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  );
}