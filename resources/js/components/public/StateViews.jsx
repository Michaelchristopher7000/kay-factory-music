export function LoadingState({ label = 'Loading…', variant = 'block' }) {
  if (variant === 'inline') {
    return (
      <div className="kfm-state kfm-state--inline">
        <span className="kfm-state__spinner" aria-hidden="true"></span>
        <span>{label}</span>
      </div>
    );
  }

  return (
    <div className="kfm-state kfm-state--block">
      <span className="kfm-state__spinner" aria-hidden="true"></span>
      <span>{label}</span>
    </div>
  );
}

export function ErrorState({ message = 'Unable to load data. Please try again.', onRetry }) {
  return (
    <div className="kfm-state kfm-state--error">
      <i className="bi bi-exclamation-triangle kfm-state__icon"></i>
      <p className="kfm-state__message">{message}</p>
      {onRetry && (
        <button type="button" className="kfm-btn kfm-btn--ghost" onClick={onRetry}>
          Try Again
        </button>
      )}
    </div>
  );
}

export function EmptyState({ message = 'Nothing here yet.', hint = null }) {
  return (
    <div className="kfm-state kfm-state--empty">
      <i className="bi bi-inbox kfm-state__icon"></i>
      <p className="kfm-state__message">{message}</p>
      {hint && <p className="kfm-state__hint">{hint}</p>}
    </div>
  );
}

export function SkeletonGrid({ columns = 4, rows = 1, aspect = '1 / 1' }) {
  const total = columns * rows;
  return (
    <div
      className="kfm-skeleton-grid"
      style={{ gridTemplateColumns: `repeat(${columns}, 1fr)` }}
    >
      {Array.from({ length: total }).map((_, i) => (
        <div key={i} className="kfm-skeleton kfm-skeleton--card">
          <div className="kfm-skeleton__image" style={{ aspectRatio: aspect }}></div>
          <div className="kfm-skeleton__line" style={{ width: '70%' }}></div>
          <div className="kfm-skeleton__line" style={{ width: '45%' }}></div>
        </div>
      ))}
    </div>
  );
}

export function SkeletonBlock({ height = 400 }) {
  return <div className="kfm-skeleton kfm-skeleton--block" style={{ height }}></div>;
}