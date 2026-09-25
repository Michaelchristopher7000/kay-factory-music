import { useEffect } from 'react';

export default function VideoPlayerModal({ video, onClose }) {
  useEffect(() => {
    if (!video) return;

    const onKey = (e) => {
      if (e.key === 'Escape') onClose();
    };
    document.addEventListener('keydown', onKey);
    document.body.style.overflow = 'hidden';

    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = '';
    };
  }, [video, onClose]);

  if (!video) return null;

  return (
    <div className="kfm-video-modal" role="dialog" aria-modal="true">
      <div className="kfm-video-modal__backdrop" onClick={onClose} />

      <div className="kfm-video-modal__content">
        <button
          type="button"
          className="kfm-video-modal__close"
          onClick={onClose}
          aria-label="Close"
        >
          <i className="bi bi-x-lg" aria-hidden="true" />
        </button>

        <div className="kfm-video-modal__player">
          {video.source === 'youtube' && video.youtube_id ? (
            <iframe
              title={video.title}
              src={`https://www.youtube.com/embed/${video.youtube_id}?autoplay=1&rel=0`}
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowFullScreen
            />
          ) : video.video_url ? (
            <video
              src={video.video_url}
              controls
              autoPlay
              playsInline
            />
          ) : (
            <div className="kfm-video-modal__empty">
              Video source unavailable.
            </div>
          )}
        </div>

        <div className="kfm-video-modal__meta">
          <div className="kfm-video-modal__title">{video.title}</div>
          {video.description && (
            <div className="kfm-video-modal__desc">{video.description}</div>
          )}
        </div>
      </div>
    </div>
  );
}