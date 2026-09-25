import { useEffect } from 'react';

export default function GalleryLightbox({ images = [], index, onClose, onPrev, onNext }) {
  const current = index != null ? images[index] : null;

  useEffect(() => {
    if (!current) return;

    const onKey = (e) => {
      if (e.key === 'Escape') onClose();
      if (e.key === 'ArrowLeft') onPrev();
      if (e.key === 'ArrowRight') onNext();
    };
    document.addEventListener('keydown', onKey);
    document.body.style.overflow = 'hidden';

    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = '';
    };
  }, [current, onClose, onPrev, onNext]);

  if (!current) return null;

  const hasPrev = index > 0;
  const hasNext = index < images.length - 1;

  return (
    <div className="kfm-lightbox" role="dialog" aria-modal="true">
      <div className="kfm-lightbox__backdrop" onClick={onClose} />

      <button
        type="button"
        className="kfm-lightbox__close"
        onClick={onClose}
        aria-label="Close"
      >
        <i className="bi bi-x-lg" aria-hidden="true" />
      </button>

      {hasPrev && (
        <button
          type="button"
          className="kfm-lightbox__nav kfm-lightbox__nav--prev"
          onClick={onPrev}
          aria-label="Previous"
        >
          <i className="bi bi-chevron-left" aria-hidden="true" />
        </button>
      )}

      {hasNext && (
        <button
          type="button"
          className="kfm-lightbox__nav kfm-lightbox__nav--next"
          onClick={onNext}
          aria-label="Next"
        >
          <i className="bi bi-chevron-right" aria-hidden="true" />
        </button>
      )}

      <div className="kfm-lightbox__stage">
        <img
          src={current.image_url}
          alt={current.alt_text || current.caption || ''}
        />

        {current.caption && (
          <div className="kfm-lightbox__caption">{current.caption}</div>
        )}
      </div>

      <div className="kfm-lightbox__counter">
        {index + 1} / {images.length}
      </div>
    </div>
  );
}