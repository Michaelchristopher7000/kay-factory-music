import { useEffect } from 'react';
import Navbar from '../components/landing/Navbar';
import Hero from '../components/landing/Hero';
import FeaturedArtist from '../components/landing/FeaturedArtist';
import LatestRelease from '../components/landing/LatestRelease';
import ArtistsSection from '../components/landing/ArtistsSection';
import AboutLabel from '../components/landing/AboutLabel';
import ReleasesSection from '../components/landing/ReleasesSection';
import SubmissionCTA from '../components/landing/SubmissionCTA';
import Footer from '../components/landing/Footer';

export default function Landing() {
  useEffect(() => {
    document.title = 'Kay Factory Music — Where Sound Becomes Legacy';
    document.body.style.background = '#0A0A0A';
  }, []);

  return (
    <div className="kfm-landing">
      <Navbar />
      <main>
        <Hero />
        <FeaturedArtist />
        <LatestRelease />
        <ArtistsSection />
        <AboutLabel />
        <ReleasesSection />
        <SubmissionCTA />
      </main>
      <Footer />
    </div>
  );
}