import { useEffect } from 'react';
import Navbar from '../../components/public/Navbar';
import Footer from '../../components/public/Footer';
import Reveal from '../../components/Reveal';
import {
  LoadingState,
  ErrorState,
  EmptyState,
} from '../../components/public/StateViews';
import { usePublicResource } from '../../hooks/usePublicResource';

const formatDuration = (s) => {
  if (!s) return '—';
  const m = Math.floor(s / 60);
  const sec = String(s % 60).padStart(2, '0');
  return `${m}:${sec}`;
};

export default function ReleasePage({ releaseSlug }) {
  useEffect(() => { window.scrollTo(0, 0); }, [releaseSlug]);

  const { data, loading, error, notFound, reload } = usePublicResource(
    releaseSlug ? `/releases/${releaseSlug}` : null
  );

  const release = data?.data || null;
  const tracks = Array.isArray(release?.tracks) ? release.tracks : [];
  const artist = release?.artist || null;

  return (
    <>
      <Navbar />
      <main>
        {loading && <LoadingState label="Loading release" />}

        {!loading && notFound && (
          <section className="kfm-page-hero">
            <div className="kfm-container kfm-page-hero__content">
              <a href="/music" className="kfm-back">All music</a>
              <div className="kfm-page-hero__eyebrow">Not Found</div>
              <h1 className="kfm-display kfm-page-hero__title">Release not found</h1>
            </div>
          </section>
        )}

        {!loading && !notFound && error && (
          <section className="kfm-section">
            <div className="kfm-container">
              <a href="/music" className="kfm-back">All music</a>
              <ErrorState message={error} onRetry={reload} />
            </div>
          </section>
        )}

        {!loading && !notFound && !error && release && (
          <>
            <section className="kfm-release-hero">
              <div className="kfm-container">
                <a href="/music" className="kfm-back">All music</a>
                <div className="kfm-release-hero__grid">
                  <Reveal>
                    <div className="kfm-release-hero__cover">
                      {release.cover_art_path ? (
                        <img src={release.cover_art_path} alt={release.title} />
                      ) : (
                        <div className="kfm-release-hero__placeholder">
                          <i className="bi bi-vinyl" aria-hidden="true"></i>
                        </div>
                      )}
                    </div>
                  </Reveal>

                  <Reveal delay={120}>
                    <div>
                      {release.type && (
                        <span className="kfm-release-hero__type">
                          {release.type.toUpperCase()}
                        </span>
                      )}
                      <h1 className="kfm-display kfm-release-hero__title">
                        {release.title}
                      </h1>

                      {artist?.name && (
                        <p className="kfm-release-hero__artist">
                          by{' '}
                          {artist.slug ? (
                            <a href={`/artists/${artist.slug}`}>{artist.name}</a>
                          ) : (
                            artist.name
                          )}
                        </p>
                      )}

                      {release.description && (
                        <p className="kfm-release-hero__desc">{release.description}</p>
                      )}

                      <div className="kfm-release-hero__meta">
                        {release.release_date && (
                          <div className="kfm-release-hero__meta-item">
                            <span className="kfm-release-hero__meta-label">Released</span>
                            <span className="kfm-release-hero__meta-value">
                              {release.release_date}
                            </span>
                          </div>
                        )}
                        {release.type && (
                          <div className="kfm-release-hero__meta-item">
                            <span className="kfm-release-hero__meta-label">Type</span>
                            <span className="kfm-release-hero__meta-value">
                              {release.type.toUpperCase()}
                            </span>
                          </div>
                        )}
                        {tracks.length > 0 && (
                          <div className="kfm-release-hero__meta-item">
                            <span className="kfm-release-hero__meta-label">Tracks</span>
                            <span className="kfm-release-hero__meta-value">
                              {tracks.length}
                            </span>
                          </div>
                        )}
                      </div>

                      {release.label_copy && (
                        <p className="text-muted small" style={{ marginTop: 0 }}>
                          {release.label_copy}
                        </p>
                      )}
                    </div>
                  </Reveal>
                </div>
              </div>
            </section>

            <section className="kfm-section">
              <div className="kfm-container">
                <div className="kfm-section__head">
                  <div>
                    <div className="kfm-eyebrow">Tracklist</div>
                    <h2 className="kfm-display kfm-section__title">{release.title}</h2>
                  </div>
                </div>

                {tracks.length === 0 ? (
                  <EmptyState message="No tracks listed yet." />
                ) : (
                  <ul className="kfm-tracklist">
                    {tracks.map((track, i) => (
                      <li key={`${track.title}-${i}`}>
                        <span className="kfm-tracklist__num">
                          {String(i + 1).padStart(2, '0')}
                        </span>
                        <span className="kfm-tracklist__title">
                          {track.title}
                          {track.is_explicit && (
                            <span className="badge bg-danger-subtle text-danger-emphasis ms-2">
                              E
                            </span>
                          )}
                        </span>
                        <span className="kfm-tracklist__duration">
                          {formatDuration(track.duration_seconds)}
                        </span>
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            </section>
          </>
        )}
      </main>
      <Footer />
    </>
  );
}
