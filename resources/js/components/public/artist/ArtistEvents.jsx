const formatDate = (d) => {
  if (!d) return '';
  try {
    return new Date(d).toLocaleDateString(undefined, {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  } catch {
    return '';
  }
};

const formatTime = (t) => (t ? t.slice(0, 5) : '');

export default function ArtistEvents({ events = [] }) {
  if (!events.length) return null;

  const now = new Date();
  const upcoming = events.filter((e) => new Date(e.event_date) >= now);
  const past = events.filter((e) => new Date(e.event_date) < now);

  return (
    <section className="kfm-section kfm-artist-events">
      <div className="kfm-container">
        <div className="kfm-section__head">
          <div>
            <div className="kfm-eyebrow">Live</div>
            <h2 className="kfm-display kfm-section__title">Shows</h2>
          </div>
        </div>

        {upcoming.length === 0 && past.length === 0 && (
          <div className="kfm-events-empty">No upcoming shows scheduled.</div>
        )}

        {upcoming.length > 0 && (
          <div className="kfm-events-list">
            {upcoming.map((e) => (
              <div key={e.id} className="kfm-event-row">
                <div className="kfm-event-row__date">
                  <span className="kfm-event-row__month">
                    {new Date(e.event_date).toLocaleDateString(undefined, {
                      month: 'short',
                    })}
                  </span>
                  <span className="kfm-event-row__day">
                    {new Date(e.event_date).getDate()}
                  </span>
                </div>

                <div className="kfm-event-row__info">
                  <div className="kfm-event-row__title">{e.title}</div>
                  <div className="kfm-event-row__venue">
                    {[e.venue, e.city, e.country]
                      .filter(Boolean)
                      .join(' · ')}
                  </div>
                  <div className="kfm-event-row__time">
                    {formatDate(e.event_date)}
                    {formatTime(e.event_time) &&
                      ` · Doors ${formatTime(e.event_time)}`}
                  </div>
                </div>

                {e.ticket_url && (
                  <a
                    href={e.ticket_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="kfm-event-row__cta"
                  >
                    Tickets <i className="bi bi-arrow-up-right" />
                  </a>
                )}
              </div>
            ))}
          </div>
        )}

        {past.length > 0 && (
          <details className="kfm-events-past">
            <summary>Past shows ({past.length})</summary>
            <div className="kfm-events-list kfm-events-list--past">
              {past.slice(0, 10).map((e) => (
                <div key={e.id} className="kfm-event-row kfm-event-row--past">
                  <div className="kfm-event-row__date">
                    <span className="kfm-event-row__month">
                      {new Date(e.event_date).toLocaleDateString(undefined, {
                        month: 'short',
                      })}
                    </span>
                    <span className="kfm-event-row__day">
                      {new Date(e.event_date).getDate()}
                    </span>
                  </div>

                  <div className="kfm-event-row__info">
                    <div className="kfm-event-row__title">{e.title}</div>
                    <div className="kfm-event-row__venue">
                      {[e.venue, e.city, e.country]
                        .filter(Boolean)
                        .join(' · ')}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </details>
        )}
      </div>
    </section>
  );
}