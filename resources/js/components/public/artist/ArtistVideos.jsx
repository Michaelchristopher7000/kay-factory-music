import { useState } from 'react';
import VideoCard from './VideoCard';
import VideoPlayerModal from './VideoPlayerModal';

export default function ArtistVideos({ videos = [] }) {
  const [activeVideo, setActiveVideo] = useState(null);

  if (!videos.length) return null;

  return (
    <section className="kfm-section kfm-artist-videos">
      <div className="kfm-container">
        <div className="kfm-section__head">
          <div>
            <div className="kfm-eyebrow">Watch</div>
            <h2 className="kfm-display kfm-section__title">Videos</h2>
          </div>
        </div>

        <div className="kfm-videos-grid">
          {videos.map((video) => (
            <VideoCard
              key={video.id}
              video={video}
              onPlay={setActiveVideo}
            />
          ))}
        </div>
      </div>

      <VideoPlayerModal
        video={activeVideo}
        onClose={() => setActiveVideo(null)}
      />
    </section>
  );
}