import { useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { clearSession } from '../auth';
import { useIdleTimeout } from '../hooks/useIdleTimeout';
import SessionWarningModal from './SessionWarningModal';

export default function SessionTimeoutGuard() {
  const navigate = useNavigate();

  const handleLogout = useCallback(() => {
    clearSession();
    navigate('/login', {
      replace: true,
      state: { reason: 'idle_timeout' },
    });
  }, [navigate]);

  const { warning, staySignedIn, logOutNow } = useIdleTimeout({
    onLogout: handleLogout,
  });

  return (
    <SessionWarningModal
      open={warning}
      onStay={staySignedIn}
      onLogout={logOutNow}
    />
  );
}