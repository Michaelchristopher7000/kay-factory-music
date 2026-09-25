import { Link } from 'react-router-dom';

export default function RoyaltiesHubPage() {
  return (
    <>
      <div className="mb-4">
        <h4 className="mb-1">Royalties</h4>
        <p className="text-muted mb-0 small">
          Statements, payouts, and artist balances.
        </p>
      </div>

      <div className="row g-3">
        <div className="col-md-6">
          <Link to="/royalty-statements" className="text-decoration-none">
            <div className="card border-0 shadow-sm h-100">
              <div className="card-body d-flex align-items-center gap-3">
                <div
                  className="d-flex align-items-center justify-content-center rounded"
                  style={{ width: 56, height: 56, background: 'rgba(228, 184, 76, 0.12)', color: '#E4B84C', fontSize: '1.5rem' }}
                >
                  <i className="bi bi-file-earmark-ruled"></i>
                </div>
                <div>
                  <h5 className="mb-1 text-dark">Royalty Statements</h5>
                  <p className="text-muted mb-0 small">
                    Period statements per artist, auto-generated from revenue entries.
                  </p>
                </div>
              </div>
            </div>
          </Link>
        </div>

        <div className="col-md-6">
          <Link to="/royalty-payments" className="text-decoration-none">
            <div className="card border-0 shadow-sm h-100">
              <div className="card-body d-flex align-items-center gap-3">
                <div
                  className="d-flex align-items-center justify-content-center rounded"
                  style={{ width: 56, height: 56, background: 'rgba(74, 222, 128, 0.12)', color: '#4ADE80', fontSize: '1.5rem' }}
                >
                  <i className="bi bi-cash-coin"></i>
                </div>
                <div>
                  <h5 className="mb-1 text-dark">Royalty Payments</h5>
                  <p className="text-muted mb-0 small">
                    Cash paid out to artists against issued statements.
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