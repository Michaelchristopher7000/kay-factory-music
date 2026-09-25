export default function ArtistAbout({ artist }) {
  if (!artist?.bio) return null;

  return (
    <section className="kfm-artist-about">
      <div className="kfm-container">
        <p className="kfm-artist-about__bio">{artist.bio}</p>
      </div>
    </section>
  );
}