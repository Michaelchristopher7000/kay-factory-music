import { useEffect, useState, useCallback } from 'react';
import api from '../../api';
import { ReportHeader, ReportShell, MetaFooter, fmtMoney, fmtNumber } from './_shared';

const CURRENCIES = ['', 'NGN', 'USD', 'ZAR', 'GBP', 'EUR'];

export default function ExpenseByCategoryPage() {
  const [rows, setRows] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [currency, setCurrency] = useState('');

  const fetchReport = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const { data } = await api.get('/reports/expenses/by-category', {
        params: {
          from: from || undefined,
          to: to || undefined,
          currency: currency || undefined,
        },
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
  }, [from, to, currency]);

  useEffect(() => {
    const t = setTimeout(fetchReport, 200);
    return () => clearTimeout(t);
  }, [fetchReport]);

  return (
    <>
      <div className="mb-3">
        <ReportHeader
          title="Expense Breakdown"
          subtitle="Expenses grouped by category and currency. Filtered on incurred date."
        >
          <input
            type="date"
            className="form-control form-control-sm"
            value={from}
            onChange={(e) => setFrom(e.target.value)}
            style={{ width: 160 }}
          />
          <input
            type="date"
            className="form-control form-control-sm"
            value={to}
            onChange={(e) => setTo(e.target.value)}
            style={{ width: 160 }}
          />
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
        <div className="card border-0 shadow-sm">
          <div className="table-responsive">
            <table className="table table-hover align-middle mb-0">
              <thead className="table-light">
                <tr>
                  <th>Category</th>
                  <th style={{ width: 120 }}>Currency</th>
                  <th style={{ width: 130 }} className="text-end">Entries</th>
                  <th style={{ width: 200 }} className="text-end">Total Amount</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((r, i) => (
                  <tr key={`${r.category}-${r.currency}-${i}`}>
                    <td data-label="Category" className="text-capitalize">{(r.category || '').replace(/_/g, ' ')}</td>
                    <td data-label="Currency">{r.currency}</td>
                    <td data-label="Entries" className="text-end text-muted small">{fmtNumber(r.entry_count)}</td>
                    <td data-label="Total Amount" className="text-end fw-semibold text-danger">
                      {fmtMoney(r.total_amount, r.currency)}
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