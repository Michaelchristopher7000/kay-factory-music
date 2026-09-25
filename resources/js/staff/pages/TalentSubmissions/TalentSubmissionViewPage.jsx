import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../../api';
import { useAuth } from '../../auth';

const STATUS_OPTIONS = [
  { value: 'pending', label: 'Pending' },
  { value: 'reviewing', label: 'Reviewing' },
  { value: 'shortlisted', label: 'Shortlisted' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'rejected', label: 'Rejected' },
];

const formatDateTime = (d) => {
  if (!d) return '—';
  try {
    return new Date(d).toLocaleString();
  } catch {
    return '—';
  }
};

export default function TalentSubmissionViewPage() {
  const { id } = useParams();
  const { user } = useAuth();

  const [submission, setSubmission] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [saveError, setSaveError] = useState('');
  const [saveSuccess, setSaveSuccess] = useState('');

  // Editable fields
  const [status, setStatus] = useState('');
  const [managerNotes, setManagerNotes] = useState('');
  const [audioUrl, setAudioUrl] = useState(null);

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const { data } = await api.get(`/talent-submissions/${id}`);
      const s = data.data;
      setSubmission(s);
      setStatus(s.status);
      setManagerNotes(s.manager_notes || '');
    } catch (err) {
      if (err.response?.status === 401) return;
      if (err.response?.status === 403) {
        setError('You do not have access to this submission.');
      } else if (err.response?.status === 404) {
        setError('Submission not found.');
      } else {
        setError('Could not load submission.');
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { load(); /* eslint-disable-next-line */ }, [id]);

  // Fetch audio as blob URL so we can render private files
  useEffect(() => {
    if (!submission) return;

    let audioObjectUrl;

    (async () => {
      if (submission.has_audio) {
        try {
          const res = await api.get(
            `/talent-submissions/${submission.id}/media/audio`,
            { responseType: 'blob' }
          );
          audioObjectUrl = URL.createObjectURL(res.data);
          setAudioUrl(audioObjectUrl);
        } catch {
          // silent
        }
      }
    })();

    return () => {
      if (audioObjectUrl) URL.revokeObjectURL(audioObjectUrl);
    };
  }, [submission]);

  const save = async () => {
    setSaving(true);
    setSaveError('');
    setSaveSuccess('');

    const payload = {
      status,
      manager_notes: managerNotes || null,
    };

    try {
      const { data } = await api.patch(`/talent-submissions/${id}`, payload);
      setSubmission(data.data);
      setSaveSuccess('Saved.');
      setTimeout(() => setSaveSuccess(''), 2500);
    } catch (err) {
      if (err.response?.status === 422) {
        const errors = err.response.data.errors || {};
        setSaveError(Object.values(errors)[0]?.[0] || 'Validation failed.');
      } else if (err.response?.status === 403) {
        setSaveError('You do not have permission to update this submission.');
      } else {
        setSaveError('Could not save. Please try again.');
      }
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="text-center py-5 text-muted">
        <div className="spinner-border spinner-border-sm me-2"></div>
        Loading…
      </div>
    );
  }

  if (error || !submission) {
    return (
      <>
        <div className="mb-4">
          <Link to="/talent-submissions" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to submissions
          </Link>
        </div>
        <div className="alert alert-danger">{error || 'Submission not found.'}</div>
      </>
    );
  }

  const socials = submission.social_links || {};
  const socialEntries = Object.entries(socials).filter(([, v]) => !!v);

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <Link to="/talent-submissions" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to submissions
          </Link>
          <h4 className="mb-0 mt-2">
            <code className="text-dark me-2">{submission.reference_number}</code>
            {submission.full_name}
          </h4>
        </div>

        <div className="d-flex gap-2 align-items-center">
          {saveSuccess && (
            <span className="text-success small">
              <i className="bi bi-check-circle me-1"></i>
              {saveSuccess}
            </span>
          )}
          <button
            type="button"
            className="btn btn-dark"
            onClick={save}
            disabled={saving}
          >
            {saving ? 'Saving…' : 'Save changes'}
          </button>
        </div>
      </div>

      {saveError && <div className="alert alert-danger">{saveError}</div>}

      <div className="row g-3">
        {/* LEFT COLUMN — Applicant details */}
        <div className="col-lg-7">
          <div className="card border-0 shadow-sm mb-3">
            <div className="card-header bg-white"><strong>Applicant</strong></div>
            <div className="card-body">
              <div className="row g-3">
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Full name</div>
                  <div className="fw-semibold">{submission.full_name}</div>
                </div>
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Category</div>
                  <div className="fw-semibold">{submission.talent_category}</div>
                </div>
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Email</div>
                  <div>
                    <a href={`mailto:${submission.email}`}>{submission.email}</a>
                  </div>
                </div>
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Phone</div>
                  <div>
                    <a href={`tel:${submission.phone}`}>{submission.phone}</a>
                  </div>
                </div>
                {submission.location && (
                  <div className="col-md-6">
                    <div className="text-muted small text-uppercase">Location</div>
                    <div>{submission.location}</div>
                  </div>
                )}
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Submitted</div>
                  <div>{formatDateTime(submission.created_at)}</div>
                </div>
              </div>
            </div>
          </div>

          {submission.bio && (
            <div className="card border-0 shadow-sm mb-3">
              <div className="card-header bg-white"><strong>Bio</strong></div>
              <div className="card-body" style={{ whiteSpace: 'pre-wrap' }}>
                {submission.bio}
              </div>
            </div>
          )}

          {submission.message && (
            <div className="card border-0 shadow-sm mb-3">
              <div className="card-header bg-white"><strong>Message</strong></div>
              <div className="card-body" style={{ whiteSpace: 'pre-wrap' }}>
                {submission.message}
              </div>
            </div>
          )}

          {socialEntries.length > 0 && (
            <div className="card border-0 shadow-sm mb-3">
              <div className="card-header bg-white"><strong>Social Links</strong></div>
              <div className="card-body">
                <div className="d-flex flex-wrap gap-2">
                  {socialEntries.map(([key, url]) => (
                    <a
                      key={key}
                      href={url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="btn btn-sm btn-outline-secondary"
                    >
                      <i className="bi bi-link-45deg me-1"></i>
                      {key}
                    </a>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Media */}
          {(submission.has_audio || submission.has_video || submission.has_image) && (
            <div className="card border-0 shadow-sm mb-3">
              <div className="card-header bg-white"><strong>Media</strong></div>
              <div className="card-body">
                {submission.has_audio && (
                  <div className="mb-4">
                    <div className="text-muted small text-uppercase mb-2">Audio</div>
                    {audioUrl ? (
                      <audio controls src={audioUrl} className="w-100" />
                    ) : (
                      <div className="text-muted small">Loading audio…</div>
                    )}
                  </div>
                )}

                {submission.has_video && (
                  <div className="mb-4">
                    <div className="text-muted small text-uppercase mb-2">Video</div>
                    <VideoFromApi
                      url={`/talent-submissions/${submission.id}/media/video`}
                    />
                  </div>
                )}

                {submission.has_image && (
                  <div>
                    <div className="text-muted small text-uppercase mb-2">Image</div>
                    <ImageFromApi
                      url={`/talent-submissions/${submission.id}/media/image`}
                    />
                  </div>
                )}
              </div>
            </div>
          )}
        </div>

        {/* RIGHT COLUMN — Status & notes */}
        <div className="col-lg-5">
          <div className="card border-0 shadow-sm mb-3">
            <div className="card-header bg-white"><strong>Review</strong></div>
            <div className="card-body">
              <div className="mb-3">
                <label className="form-label">Status</label>
                <select
                  className="form-select"
                  value={status}
                  onChange={(e) => setStatus(e.target.value)}
                >
                  {STATUS_OPTIONS.map((o) => (
                    <option key={o.value} value={o.value}>{o.label}</option>
                  ))}
                </select>
              </div>

              <div className="mb-2">
                <label className="form-label">Manager Notes</label>
                <textarea
                  rows={8}
                  className="form-control"
                  value={managerNotes}
                  onChange={(e) => setManagerNotes(e.target.value)}
                  placeholder="Internal notes (not visible to the applicant)"
                />
                <div className="form-text">Internal only — never shown to the applicant.</div>
              </div>
            </div>
          </div>

          <div className="card border-0 shadow-sm">
            <div className="card-header bg-white"><strong>Metadata</strong></div>
            <div className="card-body">
              <div className="text-muted small text-uppercase">Reviewed by</div>
              <div className="mb-3">
                {submission.reviewed_by
                  ? `${submission.reviewed_by.name} (${submission.reviewed_by.email})`
                  : <span className="text-muted">Not yet reviewed</span>}
              </div>

              <div className="text-muted small text-uppercase">Reviewed at</div>
              <div>{formatDateTime(submission.reviewed_at)}</div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

/**
 * Small helper that fetches an authenticated image and renders it.
 * Because <img src> can't send Authorization headers, we fetch as a blob.
 */
function ImageFromApi({ url }) {
  const [blobUrl, setBlobUrl] = useState(null);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    let objectUrl;
    let alive = true;

    (async () => {
      try {
        const res = await api.get(url, { responseType: 'blob' });
        if (!alive) return;
        objectUrl = URL.createObjectURL(res.data);
        setBlobUrl(objectUrl);
      } catch {
        if (alive) setFailed(true);
      }
    })();

    return () => {
      alive = false;
      if (objectUrl) URL.revokeObjectURL(objectUrl);
    };
  }, [url]);

  if (failed) {
    return <div className="text-muted small">Image failed to load.</div>;
  }
  if (!blobUrl) {
    return <div className="text-muted small">Loading image…</div>;
  }
  return (
    <img
      src={blobUrl}
      alt="Submission"
      style={{ maxWidth: '100%', maxHeight: 400, borderRadius: 8 }}
    />
  );
}

/**
 * Small helper that fetches a private video as a blob so we can attach the
 * Bearer token via the axios interceptor, then play it with an object URL.
 * Native <video src> cannot send Authorization headers — this solves that.
 */
function VideoFromApi({ url }) {
  const [blobUrl, setBlobUrl] = useState(null);
  const [failed, setFailed] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let objectUrl;
    let alive = true;

    (async () => {
      setLoading(true);
      setFailed(false);

      try {
        const res = await api.get(url, { responseType: 'blob' });
        if (!alive) return;
        objectUrl = URL.createObjectURL(res.data);
        setBlobUrl(objectUrl);
      } catch {
        if (alive) setFailed(true);
      } finally {
        if (alive) setLoading(false);
      }
    })();

    return () => {
      alive = false;
      if (objectUrl) URL.revokeObjectURL(objectUrl);
    };
  }, [url]);

  if (loading) {
    return (
      <div
        className="d-flex align-items-center justify-content-center text-muted small"
        style={{
          minHeight: 200,
          background: '#000',
          borderRadius: 8,
          color: '#fff',
        }}
      >
        <div className="spinner-border spinner-border-sm me-2"></div>
        Loading video…
      </div>
    );
  }

  if (failed || !blobUrl) {
    return (
      <div className="text-muted small">
        Video could not be loaded.
      </div>
    );
  }

  return (
    <video
      controls
      className="w-100"
      style={{ maxHeight: 420, borderRadius: 8, background: '#000' }}
      src={blobUrl}
    />
  );
}