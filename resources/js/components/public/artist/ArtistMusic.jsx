const formatType = (type) => {
  if (!type) return '';
  return type.replace(/_/g, ' ').toUpperCase();
};

const formatDate = (d) => {
  if (!d) return '';
  try {
    return new Date(d).toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
    });
  } catch {
    return '';
  }
};

export default function ArtistMusic({ releases = [] }) {
  if (!releases.length) return null;

  return (
    <section className="kfm-section kfm-artist-music">
      <div className="kfm-container">
        <div className="kfm-section__head">
          <div>
            <div className="kfm-eyebrow">Discography</div>
            <h2 className="kfm-display kfm-section__title">Music</h2>
          </div>
        </div>

        <div className="kfm-releases">
          {releases.map((release) => (
            <a
              key={release.slug}
              href={`/music/${release.slug}`}
              className="kfm-release-card"
            >
              <div className="kfm-release-card__cover">
                {release.cover_art_path ? (
                  <img
                    src={release.cover_art_path}
                    alt={release.title}
                    loading="lazy"
                  />
                ) : (
                  <div className="kfm-release-card__placeholder">
                    <i className="bi bi-vinyl" aria-hidden="true" />
                  </div>
                )}
              </div>

              <h3 className="kfm-release-card__title">{release.title}</h3>

              {release.artist?.name && (
                <p className="kfm-release-card__artist">
                  {release.artist.name}
                </p>
              )}

              <span className="kfm-release-card__meta">
                {formatType(release.type)}
                {release.release_date
                  ? ` · ${formatDate(release.release_date)}`
                  : ''}
              </span>
            </a>
          ))}
        </div>
      </div>
    </section>
  );
}