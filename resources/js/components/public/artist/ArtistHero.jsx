export default function ArtistHero({ artist }) {
  if (!artist) return null;

  const socials = artist.social_links || {};
  const socialKeys = [
    { key: 'spotify', icon: 'bi-spotify', label: 'Spotify' },
    { key: 'apple_music', icon: 'bi-apple', label: 'Apple Music' },
    { key: 'instagram', icon: 'bi-instagram', label: 'Instagram' },
    { key: 'twitter', icon: 'bi-twitter-x', label: 'Twitter' },
    { key: 'youtube', icon: 'bi-youtube', label: 'YouTube' },
    { key: 'soundcloud', icon: 'bi-soundwave', label: 'SoundCloud' },
    { key: 'website', icon: 'bi-globe', label: 'Website' },
  ];
  const activeSocials = socialKeys.filter((s) => socials[s.key]);

  return (
    <section className="kfm-artist-hero">
      {artist.avatar && (
        <div className="kfm-artist-hero__bg" aria-hidden="true">
          <img src={artist.avatar} alt="" />
          <div className="kfm-artist-hero__overlay" />
        </div>
      )}

      <div className="kfm-container kfm-artist-hero__inner">
        <a href="/artists" className="kfm-back">
          Back to artists
        </a>

        <div className="kfm-artist-hero__content">
          {artist.genre && (
            <span className="kfm-artist-hero__chip">{artist.genre}</span>
          )}

          <h1 className="kfm-artist-hero__name">{artist.name}</h1>

          {(artist.city || artist.country) && (
            <div className="kfm-artist-hero__location">
              {[artist.city, artist.country].filter(Boolean).join(', ')}
            </div>
          )}

          {activeSocials.length > 0 && (
            <div className="kfm-artist-hero__social">
              {activeSocials.map((s) => (
                <a
                  key={s.key}
                  href={socials[s.key]}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="kfm-artist-hero__social-link"
                  aria-label={s.label}
                >
                  <i className={`bi ${s.icon}`} aria-hidden="true" />
                </a>
              ))}
            </div>
          )}
        </div>
      </div>
    </section>
  );
}