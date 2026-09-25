import { useEffect, useState, useCallback } from 'react';
import api from '../../api';
import { ReportHeader, ReportShell, MetaFooter, fmtNumber } from './_shared';

const PLATFORM_ICONS = {
  spotify: 'bi-spotify',
  apple_music: 'bi-apple',
  youtube_music: 'bi-youtube',
  amazon_music: 'bi-music-note',
  deezer: 'bi-music-note-list',
  tidal: 'bi-water',
  audiomack: 'bi-music-note-beamed',
  boomplay: 'bi-play-circle',
  soundcloud: 'bi-soundwave',
  pandora: 'bi-broadcast',
  other: 'bi-globe',
};

export default function DistributionStatusPage() {
  const [rows, setRows] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const fetchReport = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const { data } = await api.get('/reports/distributions/status');
      setRows(data.data || []);
      setMeta(data.meta || null);
    } catch (err) {
      if (err.response?.status === 401) return;
      setError('Could not load report.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchReport(); }, [fetchReport]);

  return (
    <>
      <div className="mb-3">
        <ReportHeader
          title="Distribution Status"
          subtitle="Delivery counts grouped by platform."
        >
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
                  <th>Platform</th>
                  <th style={{ width: 110 }} className="text-end">Total</th>
                  <th style={{ width: 100 }} className="text-end">Pending</th>
                  <th style={{ width: 110 }} className="text-end">Submitted</th>
                  <th style={{ width: 90 }} className="text-end">Live</th>
                  <th style={{ width: 110 }} className="text-end">Takedown</th>
                  <th style={{ width: 110 }} className="text-end">Rejected</th>
                  <th style={{ width: 90 }} className="text-end">Failed</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r) => (
                  <tr key={r.platform}>
                    <td data-label="Platform" className="text-capitalize">
                      <i className={`bi ${PLATFORM_ICONS[r.platform] || 'bi-globe'} me-2`}></i>
                      {(r.platform || '').replace(/_/g, ' ')}
                    </td>
                    <td data-label="Total" className="text-end fw-semibold">{fmtNumber(r.total_distributions)}</td>
                    <td data-label="Pending" className="text-end text-muted small">{fmtNumber(r.pending_count)}</td>
                    <td data-label="Submitted" className="text-end text-muted small">{fmtNumber(r.submitted_count)}</td>
                    <td data-label="Live" className="text-end">
                      <span className="badge bg-success-subtle text-success-emphasis">
                        {fmtNumber(r.live_count)}
                      </span>
                    </td>
                    <td data-label="Takedown" className="text-end text-muted small">{fmtNumber(r.takedown_count)}</td>
                    <td data-label="Rejected" className="text-end text-muted small">{fmtNumber(r.rejected_count)}</td>
                    <td data-label="Failed" className="text-end text-muted small">{fmtNumber(r.failed_count)}</td>
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