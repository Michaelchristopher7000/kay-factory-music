export default function VideoCard({ video, onPlay }) {
  if (!video) return null;

  const thumb = video.thumbnail_url;
  const isYouTube = video.source === 'youtube';

  return (
    <button
      type="button"
      className="kfm-video-card"
      onClick={() => onPlay(video)}
      aria-label={`Play ${video.title}`}
    >
      <div className="kfm-video-card__thumb">
        {thumb ? (
          <img src={thumb} alt={video.title} loading="lazy" />
        ) : (
          <div className="kfm-video-card__placeholder">
            <i className="bi bi-film" aria-hidden="true" />
          </div>
        )}

        <span className="kfm-video-card__play">
          <i className="bi bi-play-fill" aria-hidden="true" />
        </span>

        {isYouTube && (
          <span className="kfm-video-card__badge">
            <i className="bi bi-youtube" aria-hidden="true" />
          </span>
        )}

        {video.is_featured && (
          <span className="kfm-video-card__featured">
            <i className="bi bi-star-fill" aria-hidden="true" />
          </span>
        )}
      </div>

      <div className="kfm-video-card__body">
        <div className="kfm-video-card__title">{video.title}</div>
        {video.type && (
          <div className="kfm-video-card__meta">
            {video.type.replace(/_/g, ' ')}
          </div>
        )}
      </div>
    </button>
  );
}