import { Link } from 'react-router-dom';
import { useAuth } from '../../auth';
import { canViewFinancialReports } from '../../permissions';

export default function ReportsHubPage() {
  const { user } = useAuth();
  const roleSlug = user?.role?.slug || '';
  const finance = canViewFinancialReports(roleSlug);

  const reports = [
    {
      to: '/reports/artists-summary',
      title: 'Artist Summary',
      desc: 'Per-artist totals — tracks, releases, revenue, expenses, royalty generated and paid.',
      icon: 'bi-people',
      tone: '#E4B84C',
      finance: true,
    },
    {
      to: '/reports/releases-performance',
      title: 'Release Performance',
      desc: 'Revenue vs. expenses for each release, with net amount.',
      icon: 'bi-vinyl',
      tone: '#60A5FA',
      finance: false,
    },
    {
      to: '/reports/distributions-status',
      title: 'Distribution Status',
      desc: 'Delivery counts per platform — pending, submitted, live, takedown, rejected, failed.',
      icon: 'bi-broadcast',
      tone: '#4ADE80',
      finance: false,
    },
    {
      to: '/reports/revenue-by-source',
      title: 'Revenue Breakdown',
      desc: 'Revenue grouped by source and currency.',
      icon: 'bi-graph-up-arrow',
      tone: '#4ADE80',
      finance: true,
    },
    {
      to: '/reports/expenses-by-category',
      title: 'Expense Breakdown',
      desc: 'Expenses grouped by category and currency.',
      icon: 'bi-cash-stack',
      tone: '#F87171',
      finance: true,
    },
    {
      to: '/reports/royalties-balances',
      title: 'Royalty Balances',
      desc: 'Outstanding royalty per artist, by currency.',
      icon: 'bi-cash-coin',
      tone: '#A78BFA',
      finance: true,
    },
  ];

  const visible = reports.filter((r) => !r.finance || finance);

  return (
    <>
      <div className="mb-4">
        <h4 className="mb-1">Reports</h4>
        <p className="text-muted mb-0 small">
          Read-only analytics across the label.
        </p>
      </div>

      <div className="row g-3">
        {visible.map((r) => (
          <div className="col-md-6 col-lg-4" key={r.to}>
            <Link to={r.to} className="text-decoration-none">
              <div className="card border-0 shadow-sm h-100">
                <div className="card-body d-flex align-items-start gap-3">
                  <div
                    className="d-flex align-items-center justify-content-center rounded flex-shrink-0"
                    style={{
                      width: 48,
                      height: 48,
                      background: `${r.tone}1A`,
                      color: r.tone,
                      fontSize: '1.35rem',
                    }}
                  >
                    <i className={`bi ${r.icon}`}></i>
                  </div>
                  <div>
                    <h6 className="mb-1 text-dark">{r.title}</h6>
                    <p className="text-muted mb-0 small">{r.desc}</p>
                  </div>
                </div>
              </div>
            </Link>
          </div>
        ))}
      </div>

      {!finance && (
        <div className="alert alert-warning mt-4 small">
          <i className="bi bi-info-circle me-2"></i>
          Financial reports are hidden because your role doesn't have access.
        </div>
      )}
    </>
  );
}