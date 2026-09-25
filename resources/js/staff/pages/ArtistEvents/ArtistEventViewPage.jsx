import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../../api';

const formatDate = (d) => {
  if (!d) return '—';
  try {
    return new Date(d).toLocaleDateString(undefined, {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  } catch {
    return '—';
  }
};

const formatTime = (t) => (t ? t.slice(0, 5) : '—');

const isPast = (event) => {
  if (!event?.event_date) return false;
  return new Date(event.event_date) < new Date();
};

export default function ArtistEventViewPage() {
  const { id } = useParams();
  const navigate = useNavigate();

  const [event, setEvent] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get(`/artist-events/${id}`);
        setEvent(data.data);
      } catch (err) {
        if (err.response?.status === 401) return;
        if (err.response?.status === 404) {
          setError('Event not found.');
        } else {
          setError('Could not load event.');
        }
      } finally {
        setLoading(false);
      }
    })();
  }, [id]);

  if (loading) {
    return (
      <div className="text-center py-5 text-muted">
        <div className="spinner-border spinner-border-sm me-2"></div>
        Loading…
      </div>
    );
  }

  if (error || !event) {
    return (
      <>
        <div className="d-flex justify-content-between align-items-center mb-4">
          <div>
            <Link to="/artist-events" className="text-muted text-decoration-none small">
              <i className="bi bi-arrow-left me-1"></i> Back to Events
            </Link>
          </div>
        </div>
        <div className="alert alert-danger">{error || 'Event not found.'}</div>
      </>
    );
  }

  const past = isPast(event);

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <Link to="/artist-events" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to Events
          </Link>
          <h4 className="mb-0 mt-2">
            {event.title}
            {past && <span className="badge bg-light text-muted ms-2">Past</span>}
          </h4>
        </div>

        <div className="d-flex gap-2">
          {event.ticket_url && (
            <a
              href={event.ticket_url}
              target="_blank"
              rel="noopener noreferrer"
              className="btn btn-outline-dark"
            >
              <i className="bi bi-ticket-perforated me-1"></i> Tickets
            </a>
          )}
          <Link to={`/artist-events/${id}/edit`} className="btn btn-dark">
            <i className="bi bi-pencil me-1"></i> Edit
          </Link>
        </div>
      </div>

      {event.image_url && (
        <div className="card border-0 shadow-sm mb-3">
          <img
            src={event.image_url}
            alt={event.title}
            style={{
              width: '100%',
              maxHeight: 400,
              objectFit: 'cover',
              borderRadius: 8,
            }}
          />
        </div>
      )}

      <div className="row g-3">
        <div className="col-md-8">
          <div className="card border-0 shadow-sm h-100">
            <div className="card-header bg-white"><strong>Details</strong></div>
            <div className="card-body">
              <div className="row g-3">
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Date</div>
                  <div className="fw-semibold">{formatDate(event.event_date)}</div>
                </div>
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Doors Time</div>
                  <div className="fw-semibold">{formatTime(event.event_time)}</div>
                </div>
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Venue</div>
                  <div className="fw-semibold">{event.venue || '—'}</div>
                </div>
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Location</div>
                  <div className="fw-semibold">
                    {[event.city, event.country].filter(Boolean).join(', ') || '—'}
                  </div>
                </div>
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Status</div>
                  <div>
                    {event.status === 'published' ? (
                      <span className="badge bg-success-subtle text-success-emphasis">Published</span>
                    ) : (
                      <span className="badge bg-secondary-subtle text-secondary-emphasis">Draft</span>
                    )}
                  </div>
                </div>
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">When</div>
                  <div>
                    {past ? (
                      <span className="badge bg-light text-muted">Past event</span>
                    ) : (
                      <span className="badge bg-info-subtle text-info-emphasis">Upcoming</span>
                    )}
                  </div>
                </div>

                {event.description && (
                  <div className="col-12">
                    <div className="text-muted small text-uppercase">Description</div>
                    <div style={{ whiteSpace: 'pre-wrap' }}>{event.description}</div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="col-md-4">
          <div className="card border-0 shadow-sm">
            <div className="card-header bg-white"><strong>Links</strong></div>
            <div className="card-body">
              <div className="d-flex flex-column gap-2">
                {event.ticket_url ? (
                  <a
                    href={event.ticket_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn btn-outline-dark btn-sm"
                  >
                    <i className="bi bi-ticket-perforated me-1"></i> Ticket link
                  </a>
                ) : (
                  <div className="text-muted small">No ticket link set.</div>
                )}

                {event.event_url ? (
                  <a
                    href={event.event_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn btn-outline-dark btn-sm"
                  >
                    <i className="bi bi-link-45deg me-1"></i> Event page
                  </a>
                ) : (
                  <div className="text-muted small">No event link set.</div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}