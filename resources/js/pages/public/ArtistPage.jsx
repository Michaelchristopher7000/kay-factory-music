import { useEffect } from 'react';
import Navbar from '../../components/public/Navbar';
import Footer from '../../components/public/Footer';
import {
  LoadingState,
  ErrorState,
} from '../../components/public/StateViews';
import { usePublicResource } from '../../hooks/usePublicResource';

import ArtistHero from '../../components/public/artist/ArtistHero';
import ArtistAbout from '../../components/public/artist/ArtistAbout';
import ArtistMusic from '../../components/public/artist/ArtistMusic';
import ArtistVideos from '../../components/public/artist/ArtistVideos';
import ArtistGallery from '../../components/public/artist/ArtistGallery';
import ArtistEvents from '../../components/public/artist/ArtistEvents';
import ArtistBooking from '../../components/public/artist/ArtistBooking';

export default function ArtistPage({ artistSlug }) {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, [artistSlug]);

  const { data, loading, error, notFound, reload } = usePublicResource(
    artistSlug ? `/artists/${artistSlug}` : null
  );

  const artist = data?.data || null;
  const releases = artist?.releases || [];
  const videos = artist?.videos || [];
  const gallery = artist?.gallery || [];
  const events = artist?.events || [];

  return (
    <>
      <Navbar />
      <main>
        {loading && <LoadingState label="Loading artist" />}

        {!loading && notFound && (
          <section className="kfm-page-hero">
            <div className="kfm-container kfm-page-hero__content">
              <a href="/artists" className="kfm-back">
                All artists
              </a>
              <div className="kfm-page-hero__eyebrow">Not Found</div>
              <h1 className="kfm-display kfm-page-hero__title">
                Artist not found
              </h1>
            </div>
          </section>
        )}

        {!loading && !notFound && error && (
          <section className="kfm-section">
            <div className="kfm-container">
              <a href="/artists" className="kfm-back">
                All artists
              </a>
              <ErrorState message={error} onRetry={reload} />
            </div>
          </section>
        )}

        {!loading && !notFound && !error && artist && (
          <>
            <ArtistHero artist={artist} />
            <ArtistAbout artist={artist} />
            <ArtistMusic releases={releases} />
            <ArtistVideos videos={videos} />
            <ArtistGallery images={gallery} />
            <ArtistEvents events={events} />
            <ArtistBooking artist={artist} />
          </>
        )}
      </main>
      <Footer />
    </>
  );
}