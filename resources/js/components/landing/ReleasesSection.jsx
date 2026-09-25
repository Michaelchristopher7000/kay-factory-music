import { RELEASES } from '../../data/landing';
import Reveal from '../Reveal';

export default function ReleasesSection() {
  return (
    <section id="music" className="kfm-section">
      <div className="kfm-container">
        <div className="kfm-section__head kfm-section__head--split">
          <div>
            <div className="kfm-eyebrow">The Catalogue</div>
            <h2 className="kfm-display kfm-section__title">Latest Releases</h2>
          </div>
          <a href="#" className="kfm-btn kfm-btn--link">View All Music</a>
        </div>

        <div className="kfm-releases">
          {RELEASES.map((release, i) => (
            <Reveal key={release.title} delay={(i % 4) * 60}>
              <a href="#" className="kfm-release-card">
                <div className="kfm-release-card__cover">
                  <img src={release.cover} alt={release.title} loading="lazy" />
                </div>
                <h3 className="kfm-release-card__title">{release.title}</h3>
                <p className="kfm-release-card__artist">{release.artist}</p>
                <span className="kfm-release-card__meta">
                  {release.type} · {release.date}
                </span>
              </a>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}