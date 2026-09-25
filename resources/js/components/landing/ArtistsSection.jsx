import { ARTISTS } from '../../data/landing';
import Reveal from '../Reveal';

export default function ArtistsSection() {
  return (
    <section id="artists" className="kfm-section">
      <div className="kfm-container">
        <div className="kfm-section__head kfm-section__head--split">
          <div>
            <div className="kfm-eyebrow">The Roster</div>
            <h2 className="kfm-display kfm-section__title">Our Artists</h2>
          </div>
          <a href="#" className="kfm-btn kfm-btn--link">View All</a>
        </div>

        <div className="kfm-artists">
          {ARTISTS.map((artist, i) => (
            <Reveal key={artist.name} delay={i * 60}>
              <a href="#" className="kfm-artist-card">
                <img src={artist.image} alt={artist.name} className="kfm-artist-card__image" loading="lazy" />
                <div className="kfm-artist-card__overlay">
                  <h3 className="kfm-display kfm-artist-card__name">{artist.name}</h3>
                  <span className="kfm-artist-card__genre">{artist.genre}</span>
                </div>
              </a>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}