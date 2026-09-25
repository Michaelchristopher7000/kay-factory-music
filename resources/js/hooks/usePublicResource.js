import { useCallback, useEffect, useRef, useState } from 'react';
import publicApi from '../publicApi';

export function usePublicResource(url, params = null) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [notFound, setNotFound] = useState(false);

  const paramsKey = params ? JSON.stringify(params) : '';
  const aliveRef = useRef(true);

  const load = useCallback(async () => {
    if (!url) return;
    setLoading(true);
    setError(null);
    setNotFound(false);

    try {
      const res = await publicApi.get(url, params ? { params } : undefined);
      if (!aliveRef.current) return;
      setData(res.data);
    } catch (err) {
      if (!aliveRef.current) return;
      if (err.response?.status === 404) {
        setNotFound(true);
      } else {
        setError('Unable to load data. Please try again.');
      }
    } finally {
      if (aliveRef.current) setLoading(false);
    }
  }, [url, paramsKey]);

  useEffect(() => {
    aliveRef.current = true;
    load();
    return () => { aliveRef.current = false; };
  }, [load]);

  return { data, loading, error, notFound, reload: load };
}