import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import { ReportHeader, ReportShell, MetaFooter, fmtMoney, fmtNumber } from './_shared';

export default function ReleasePerformancePage() {
  const [rows, setRows] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');

  const fetchReport = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const { data } = await api.get('/reports/releases/performance', {
        params: { from: from || undefined, to: to || undefined },
      });
      setRows(data.data || []);
      setMeta(data.meta || null);
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load report.');
    } finally {
      setLoading(false);
    }
  }, [from, to]);

  useEffect(() => {
    const t = setTimeout(fetchReport, 200);
    return () => clearTimeout(t);
  }, [fetchReport]);

  return (
    <>
      <Link to="/reports" className="text-muted text-decoration-none small">
        <i className="bi bi-arrow-left me-1"></i> Back to Reports
      </Link>

      <div className="mt-2">
        <ReportHeader
          title="Release Performance"
          subtitle="Revenue, expenses, and net per release. Filtered on release date."
        >
          <input
            type="date"
            className="form-control form-control-sm"
            value={from}
            onChange={(e) => setFrom(e.target.value)}
            placeholder="From"
            style={{ width: 160 }}
          />
          <input
            type="date"
            className="form-control form-control-sm"
            value={to}
            onChange={(e) => setTo(e.target.value)}
            placeholder="To"
            style={{ width: 160 }}
          />
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
        <div className="card border-0 shadow-sm">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead className="table-light">
                <tr>
                  <th>Release</th>
                  <th>Artist</th>
                  <th style={{ width: 100 }}>Type</th>
                  <th style={{ width: 120 }}>Released</th>
                  <th style={{ width: 90 }} className="text-end">Tracks</th>
                  <th style={{ width: 110 }} className="text-end">Dists.</th>
                  <th style={{ width: 150 }} className="text-end">Revenue</th>
                  <th style={{ width: 150 }} className="text-end">Expenses</th>
                  <th style={{ width: 150 }} className="text-end">Net</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r) => (
                  <tr key={r.release_id}>
                    <td data-label="Release">
                      <Link
                        to={`/releases/${r.release_id}`}
                        className="text-decoration-none fw-semibold text-dark"
                      >
                        {r.release_title}
                      </Link>
                    </td>
                    <td data-label="Artist" className="text-muted small">{r.artist?.name || '—'}</td>
                    <td data-label="Type">
                      <span className="badge bg-light text-dark border text-uppercase">
                        {r.type}
                      </span>
                    </td>
                    <td data-label="Released" className="text-muted small">{r.release_date || '—'}</td>
                    <td data-label="Tracks" className="text-end text-muted small">{fmtNumber(r.track_count)}</td>
                    <td data-label="Dists." className="text-end text-muted small">{fmtNumber(r.distribution_count)}</td>
                    <td data-label="Revenue" className="text-end small">{fmtMoney(r.revenue)}</td>
                    <td data-label="Expenses" className="text-end small">{fmtMoney(r.expenses)}</td>
                    <td data-label="Net" className="text-end fw-semibold">
                      <span
                        className={Number(r.net_amount) >= 0 ? 'text-success' : 'text-danger'}
                      >
                        {fmtMoney(r.net_amount)}
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