import { FEATURED_ARTIST } from '../../data/landing';
import Reveal from '../Reveal';

export default function FeaturedArtist() {
  return (
    <section className="kfm-section">
      <div className="kfm-container">
        <div className="kfm-featured">
          <Reveal>
            <div className="kfm-featured__image">
              <img src={FEATURED_ARTIST.image} alt={FEATURED_ARTIST.name} loading="lazy" />
            </div>
          </Reveal>

          <Reveal delay={120}>
            <div className="kfm-featured__body">
              <div className="kfm-eyebrow">{FEATURED_ARTIST.eyebrow}</div>
              <h2 className="kfm-display kfm-featured__name">{FEATURED_ARTIST.name}</h2>
              <div className="kfm-featured__meta">{FEATURED_ARTIST.genre}</div>
              <p className="kfm-featured__bio">{FEATURED_ARTIST.bio}</p>
              <a href={FEATURED_ARTIST.cta.href} className="kfm-btn kfm-btn--link">
                {FEATURED_ARTIST.cta.label}
              </a>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  );
}