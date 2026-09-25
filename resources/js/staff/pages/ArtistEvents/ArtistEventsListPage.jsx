import { useEffect, useState, useCallback } from 'react';
import { Link, useParams } from 'react-router-dom';
import api from '../../api';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const formatDate = (d) => {
  if (!d) return '—';
  try {
    return new Date(d).toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  } catch {
    return '—';
  }
};

const formatTime = (t) => {
  if (!t) return '';
  return t.slice(0, 5);
};

const isPast = (event) => {
  if (!event?.event_date) return false;
  return new Date(event.event_date) < new Date();
};

export default function ArtistEventsListPage() {
  const { artistId } = useParams();

  const [events, setEvents] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 20 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [status, setStatus] = useState('');
  const [when, setWhen] = useState('all');
  const [page, setPage] = useState(1);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchEvents = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/artist-events', {
        params: {
          status: status || undefined,
          artist_id: artistId || undefined,
          per_page: 20,
          page,
        },
      });

      let list = data.data || [];

      if (when === 'upcoming') {
        list = list.filter((e) => !isPast(e));
      } else if (when === 'past') {
        list = list.filter((e) => isPast(e));
      }

      setEvents(list);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load events. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [status, artistId, when, page]);

  useEffect(() => { fetchEvents(); }, [fetchEvents]);
  useEffect(() => { setPage(1); }, [status, when]);

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);

    try {
      await api.delete(`/artist-events/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchEvents();
    } catch (err) {
      alert('Could not delete event. ' + (err.response?.data?.message || ''));
    } finally {
      setDeleting(false);
    }
  };

  const newLink = artistId
    ? `/artist-events/new?artist_id=${artistId}`
    : '/artist-events/new';

  const editLink = (id) =>
    artistId ? `/artist-events/${id}/edit?artist_id=${artistId}` : `/artist-events/${id}/edit`;

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h4 className="mb-1">Artist Events</h4>
          <p className="text-muted mb-0 small">
            Manage shows and appearances — {meta.total || 0} total
          </p>
        </div>

        <Link to={newLink} className="btn btn-dark">
          <i className="bi bi-plus-lg me-2"></i>
          Add Event
        </Link>
      </div>

      <div className="card border-0 shadow-sm mb-3">
        <div className="card-body">
          <div className="row g-2">
            <div className="col-6 col-md-4">
              <select
                className="form-select"
                value={when}
                onChange={(e) => setWhen(e.target.value)}
              >
                <option value="all">All events</option>
                <option value="upcoming">Upcoming</option>
                <option value="past">Past</option>
              </select>
            </div>
            <div className="col-6 col-md-4">
              <select
                className="form-select"
                value={status}
                onChange={(e) => setStatus(e.target.value)}
              >
                <option value="">All statuses</option>
                <option value="published">Published</option>
                <option value="draft">Draft</option>
              </select>
            </div>
            <div className="col-12 col-md-4 d-flex">
              <button
                type="button"
                className="btn btn-outline-secondary w-100"
                title="Refresh"
                onClick={fetchEvents}
              >
                <i className="bi bi-arrow-clockwise me-1"></i> Refresh
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* ---------- DESKTOP TABLE ---------- */}
      <div className="card border-0 shadow-sm kfm-desktop-only">
        <div className="table-responsive">
          <table className="table table-hover align-middle mb-0">
            <thead className="table-light">
              <tr>
                <th style={{ width: 140 }}>Date</th>
                <th>Event</th>
                <th style={{ width: 200 }}>Venue</th>
                <th style={{ width: 160 }}>Location</th>
                <th style={{ width: 110 }}>Status</th>
                <th style={{ width: 160 }} className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={6} className="text-center py-5 text-muted">
                    <div className="spinner-border spinner-border-sm me-2"></div>
                    Loading events…
                  </td>
                </tr>
              )}

              {!loading && error && (
                <tr>
                  <td colSpan={6} className="text-center py-5 text-danger">{error}</td>
                </tr>
              )}

              {!loading && !error && events.length === 0 && (
                <tr>
                  <td colSpan={6} className="text-center py-5 text-muted">
                    No events found.
                    {(status || when !== 'all') && <> Try clearing the filters.</>}
                  </td>
                </tr>
              )}

              {!loading && events.map((e) => {
                const past = isPast(e);
                return (
                  <tr key={e.id} className={past ? 'text-muted' : ''}>
                    <td>
                      <div className="fw-semibold">{formatDate(e.event_date)}</div>
                      {e.event_time && (
                        <div className="text-muted small">{formatTime(e.event_time)}</div>
                      )}
                    </td>
                    <td>
                      <div className="fw-semibold text-dark">{e.title}</div>
                      {e.description && (
                        <div
                          className="text-muted small text-truncate"
                          style={{ maxWidth: 320 }}
                        >
                          {e.description}
                        </div>
                      )}
                    </td>
                    <td className="small">{e.venue || '—'}</td>
                    <td className="small">
                      {[e.city, e.country].filter(Boolean).join(', ') || '—'}
                    </td>
                    <td>
                      {e.status === 'published' ? (
                        <span className="badge bg-success-subtle text-success-emphasis">
                          Published
                        </span>
                      ) : (
                        <span className="badge bg-secondary-subtle text-secondary-emphasis">
                          Draft
                        </span>
                      )}
                      {past && (
                        <span className="badge bg-light text-muted ms-1">Past</span>
                      )}
                    </td>
                    <td className="text-end">
                      <Link
                        to={`/artist-events/${e.id}`}
                        className="btn btn-sm btn-outline-secondary me-1"
                        title="View"
                      >
                        <i className="bi bi-eye"></i>
                      </Link>

                      <Link
                        to={editLink(e.id)}
                        className="btn btn-sm btn-outline-secondary me-1"
                        title="Edit"
                      >
                        <i className="bi bi-pencil"></i>
                      </Link>

                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger"
                        title="Delete"
                        onClick={() => setDeleteTarget(e)}
                      >
                        <i className="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>

        {!loading && meta.last_page > 1 && (
          <div className="card-footer bg-white d-flex justify-content-between align-items-center">
            <div className="text-muted small">
              Page {meta.current_page} of {meta.last_page}
            </div>
            <div>
              <button
                type="button"
                className="btn btn-sm btn-outline-secondary me-1"
                disabled={meta.current_page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                <i className="bi bi-chevron-left"></i> Prev
              </button>
              <button
                type="button"
                className="btn btn-sm btn-outline-secondary"
                disabled={meta.current_page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
              >
                Next <i className="bi bi-chevron-right"></i>
              </button>
            </div>
          </div>
        )}
      </div>

      {/* ---------- MOBILE CARDS ---------- */}
      <div className="kfm-mobile-only">
        {loading && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              <div className="spinner-border spinner-border-sm me-2"></div>
              Loading events…
            </div>
          </div>
        )}

        {!loading && error && <div className="alert alert-danger">{error}</div>}

        {!loading && !error && events.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No events found.
              {(status || when !== 'all') && <> Try clearing the filters.</>}
            </div>
          </div>
        )}

        {!loading && events.map((e) => {
          const past = isPast(e);
          return (
            <Link
              key={e.id}
              to={`/artist-events/${e.id}`}
              className="kfm-mobile-card"
            >
              <div className="kfm-mobile-card__head">
                <code className="kfm-mobile-card__ref">{formatDate(e.event_date)}</code>
                {e.status === 'published' ? (
                  <span className="badge bg-success-subtle text-success-emphasis">Published</span>
                ) : (
                  <span className="badge bg-secondary-subtle text-secondary-emphasis">Draft</span>
                )}
              </div>

              <div className="kfm-mobile-card__subject">{e.title}</div>

              <div className="kfm-mobile-card__meta">
                {e.event_time && (
                  <div className="kfm-mobile-card__meta-row">
                    <i className="bi bi-clock"></i>
                    <span>Doors {formatTime(e.event_time)}</span>
                  </div>
                )}
                {e.venue && (
                  <div className="kfm-mobile-card__meta-row">
                    <i className="bi bi-geo"></i>
                    <span className="text-truncate">{e.venue}</span>
                  </div>
                )}
                {(e.city || e.country) && (
                  <div className="kfm-mobile-card__meta-row">
                    <i className="bi bi-geo-alt"></i>
                    <span>{[e.city, e.country].filter(Boolean).join(', ')}</span>
                  </div>
                )}
                {past && (
                  <div className="kfm-mobile-card__meta-row">
                    <i className="bi bi-clock-history"></i>
                    <span>Past event</span>
                  </div>
                )}
              </div>
            </Link>
          );
        })}

        {!loading && meta.last_page > 1 && (
          <div className="d-flex justify-content-between align-items-center mt-3">
            <div className="text-muted small">
              Page {meta.current_page} of {meta.last_page}
            </div>
            <div>
              <button
                type="button"
                className="btn btn-sm btn-outline-secondary me-1"
                disabled={meta.current_page <= 1}
                onClick={() => setPage((p) => Math.max(1, p - 1))}
              >
                <i className="bi bi-chevron-left"></i> Prev
              </button>
              <button
                type="button"
                className="btn btn-sm btn-outline-secondary"
                disabled={meta.current_page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
              >
                Next <i className="bi bi-chevron-right"></i>
              </button>
            </div>
          </div>
        )}
      </div>

      <ConfirmDeleteModal
        show={!!deleteTarget}
        title="Delete event?"
        message={deleteTarget ? `"${deleteTarget.title}" will be permanently deleted.` : ''}
        loading={deleting}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </>
  );
}