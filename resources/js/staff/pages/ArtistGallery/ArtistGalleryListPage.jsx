import { useEffect, useState, useCallback } from 'react';
import { Link, useParams } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';
import ConfirmDeleteModal from '../Artists/ConfirmDeleteModal';

export default function ArtistGalleryListPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';
  const { artistId } = useParams();

  const [images, setImages] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 30 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);

  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchImages = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const { data } = await api.get('/artist-gallery', {
        params: {
          status: status || undefined,
          artist_id: artistId || undefined,
          per_page: 30,
          page,
        },
      });

      setImages(data.data || []);
      setMeta(data.meta || {});
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load gallery. Please try again.');
    } finally {
      setLoading(false);
    }
  }, [status, artistId, page]);

  useEffect(() => { fetchImages(); }, [fetchImages]);
  useEffect(() => { setPage(1); }, [status]);

  const confirmDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);

    try {
      await api.delete(`/artist-gallery/${deleteTarget.id}`);
      setDeleteTarget(null);
      fetchImages();
    } catch (err) {
      alert('Could not delete image. ' + (err.response?.data?.message || ''));
    } finally {
      setDeleting(false);
    }
  };

  const newLink = artistId
    ? `/artist-gallery/new?artist_id=${artistId}`
    : '/artist-gallery/new';

  const editLink = (id) =>
    artistId ? `/artist-gallery/${id}/edit?artist_id=${artistId}` : `/artist-gallery/${id}/edit`;

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <h4 className="mb-1">Artist Gallery</h4>
          <p className="text-muted mb-0 small">
            Manage artist photos — {meta.total || 0} total
          </p>
        </div>

        <Link to={newLink} className="btn btn-dark">
          <i className="bi bi-plus-lg me-2"></i>
          Upload Image
        </Link>
      </div>

      <div className="card border-0 shadow-sm mb-3">
        <div className="card-body">
          <div className="row g-2">
            <div className="col-12 col-md-8">
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
                onClick={fetchImages}
              >
                <i className="bi bi-arrow-clockwise me-1"></i> Refresh
              </button>
            </div>
          </div>
        </div>
      </div>

      {loading && (
        <div className="card border-0 shadow-sm">
          <div className="card-body text-center py-5 text-muted">
            <div className="spinner-border spinner-border-sm me-2"></div>
            Loading gallery…
          </div>
        </div>
      )}

      {!loading && error && (
        <div className="alert alert-danger">{error}</div>
      )}

      {!loading && !error && images.length === 0 && (
        <div className="card border-0 shadow-sm">
          <div className="card-body text-center py-5 text-muted">
            No gallery images yet.
            {status && <> Try clearing the filter.</>}
          </div>
        </div>
      )}

      {!loading && !error && images.length > 0 && (
        <>
          <div className="row g-3">
            {images.map((img) => (
              <div key={img.id} className="col-6 col-md-4 col-lg-3">
                <div className="card border-0 shadow-sm h-100">
                  <div
                    className="position-relative"
                    style={{ aspectRatio: '1 / 1', overflow: 'hidden' }}
                  >
                    <img
                      src={img.image_url}
                      alt={img.alt_text || ''}
                      style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                    />

                    <div
                      className="position-absolute top-0 end-0 m-2 d-flex gap-1"
                      style={{ zIndex: 2 }}
                    >
                      {img.is_featured && (
                        <span className="badge bg-warning text-dark" title="Featured">
                          <i className="bi bi-star-fill"></i>
                        </span>
                      )}
                      {img.status === 'published' ? (
                        <span className="badge bg-success" title="Published">Live</span>
                      ) : (
                        <span className="badge bg-secondary" title="Draft">Draft</span>
                      )}
                    </div>
                  </div>

                  <div className="card-body p-3">
                    {img.caption ? (
                      <div className="small text-truncate" title={img.caption}>
                        {img.caption}
                      </div>
                    ) : (
                      <div className="small text-muted fst-italic">No caption</div>
                    )}
                  </div>

                  <div className="card-footer bg-white d-flex justify-content-between align-items-center">
                    <span className="text-muted small">#{img.sort_order}</span>
                    <div>
                      <Link
                        to={editLink(img.id)}
                        className="btn btn-sm btn-outline-secondary me-1"
                        title="Edit"
                      >
                        <i className="bi bi-pencil"></i>
                      </Link>
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger"
                        title="Delete"
                        onClick={() => setDeleteTarget(img)}
                      >
                        <i className="bi bi-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {meta.last_page > 1 && (
            <div className="d-flex justify-content-between align-items-center mt-4">
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
        </>
      )}

      <ConfirmDeleteModal
        show={!!deleteTarget}
        title="Delete gallery image?"
        message={
          deleteTarget
            ? `This image${deleteTarget.caption ? ` ("${deleteTarget.caption}")` : ''} will be permanently deleted.`
            : ''
        }
        loading={deleting}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </>
  );
}