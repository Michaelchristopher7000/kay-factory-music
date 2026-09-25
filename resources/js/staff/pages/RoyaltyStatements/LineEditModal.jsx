import { useState, useEffect } from 'react';

export default function LineEditModal({ show, line, onCancel, onSave, saving }) {
  const [royaltyRate, setRoyaltyRate] = useState('');
  const [royaltyAmount, setRoyaltyAmount] = useState('');
  const [description, setDescription] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    if (line) {
      setRoyaltyRate(line.royalty_rate ?? '');
      setRoyaltyAmount(line.royalty_amount ?? '');
      setDescription(line.description || '');
      setError('');
    }
  }, [line]);

  if (!show || !line) return null;

  const submit = (e) => {
    e.preventDefault();
    setError('');

    const rateNum = Number(royaltyRate);
    if (isNaN(rateNum) || rateNum < 0 || rateNum > 100) {
      setError('Rate must be between 0 and 100.');
      return;
    }

    const payload = {
      royalty_rate: rateNum,
      description: description || null,
    };

    // Only send royalty_amount if user manually changed it
    if (royaltyAmount !== '' && Number(royaltyAmount) !== Number(line.royalty_amount)) {
      payload.royalty_amount = Number(royaltyAmount);
    }

    onSave(payload);
  };

  return (
    <div className="modal d-block" tabIndex={-1} style={{ background: 'rgba(0,0,0,0.5)' }}>
      <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content border-0 shadow">
          <form onSubmit={submit}>
            <div className="modal-header">
              <h5 className="modal-title">Edit Line</h5>
              <button type="button" className="btn-close" onClick={onCancel} disabled={saving}></button>
            </div>
            <div className="modal-body">
              <div className="mb-3">
                <label className="form-label">Source</label>
                <div className="text-muted small">{line.source}</div>
              </div>

              <div className="mb-3">
                <label className="form-label">Revenue</label>
                <div className="text-muted small">{Number(line.revenue_amount).toLocaleString()}</div>
              </div>

              <div className="mb-3">
                <label className="form-label">Royalty Rate (%)</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  className="form-control"
                  value={royaltyRate}
                  onChange={(e) => setRoyaltyRate(e.target.value)}
                  required
                />
              </div>

              <div className="mb-3">
                <label className="form-label">Royalty Amount</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  className="form-control"
                  value={royaltyAmount}
                  onChange={(e) => setRoyaltyAmount(e.target.value)}
                  placeholder="Auto-computed from rate"
                />
                <div className="form-text">Leave as-is to auto-compute from rate.</div>
              </div>

              <div className="mb-0">
                <label className="form-label">Description</label>
                <input
                  type="text"
                  className="form-control"
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                />
              </div>

              {error && <div className="text-danger small mt-2">{error}</div>}
            </div>
            <div className="modal-footer">
              <button type="button" className="btn btn-outline-secondary" onClick={onCancel} disabled={saving}>
                Cancel
              </button>
              <button type="submit" className="btn btn-dark" disabled={saving}>
                {saving ? 'Saving…' : 'Save Line'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}