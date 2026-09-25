import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../../api';

const STATUS_OPTIONS = [
  { value: 'unread', label: 'Unread' },
  { value: 'read', label: 'Read' },
  { value: 'replied', label: 'Replied' },
  { value: 'resolved', label: 'Resolved' },
];

const formatDateTime = (d) => {
  if (!d) return '—';
  try {
    return new Date(d).toLocaleString();
  } catch {
    return '—';
  }
};

export default function ContactMessageViewPage() {
  const { id } = useParams();

  const [message, setMessage] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [saveError, setSaveError] = useState('');
  const [saveSuccess, setSaveSuccess] = useState('');

  const [status, setStatus] = useState('');
  const [internalNotes, setInternalNotes] = useState('');

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const { data } = await api.get(`/contact-messages/${id}`);
      const m = data.data;
      setMessage(m);
      setStatus(m.status);
      setInternalNotes(m.internal_notes || '');
    } catch (err) {
      if (err.response?.status === 401) return;
      if (err.response?.status === 403) {
        setError('You do not have access to this message.');
      } else if (err.response?.status === 404) {
        setError('Message not found.');
      } else {
        setError('Could not load message.');
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { load(); /* eslint-disable-next-line */ }, [id]);

  const save = async () => {
    setSaving(true);
    setSaveError('');
    setSaveSuccess('');

    try {
      const { data } = await api.patch(`/contact-messages/${id}`, {
        status,
        internal_notes: internalNotes || null,
      });
      setMessage(data.data);
      setSaveSuccess('Saved.');
      setTimeout(() => setSaveSuccess(''), 2500);
    } catch (err) {
      if (err.response?.status === 422) {
        const errors = err.response.data.errors || {};
        setSaveError(Object.values(errors)[0]?.[0] || 'Validation failed.');
      } else if (err.response?.status === 403) {
        setSaveError('You do not have permission to update this message.');
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

  if (error || !message) {
    return (
      <>
        <div className="mb-4">
          <Link to="/contact-messages" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to messages
          </Link>
        </div>
        <div className="alert alert-danger">{error || 'Message not found.'}</div>
      </>
    );
  }

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <div>
          <Link to="/contact-messages" className="text-muted text-decoration-none small">
            <i className="bi bi-arrow-left me-1"></i> Back to messages
          </Link>
          <h4 className="mb-0 mt-2">
            <code className="text-dark me-2">{message.reference_number}</code>
            {message.subject}
          </h4>
        </div>

        <div className="d-flex gap-2 align-items-center">
          {saveSuccess && (
            <span className="text-success small">
              <i className="bi bi-check-circle me-1"></i> {saveSuccess}
            </span>
          )}
          <button type="button" className="btn btn-dark" onClick={save} disabled={saving}>
            {saving ? 'Saving…' : 'Save changes'}
          </button>
        </div>
      </div>

      {saveError && <div className="alert alert-danger">{saveError}</div>}

      <div className="row g-3">
        <div className="col-lg-7">
          <div className="card border-0 shadow-sm mb-3">
            <div className="card-header bg-white"><strong>Sender</strong></div>
            <div className="card-body">
              <div className="row g-3">
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Name</div>
                  <div className="fw-semibold">{message.name}</div>
                </div>
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Category</div>
                  <div className="fw-semibold">{message.category_label}</div>
                </div>
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Email</div>
                  <div><a href={`mailto:${message.email}`}>{message.email}</a></div>
                </div>
                {message.phone && (
                  <div className="col-md-6">
                    <div className="text-muted small text-uppercase">Phone</div>
                    <div><a href={`tel:${message.phone}`}>{message.phone}</a></div>
                  </div>
                )}
                <div className="col-md-6">
                  <div className="text-muted small text-uppercase">Received</div>
                  <div>{formatDateTime(message.created_at)}</div>
                </div>
              </div>
            </div>
          </div>

          <div className="card border-0 shadow-sm mb-3">
            <div className="card-header bg-white"><strong>Message</strong></div>
            <div className="card-body" style={{ whiteSpace: 'pre-wrap' }}>
              {message.message}
            </div>
          </div>

          <div className="card border-0 shadow-sm">
            <div className="card-header bg-white"><strong>Reply</strong></div>
            <div className="card-body">
              <a
                href={`mailto:${message.email}?subject=${encodeURIComponent('Re: ' + message.subject)}`}
                className="btn btn-dark"
              >
                <i className="bi bi-envelope me-1"></i> Reply by email
              </a>
              <p className="text-muted small mb-0 mt-3">
                Opens your default email client with the recipient and subject prefilled.
              </p>
            </div>
          </div>
        </div>

        <div className="col-lg-5">
          <div className="card border-0 shadow-sm mb-3">
            <div className="card-header bg-white"><strong>Status</strong></div>
            <div className="card-body">
              <select
                className="form-select mb-3"
                value={status}
                onChange={(e) => setStatus(e.target.value)}
              >
                {STATUS_OPTIONS.map((o) => (
                  <option key={o.value} value={o.value}>{o.label}</option>
                ))}
              </select>

              <label className="form-label">Internal Notes</label>
              <textarea
                rows={6}
                className="form-control"
                value={internalNotes}
                onChange={(e) => setInternalNotes(e.target.value)}
                placeholder="Notes are internal only — never visible to the sender."
              />
            </div>
          </div>

          <div className="card border-0 shadow-sm">
            <div className="card-header bg-white"><strong>Timeline</strong></div>
            <div className="card-body">
              <div className="text-muted small text-uppercase">Read at</div>
              <div className="mb-3">{formatDateTime(message.read_at)}</div>

              <div className="text-muted small text-uppercase">Replied at</div>
              <div className="mb-3">{formatDateTime(message.replied_at)}</div>

              <div className="text-muted small text-uppercase">Resolved at</div>
              <div className="mb-3">{formatDateTime(message.resolved_at)}</div>

              <div className="text-muted small text-uppercase">Assigned to</div>
              <div>
                {message.assigned_to
                  ? `${message.assigned_to.name} (${message.assigned_to.email})`
                  : <span className="text-muted">Unassigned</span>}
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}