import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { ReportHeader, ReportShell, MetaFooter, fmtMoney } from './_shared';

const CURRENCIES = ['', 'NGN', 'USD', 'ZAR', 'GBP', 'EUR'];

export default function RoyaltyBalancesPage() {
  const [rows, setRows] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [currency, setCurrency] = useState('');

  const fetchReport = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const { data } = await api.get('/reports/royalties/balances', {
        params: { currency: currency || undefined },
      });
      setRows(data.data || []);
      setMeta(data.meta || null);
    } catch (err) {
      if (err.response?.status === 401) return;
      if (err.response?.status === 403) {
        setError('You do not have permission to view this report.');
      } else {
        setError('Could not load report.');
      }
    } finally {
      setLoading(false);
    }
  }, [currency]);

  useEffect(() => {
    const t = setTimeout(fetchReport, 200);
    return () => clearTimeout(t);
  }, [fetchReport]);

  const totalOutstanding = rows.reduce(
    (sum, r) => sum + Number(r.outstanding_balance || 0),
    0
  );

  return (
    <>
      <div className="mb-3">
        <ReportHeader
          title="Royalty Balances"
          subtitle="Outstanding royalty per artist. Voided statements excluded."
        >
          <select
            className="form-select form-select-sm"
            value={currency}
            onChange={(e) => setCurrency(e.target.value)}
            style={{ width: 160 }}
          >
            {CURRENCIES.map((c) => (
              <option key={c} value={c}>{c || 'All currencies'}</option>
            ))}
          </select>
          <button
            type="button"
            className="btn btn-sm btn-outline-secondary"
            onClick={fetchReport}
          >
            <i className="bi bi-arrow-clockwise"></i>
          </button>
        </ReportHeader>
      </div>

      <ReportShell loading={loading} error={error} empty={rows.length === 0}>
        <div className="row g-3 mb-3">
          <div className="col-md-4">
            <div className="card border-0 shadow-sm">
              <div className="card-body">
                <div className="text-muted small">Total Outstanding</div>
                <div className="fw-semibold fs-5 text-danger">
                  {fmtMoney(totalOutstanding)}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="card border-0 shadow-sm">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead className="table-light">
                <tr>
                  <th>Artist</th>
                  <th style={{ width: 120 }}>Currency</th>
                  <th style={{ width: 180 }} className="text-end">Total Royalty</th>
                  <th style={{ width: 180 }} className="text-end">Total Paid</th>
                  <th style={{ width: 200 }} className="text-end">Outstanding Balance</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r, i) => (
                  <tr key={`${r.artist_id}-${r.currency}-${i}`}>
                    <td data-label="Artist">
                      <Link
                        to={`/artists/${r.artist_id}`}
                        className="text-decoration-none fw-semibold text-dark"
                      >
                        {r.artist_name}
                      </Link>
                    </td>
                    <td data-label="Currency">{r.currency}</td>
                    <td data-label="Total Royalty" className="text-end small">{fmtMoney(r.total_royalty, r.currency)}</td>
                    <td data-label="Total Paid" className="text-end small">{fmtMoney(r.total_paid, r.currency)}</td>
                    <td data-label="Outstanding Balance" className="text-end fw-semibold">
                      <span
                        className={
                          Number(r.outstanding_balance) > 0 ? 'text-danger' : 'text-success'
                        }
                      >
                        {fmtMoney(r.outstanding_balance, r.currency)}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        <MetaFooter meta={meta} />
      </ReportShell>
    </>
  );
}