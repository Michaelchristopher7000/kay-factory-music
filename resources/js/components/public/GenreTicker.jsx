const DEFAULT_GENRES = ['Afrobeats', 'Alté', 'R&B', 'Hip-Hop', 'Amapiano', 'Soul'];

export default function GenreTicker({ genres = DEFAULT_GENRES }) {
  const items = [...genres, ...genres];

  return (
    <div className="kfm-marquee" aria-hidden="true">
      <div className="kfm-marquee__track">
        {items.map((genre, i) => (
          <span key={i} className="kfm-marquee__item">
            {genre}
            <span className="kfm-marquee__dot">●</span>
          </span>
        ))}
      </div>
    </div>
  );
}