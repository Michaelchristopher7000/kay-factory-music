import api from './api';

/**
 * Search across artists, releases, contracts, and tracks.
 * Runs the four queries in parallel and normalizes results into a flat list.
 */
export async function searchAll(term, perType = 4) {
  if (!term || term.trim().length < 2) {
    return [];
  }

  const params = { search: term, per_page: perType };

  const [artists, releases, contracts, tracks] = await Promise.all([
    api.get('/artists', { params }).then((r) => r.data?.data || []).catch(() => []),
    api.get('/releases', { params }).then((r) => r.data?.data || []).catch(() => []),
    api.get('/contracts', { params }).then((r) => r.data?.data || []).catch(() => []),
    api.get('/tracks', { params }).then((r) => r.data?.data || []).catch(() => []),
  ]);

  return [
    ...artists.map((a) => ({
      kind: 'artist',
      id: a.id,
      code: a.artist_code,
      title: a.name,
      subtitle: a.genre || null,
      url: `/artists/${a.id}`,
    })),
    ...releases.map((r) => ({
      kind: 'release',
      id: r.id,
      code: r.release_code,
      title: r.title,
      subtitle: r.artist?.name || null,
      url: `/releases/${r.id}`,
    })),
    ...contracts.map((c) => ({
      kind: 'contract',
      id: c.id,
      code: c.contract_code,
      title: c.title,
      subtitle: c.artist?.name || null,
      url: `/contracts/${c.id}`,
    })),
    ...tracks.map((t) => ({
      kind: 'track',
      id: t.id,
      code: t.track_code,
      title: t.title,
      subtitle: t.artist?.name || null,
      url: `/tracks/${t.id}`,
    })),
  ];
}

export const KIND_META = {
  artist:   { label: 'Artist',   icon: 'bi-person' },
  release:  { label: 'Release',  icon: 'bi-vinyl' },
  contract: { label: 'Contract', icon: 'bi-file-earmark-text' },
  track:    { label: 'Track',    icon: 'bi-music-note-list' },
};