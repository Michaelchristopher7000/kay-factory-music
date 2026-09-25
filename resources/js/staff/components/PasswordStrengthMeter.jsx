/**
 * Pure client-side password strength indicator.
 * Never sends, logs, or stores the password.
 */

const getStrength = (password) => {
  if (!password) return { score: 0, label: '', color: '' };

  let score = 0;
  if (password.length >= 16) score += 3;
  else if (password.length >= 12) score += 2;
  else if (password.length >= 8) score += 1;

  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 1;
  if (/\d/.test(password)) score += 1;
  if (/[^A-Za-z0-9]/.test(password)) score += 1;

  if (score <= 3) return { score: 1, label: 'Weak', color: '#ef4444' };
  if (score <= 5) return { score: 2, label: 'Fair', color: '#f59e0b' };
  return { score: 3, label: 'Strong', color: '#4ade80' };
};

export default function PasswordStrengthMeter({ password = '' }) {
  if (!password) return null;

  const { score, label, color } = getStrength(password);

  return (
    <div className="kfm-password-strength">
      <div className="kfm-password-strength__bars">
        {[1, 2, 3].map((i) => (
          <span
            key={i}
            className="kfm-password-strength__bar"
            style={{
              background: i <= score ? color : 'rgba(255,255,255,0.08)',
            }}
          />
        ))}
      </div>
      <div className="kfm-password-strength__label" style={{ color }}>
        {label}
      </div>
    </div>
  );
}