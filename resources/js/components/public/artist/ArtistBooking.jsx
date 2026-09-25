export default function ArtistBooking({ artist }) {
  if (!artist?.name) return null;

  return (
    <section className="kfm-section kfm-booking-cta">
      <div className="kfm-container">
        <div className="kfm-booking-cta__inner">
          <div className="kfm-eyebrow">Bookings</div>

          <h2 className="kfm-display kfm-booking-cta__title">
            Want to work with {artist.name}?
          </h2>

          <p className="kfm-booking-cta__lede">
            For bookings, features, and press inquiries — get in touch
            with Kay Factory Music.
          </p>

          <a href="/contact" className="kfm-booking-cta__btn">
            Contact us
            <i className="bi bi-arrow-up-right" aria-hidden="true" />
          </a>
        </div>
      </div>
    </section>
  );
}