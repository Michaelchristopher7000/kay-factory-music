/* Shared helpers for report pages */

export const fmtMoney = (v, c) =>
  `${c || 'NGN'} ${Number(v || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;

export const fmtNumber = (v) => Number(v || 0).toLocaleString();

export function ReportHeader({ title, subtitle, children }) {
  return (
    <div className="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
      <div>
        <h4 className="mb-1">{title}</h4>
        {subtitle && <p className="text-muted mb-0 small">{subtitle}</p>}
      </div>
      {children && <div className="d-flex gap-2 flex-wrap">{children}</div>}
    </div>
  );
}

export function ReportShell({ loading, error, empty, children }) {
  if (loading) {
    return (
      <div className="text-center py-5 text-muted">
        <div className="spinner-border spinner-border-sm me-2"></div>
        Loading report…
      </div>
    );
  }

  if (error) {
    return <div className="alert alert-danger">{error}</div>;
  }

  if (empty) {
    return (
      <div className="card border-0 shadow-sm">
        <div className="card-body text-center text-muted py-5">
          No data matches the current filters.
        </div>
      </div>
    );
  }

  return children;
}

export function MetaFooter({ meta }) {
  if (!meta) return null;

  const filters = meta.filters || {};
  const activeFilters = Object.entries(filters).filter(
    ([, v]) => v !== null && v !== undefined && v !== ''
  );

  return (
    <div className="text-muted small mt-3">
      <div>
        Generated at{' '}
        {meta.generated_at ? new Date(meta.generated_at).toLocaleString() : '—'}
      </div>
      {activeFilters.length > 0 && (
        <div className="mt-1">
          Filters:{' '}
          {activeFilters.map(([k, v], i) => (
            <span key={k}>
              <code>{k}={String(v)}</code>
              {i < activeFilters.length - 1 ? ', ' : ''}
            </span>
          ))}
        </div>
      )}
    </div>
  );
}