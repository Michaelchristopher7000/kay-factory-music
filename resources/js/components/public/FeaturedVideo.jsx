import { useEffect, useState } from 'react';
import publicApi from '../../publicApi';
import Reveal from '../Reveal';

export default function FeaturedVideo() {
  const [video, setVideo] = useState(null);
  const [loading, setLoading] = useState(true);
  const [playing, setPlaying] = useState(false);

  useEffect(() => {
    let alive = true;

    (async () => {
      try {
        const { data } = await publicApi.get('/videos/featured');
        if (!alive) return;
        setVideo(data?.data || null);
      } catch {
        if (alive) setVideo(null);
      } finally {
        if (alive) setLoading(false);
      }
    })();

    return () => { alive = false; };
  }, []);

  if (loading || !video) return null;

  const isYouTube = video.source === 'youtube' && video.youtube_id;
  const thumbnail = video.thumbnail_url;

  const playVideo = () => setPlaying(true);

  return (
    <section className="kfm-featured-video">
      <div className="kfm-container">
        <div className="kfm-featured-video__grid">
          <Reveal>
            <div className="kfm-featured-video__player">
              <div className="kfm-featured-video__badge">Now Streaming</div>

              {playing && isYouTube ? (
                <iframe
                  title={video.title}
                  src={`https://www.youtube.com/embed/${video.youtube_id}?autoplay=1&rel=0`}
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                  allowFullScreen
                />
              ) : playing && video.video_url ? (
                <video
                  src={video.video_url}
                  controls
                  autoPlay
                  playsInline
                />
              ) : (
                <button
                  type="button"
                  className="kfm-featured-video__thumbnail"
                  onClick={playVideo}
                  aria-label={`Play ${video.title}`}
                >
                  {thumbnail ? (
                    <img src={thumbnail} alt={video.title} />
                  ) : (
                    <div className="kfm-featured-video__placeholder">
                      <i className="bi bi-play-circle" />
                    </div>
                  )}

                  <span className="kfm-featured-video__play-btn">
                    <i className="bi bi-play-fill" />
                  </span>

                  <div className="kfm-featured-video__caption">
                    <div className="kfm-featured-video__caption-title">
                      {video.title}
                    </div>
                    {video.artist?.name && (
                      <div className="kfm-featured-video__caption-artist">
                        {video.artist.name}
                      </div>
                    )}
                  </div>
                </button>
              )}
            </div>
          </Reveal>

          <Reveal delay={120}>
            <div className="kfm-featured-video__body">
              {video.artist?.name && (
                <div className="kfm-featured-video__eyebrow">
                  <i className="bi bi-music-note" /> {video.artist.name}
                </div>
              )}

              <h2 className="kfm-featured-video__title">{video.title}</h2>

              {video.type && (
                <p className="kfm-featured-video__subtitle">
                  {video.type.replace(/_/g, ' ')}
                </p>
              )}

              {video.description && (
                <p className="kfm-featured-video__lede">{video.description}</p>
              )}

              <div className="kfm-featured-video__actions">
                <button
                  type="button"
                  className="kfm-featured-video__btn kfm-featured-video__btn--primary"
                  onClick={playVideo}
                >
                  <i className="bi bi-play-fill" /> Play
                </button>

                {video.artist?.slug && (
                  <a
                    href={`/artists/${video.artist.slug}`}
                    className="kfm-featured-video__btn kfm-featured-video__btn--ghost"
                  >
                    View artist
                  </a>
                )}
              </div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  );
}