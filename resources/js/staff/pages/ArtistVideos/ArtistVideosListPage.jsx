import { useEffect, useState, useCallback } from 'react';
import { Link, useParams } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

const TYPE_LABELS = {
  music_video: 'Music Video',
  visualizer: 'Visualizer',
  live: 'Live',
  interview: 'Interview',
  behind_the_scenes: 'Behind the Scenes',
};

export default function ArtistVideosListPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';
  const { artistId } = useParams();

  const [videos, setVideos] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 20 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchVideos = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/artist-videos', {
        params: {
          search: search || undefined,
          status: status || undefined,
          artist_id: artistId || undefined,
          per_page: 20,
          page,
        },
      });

      setVideos(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load videos. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [search, status, artistId, page]);

  useEffect(() => {
    const t = setTimeout(fetchVideos, 250);
    return () => clearTimeout(t);
  }, [fetchVideos]);

  useEffect(() => { setPage(1); }, [search, status]);

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);

    try {
      await api.delete(`/artist-videos/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchVideos();
    } catch (err) {
      alert('Could not delete video. ' + (err.response?.data?.message || ''));
    } finally {
      setDeleting(false);
    }
  };

  const newLink = artistId
    ? `/artist-videos/new?artist_id=${artistId}`
    : '/artist-videos/new';

  const editLink = (id) =>
    artistId ? `/artist-videos/${id}/edit?artist_id=${artistId}` : `/artist-videos/${id}/edit`;

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h4 className="mb-1">Artist Videos</h4>
          <p className="text-muted mb-0 small">
            Manage YouTube and uploaded videos — {meta.total || 0} total
          </p>
        </div>

        <Link to={newLink} className="btn btn-dark">
          <i className="bi bi-plus-lg me-2"></i>
          Add Video
        </Link>
      </div>

      <div className="card border-0 shadow-sm mb-3">
        <div className="card-body">
          <div className="row g-2">
            <div className="col-12 col-md-6">
              <div className="input-group">
                <span className="input-group-text bg-white">
                  <i className="bi bi-search"></i>
                </span>
                <input
                  type="text"
                  className="form-control"
                  placeholder="Search by title"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
            </div>
            <div className="col-8 col-md-4">
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
            <div className="col-4 col-md-2 d-flex">
              <button
                type="button"
                className="btn btn-outline-secondary w-100"
                title="Refresh"
                onClick={fetchVideos}
              >
                <i className="bi bi-arrow-clockwise"></i>
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
                <th style={{ width: 90 }}>Thumb</th>
                <th>Title</th>
                <th style={{ width: 140 }}>Type</th>
                <th style={{ width: 110 }}>Source</th>
                <th style={{ width: 110 }}>Status</th>
                <th style={{ width: 90 }}>Featured</th>
                <th style={{ width: 160 }} className="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              {loading && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    <div className="spinner-border spinner-border-sm me-2"></div>
                    Loading videos…
                  </td>
                </tr>
              )}

              {!loading && error && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-danger">{error}</td>
                </tr>
              )}

              {!loading && !error && videos.length === 0 && (
                <tr>
                  <td colSpan={7} className="text-center py-5 text-muted">
                    No videos found.
                    {(search || status) && <> Try clearing the filters.</>}
                  </td>
                </tr>
              )}

              {!loading && videos.map((v) => (
                <tr key={v.id}>
                  <td>
                    {v.thumbnail_url ? (
                      <img
                        src={v.thumbnail_url}
                        alt=""
                        style={{ width: 64, height: 40, objectFit: 'cover', borderRadius: 4 }}
                      />
                    ) : (
                      <div
                        className="bg-light d-flex align-items-center justify-content-center text-muted"
                        style={{ width: 64, height: 40, borderRadius: 4 }}
                      >
                        <i className="bi bi-film"></i>
                      </div>
                    )}
                  </td>
                  <td>
                    <div className="fw-semibold text-dark">{v.title}</div>
                    {v.description && (
                      <div className="text-muted small text-truncate" style={{ maxWidth: 320 }}>
                        {v.description}
                      </div>
                    )}
                  </td>
                  <td className="small">{TYPE_LABELS[v.type] || v.type}</td>
                  <td className="small">
                    {v.source === 'youtube' ? (
                      <span className="badge bg-danger-subtle text-danger-emphasis">
                        <i className="bi bi-youtube me-1"></i> YouTube
                      </span>
                    ) : (
                      <span className="badge bg-primary-subtle text-primary-emphasis">
                        <i className="bi bi-cloud-upload me-1"></i> Upload
                      </span>
                    )}
                  </td>
                  <td>
                    {v.status === 'published' ? (
                      <span className="badge bg-success-subtle text-success-emphasis">Published</span>
                    ) : (
                      <span className="badge bg-secondary-subtle text-secondary-emphasis">Draft</span>
                    )}
                  </td>
                  <td>
                    {v.is_featured ? (
                      <i className="bi bi-star-fill text-warning"></i>
                    ) : (
                      <i className="bi bi-star text-muted"></i>
                    )}
                  </td>
                  <td className="text-end">
                    {v.source === 'youtube' && v.youtube_id && (
                      <a
                        href={`https://www.youtube.com/watch?v=${v.youtube_id}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn btn-sm btn-outline-secondary me-1"
                        title="Watch on YouTube"
                      >
                        <i className="bi bi-play-fill"></i>
                      </a>
                    )}

                    <Link
                      to={editLink(v.id)}
                      className="btn btn-sm btn-outline-secondary me-1"
                      title="Edit"
                    >
                      <i className="bi bi-pencil"></i>
                    </Link>

                    <button
                      type="button"
                      className="btn btn-sm btn-outline-danger"
                      title="Delete"
                      onClick={() => setDeleteTarget(v)}
                    >
                      <i className="bi bi-trash"></i>
                    </button>
                  </td>
                </tr>
              ))}
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
              Loading videos…
            </div>
          </div>
        )}

        {!loading && error && <div className="alert alert-danger">{error}</div>}

        {!loading && !error && videos.length === 0 && (
          <div className="card border-0 shadow-sm">
            <div className="card-body text-center py-5 text-muted">
              No videos found.
              {(search || status) && <> Try clearing the filters.</>}
            </div>
          </div>
        )}

        {!loading && videos.map((v) => (
          <Link
            key={v.id}
            to={editLink(v.id)}
            className="kfm-mobile-card"
          >
            <div className="kfm-mobile-card__head">
              <code className="kfm-mobile-card__ref">
                {TYPE_LABELS[v.type] || v.type}
              </code>
              {v.status === 'published' ? (
                <span className="badge bg-success-subtle text-success-emphasis">Published</span>
              ) : (
                <span className="badge bg-secondary-subtle text-secondary-emphasis">Draft</span>
              )}
            </div>

            <div className="kfm-mobile-card__subject">{v.title}</div>

            <div className="kfm-mobile-card__meta">
              {v.source && (
                <div className="kfm-mobile-card__meta-row">
                  <i className={`bi ${v.source === 'youtube' ? 'bi-youtube' : 'bi-cloud-upload'}`}></i>
                  <span>{v.source === 'youtube' ? 'YouTube' : 'Uploaded'}</span>
                </div>
              )}
              {v.is_featured && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-star-fill"></i>
                  <span>Featured</span>
                </div>
              )}
              {v.description && (
                <div className="kfm-mobile-card__meta-row">
                  <i className="bi bi-card-text"></i>
                  <span className="text-truncate">{v.description}</span>
                </div>
              )}
            </div>
          </Link>
        ))}

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
        title="Delete video?"
        message={deleteTarget ? `"${deleteTarget.title}" will be permanently deleted.` : ''}
        loading={deleting}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </>
  );
}