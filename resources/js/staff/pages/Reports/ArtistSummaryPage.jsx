import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../api';
import {
  ReportHeader,
  ReportShell,
  MetaFooter,
  fmtMoney,
  fmtNumber,
} from './_shared';

export default function ArtistSummaryPage() {
  const [rows, setRows] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [artistId, setArtistId] = useState('');
  const [artists, setArtists] = useState([]);

  useEffect(() => {
    (async () => {
      try {
        const { data } = await api.get('/artists', { params: { per_page: 100 } });
        setArtists(data.data || []);
      } catch {}
    })();
  }, []);

  const fetchReport = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const { data } = await api.get('/reports/artists/summary', {
        params: { artist_id: artistId || undefined },
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
  }, [artistId]);

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
          title="Artist Summary"
          subtitle="Per-artist totals across the entire label."
        >
          <select
            className="form-select form-select-sm"
            style={{ minWidth: 260 }}
            value={artistId}
            onChange={(e) => setArtistId(e.target.value)}
          >
            <option value="">All artists</option>
            {artists.map((a) => (
              <option key={a.id} value={a.id}>
                {a.artist_code} — {a.name}
              </option>
            ))}
          </select>
          <button
            type="button"
            className="btn btn-sm btn-outline-secondary"
            onClick={fetchReport}
            title="Refresh"
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
                  <th>Artist</th>
                  <th style={{ width: 90 }} className="text-end">Tracks</th>
                  <th style={{ width: 100 }} className="text-end">Releases</th>
                  <th style={{ width: 110 }} className="text-end">Contracts</th>
                  <th style={{ width: 160 }} className="text-end">Revenue</th>
                  <th style={{ width: 160 }} className="text-end">Expenses</th>
                  <th style={{ width: 160 }} className="text-end">Royalty Gen.</th>
                  <th style={{ width: 160 }} className="text-end">Royalty Paid</th>
                  <th style={{ width: 160 }} className="text-end">Balance</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r) => (
                  <tr key={r.artist_id}>
                    <td data-label="Artist">
                      <Link
                        to={`/artists/${r.artist_id}`}
                        className="text-decoration-none fw-semibold text-dark"
                      >
                        {r.artist_name}
                      </Link>
                    </td>
                    <td data-label="Tracks" className="text-end text-muted small">{fmtNumber(r.total_tracks)}</td>
                    <td data-label="Releases" className="text-end text-muted small">{fmtNumber(r.total_releases)}</td>
                    <td data-label="Contracts" className="text-end text-muted small">{fmtNumber(r.total_contracts)}</td>
                    <td data-label="Revenue" className="text-end small">{fmtMoney(r.total_revenue)}</td>
                    <td data-label="Expenses" className="text-end small">{fmtMoney(r.total_expenses)}</td>
                    <td data-label="Royalty Gen." className="text-end small">{fmtMoney(r.total_royalty_generated)}</td>
                    <td data-label="Royalty Paid" className="text-end small">{fmtMoney(r.total_royalty_paid)}</td>
                    <td data-label="Balance" className="text-end fw-semibold">
                      <span
                        className={
                          Number(r.outstanding_royalty_balance) > 0
                            ? 'text-danger'
                            : 'text-success'
                        }
                      >
                        {fmtMoney(r.outstanding_royalty_balance)}
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