const actionLabel = (a) => (a || '').replace(/_/g, ' ');

const actionBadge = (action) => {
  switch (action) {
    case 'created':  return 'badge bg-success-subtle text-success-emphasis';
    case 'updated':  return 'badge bg-primary-subtle text-primary-emphasis';
    case 'deleted':  return 'badge bg-danger-subtle text-danger-emphasis';
    case 'restored': return 'badge bg-warning-subtle text-warning-emphasis';
    default:         return 'badge bg-light text-dark';
  }
};

export default function AuditLogViewModal({ show, log, onClose }) {
  if (!show || !log) return null;

  const changes = log.changes || {};
  const hasBeforeAfter = changes.before || changes.after;
  const attributes = changes.attributes;

  return (
    <div
      className="modal d-block"
      tabIndex={-1}
      style={{ background: 'rgba(0,0,0,0.5)' }}
      onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}
    >
      <div className="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div className="modal-content border-0 shadow">
          <div className="modal-header">
            <div>
              <h5 className="modal-title mb-1">Audit Entry #{log.id}</h5>
              <div className="small text-muted">
                {log.model_label || `${log.model_type} #${log.model_id}`}
              </div>
            </div>
            <button type="button" className="btn-close" onClick={onClose}></button>
          </div>

          <div className="modal-body">
            <dl className="row mb-4">
              <dt className="col-sm-3 text-muted fw-normal">Action</dt>
              <dd className="col-sm-9">
                <span className={actionBadge(log.action)}>
                  {actionLabel(log.action)}
                </span>
              </dd>

              <dt className="col-sm-3 text-muted fw-normal">Entity</dt>
              <dd className="col-sm-9">
                {log.model_label || '—'}{' '}
                <span className="text-muted small">
                  ({log.model_type} · ID {log.model_id})
                </span>
              </dd>

              <dt className="col-sm-3 text-muted fw-normal">User</dt>
              <dd className="col-sm-9">
                {log.user_name || 'system'}
                {log.user_email && (
                  <span className="text-muted small"> · {log.user_email}</span>
                )}
              </dd>

              <dt className="col-sm-3 text-muted fw-normal">When</dt>
              <dd className="col-sm-9">
                {log.created_at ? new Date(log.created_at).toLocaleString() : '—'}
              </dd>

              {log.ip_address && (
                <>
                  <dt className="col-sm-3 text-muted fw-normal">IP Address</dt>
                  <dd className="col-sm-9">{log.ip_address}</dd>
                </>
              )}

              {log.user_agent && (
                <>
                  <dt className="col-sm-3 text-muted fw-normal">User Agent</dt>
                  <dd className="col-sm-9 text-muted small text-break">
                    {log.user_agent}
                  </dd>
                </>
              )}
            </dl>

            {hasBeforeAfter && (
              <>
                <h6 className="mb-3">Changes</h6>
                <div className="table-responsive border rounded">
                  <table className="table table-sm mb-0">
                    <thead className="table-light">
                      <tr>
                        <th style={{ width: 200 }}>Field</th>
                        <th>Before</th>
                        <th>After</th>
                      </tr>
                    </thead>
                    <tbody>
                      {Object.keys({ ...changes.before, ...changes.after }).map((key) => {
                        const before = changes.before?.[key];
                        const after = changes.after?.[key];
                        return (
                          <tr key={key}>
                            <td><code>{key}</code></td>
                            <td className="text-muted small">
                              {before === null || before === undefined
                                ? '—'
                                : typeof before === 'object'
                                ? JSON.stringify(before)
                                : String(before)}
                            </td>
                            <td className="small">
                              {after === null || after === undefined
                                ? '—'
                                : typeof after === 'object'
                                ? JSON.stringify(after)
                                : String(after)}
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </>
            )}

            {attributes && (
              <>
                <h6 className="mb-3 mt-4">
                  {log.action === 'created' ? 'Created With' : 'Last Known Values'}
                </h6>
                <div className="table-responsive border rounded">
                  <table className="table table-sm mb-0">
                    <thead className="table-light">
                      <tr>
                        <th style={{ width: 220 }}>Field</th>
                        <th>Value</th>
                      </tr>
                    </thead>
                    <tbody>
                      {Object.entries(attributes).map(([k, v]) => (
                        <tr key={k}>
                          <td><code>{k}</code></td>
                          <td className="small text-break">
                            {v === null || v === undefined
                              ? '—'
                              : typeof v === 'object'
                              ? JSON.stringify(v)
                              : String(v)}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </>
            )}

            {!hasBeforeAfter && !attributes && (
              <div className="text-muted text-center py-4">
                No change details recorded.
              </div>
            )}
          </div>

          <div className="modal-footer">
            <button type="button" className="btn btn-outline-secondary" onClick={onClose}>
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}