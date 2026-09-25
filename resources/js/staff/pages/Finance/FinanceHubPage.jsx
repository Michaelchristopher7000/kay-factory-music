import { Link } from 'react-router-dom';

export default function FinanceHubPage() {
  return (
    <>
      <div className="mb-4">
        <h4 className="mb-1">Finance</h4>
        <p className="text-muted mb-0 small">
          Track money in and money out.
        </p>
      </div>

      <div className="row g-3">
        <div className="col-md-6">
          <Link to="/revenue-entries" className="text-decoration-none">
            <div className="card border-0 shadow-sm h-100">
              <div className="card-body d-flex align-items-center gap-3">
                <div
                  className="d-flex align-items-center justify-content-center rounded"
                  style={{ width: 56, height: 56, background: 'rgba(74, 222, 128, 0.12)', color: '#4ADE80', fontSize: '1.5rem' }}
                >
                  <i className="bi bi-graph-up-arrow"></i>
                </div>
                <div>
                  <h5 className="mb-1 text-dark">Revenue Entries</h5>
                  <p className="text-muted mb-0 small">
                    Money received from streaming, sync, publishing, merch, and more.
                  </p>
                </div>
              </div>
            </div>
          </Link>
        </div>

        <div className="col-md-6">
          <Link to="/expenses" className="text-decoration-none">
            <div className="card border-0 shadow-sm h-100">
              <div className="card-body d-flex align-items-center gap-3">
                <div
                  className="d-flex align-items-center justify-content-center rounded"
                  style={{ width: 56, height: 56, background: 'rgba(248, 113, 113, 0.12)', color: '#F87171', fontSize: '1.5rem' }}
                >
                  <i className="bi bi-cash-stack"></i>
                </div>
                <div>
                  <h5 className="mb-1 text-dark">Expenses</h5>
                  <p className="text-muted mb-0 small">
                    Money spent on studio, marketing, video, advances, and operations.
                  </p>
                </div>
              </div>
            </div>
          </Link>
        </div>
      </div>
    </>
  );
}