import { useEffect, useState } from 'react';
import publicApi from '../../publicApi';
import Reveal from '../Reveal';

const formatDate = (d) => {
  if (!d) return '';
  try {
    return new Date(d).toLocaleDateString(undefined, {
      weekday: 'long',
      month: 'long',
      day: 'numeric',
      year: 'numeric',
    });
  } catch {
    return '';
  }
};

const formatTime = (t) => {
  if (!t) return '';
  return t.slice(0, 5);
};

export default function EventFeature() {
  const [event, setEvent] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let alive = true;

    (async () => {
      try {
        const { data } = await publicApi.get('/events/next');
        if (!alive) return;
        setEvent(data?.data || null);
      } catch {
        if (alive) setEvent(null);
      } finally {
        if (alive) setLoading(false);
      }
    })();

    return () => { alive = false; };
  }, []);

  // Hide the whole section while loading or when there's no event
  if (loading || !event) return null;

  const dateLabel = formatDate(event.event_date);
  const timeLabel = formatTime(event.event_time);

  return (
    <section className="kfm-event">
      <div className="kfm-container">
        <div className="kfm-event__grid">
          {/* LEFT — Copy */}
          <Reveal>
            <div className="kfm-event__body">
              <div className="kfm-event__eyebrow">
                <span className="kfm-event__eyebrow-line" />
                Upcoming Experience
              </div>

              <h2 className="kfm-event__title">
                {event.title}
              </h2>

              {event.description && (
                <p className="kfm-event__lede">
                  {event.description}
                </p>
              )}

              <ul className="kfm-event__meta">
                {dateLabel && (
                  <li>
                    <i className="bi bi-calendar3" aria-hidden="true" />
                    <span>
                      {dateLabel}
                      {timeLabel && ` · ${timeLabel}`}
                    </span>
                  </li>
                )}
                {(event.city || event.country) && (
                  <li>
                    <i className="bi bi-geo-alt" aria-hidden="true" />
                    <span>
                      {[event.city, event.country].filter(Boolean).join(', ')}
                    </span>
                  </li>
                )}
                {event.venue && (
                  <li>
                    <i className="bi bi-geo" aria-hidden="true" />
                    <span>{event.venue}</span>
                  </li>
                )}
                {event.artist?.name && (
                  <li>
                    <i className="bi bi-person-badge" aria-hidden="true" />
                    <span>Featuring {event.artist.name}</span>
                  </li>
                )}
              </ul>

              <div className="kfm-event__actions">
                {event.ticket_url ? (
                  <a
                    href={event.ticket_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="kfm-event__cta"
                  >
                    Get tickets
                    <i className="bi bi-arrow-up-right" aria-hidden="true" />
                  </a>
                ) : (
                  <a href="/contact" className="kfm-event__cta">
                    Join the list
                    <i className="bi bi-arrow-up-right" aria-hidden="true" />
                  </a>
                )}

                {event.event_url && (
                  <a
                    href={event.event_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="kfm-event__secondary"
                  >
                    Event details
                    <i className="bi bi-arrow-right" aria-hidden="true" />
                  </a>
                )}
              </div>
            </div>
          </Reveal>

          {/* RIGHT — Poster */}
          <Reveal delay={120}>
            <div className="kfm-event__poster">
              {event.image_url ? (
                <>
                  <img
                    src={event.image_url}
                    alt={event.title}
                    className="kfm-event__poster-image"
                  />
                  <div className="kfm-event__poster-shade" aria-hidden="true" />
                </>
              ) : (
                <div className="kfm-event__poster-bg" aria-hidden="true">
                  <div className="kfm-event__poster-scan" />
                </div>
              )}

              <div className="kfm-event__poster-content">
                <div className="kfm-event__poster-mark">KFM</div>

                <div>
                  <div className="kfm-event__poster-title">
                    {event.city || 'Live'}
                  </div>

                  <div className="kfm-event__poster-list">
                    {event.artist?.name && (
                      <span>{event.artist.name}</span>
                    )}
                    {dateLabel && (
                      <span>{dateLabel}</span>
                    )}
                    {event.venue && (
                      <span>{event.venue}</span>
                    )}
                  </div>
                </div>

                <div className="kfm-event__poster-footer">
                  <span>Kay Factory Music</span>
                  <span>
                    {new Date(event.event_date).toLocaleDateString(undefined, {
                      month: 'short',
                      day: 'numeric',
                    })}
                  </span>
                </div>
              </div>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  );
}