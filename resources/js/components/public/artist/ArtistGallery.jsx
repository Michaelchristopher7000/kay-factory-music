import { useState } from 'react';
import GalleryLightbox from './GalleryLightbox';

export default function ArtistGallery({ images = [] }) {
  const [activeIndex, setActiveIndex] = useState(null);

  if (!images.length) return null;

  const open = (i) => setActiveIndex(i);
  const close = () => setActiveIndex(null);
  const prev = () => setActiveIndex((i) => Math.max(0, i - 1));
  const next = () =>
    setActiveIndex((i) => Math.min(images.length - 1, i + 1));

  return (
    <section className="kfm-section kfm-artist-gallery">
      <div className="kfm-container">
        <div className="kfm-section__head">
          <div>
            <div className="kfm-eyebrow">Gallery</div>
            <h2 className="kfm-display kfm-section__title">Photos</h2>
          </div>
        </div>

        <div className="kfm-gallery-grid">
          {images.map((img, i) => (
            <button
              key={img.id}
              type="button"
              className="kfm-gallery-item"
              onClick={() => open(i)}
              aria-label={img.caption || `View image ${i + 1}`}
            >
              <img
                src={img.image_url}
                alt={img.alt_text || img.caption || ''}
                loading="lazy"
              />
              <span className="kfm-gallery-item__shade" />
            </button>
          ))}
        </div>
      </div>

      <GalleryLightbox
        images={images}
        index={activeIndex}
        onClose={close}
        onPrev={prev}
        onNext={next}
      />
    </section>
  );
}