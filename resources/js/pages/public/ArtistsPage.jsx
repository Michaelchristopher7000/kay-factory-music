import { useEffect, useState } from "react";
import Navbar from "../../components/public/Navbar";
import Footer from "../../components/public/Footer";
import Reveal from "../../components/Reveal";
import publicApi from "../../publicApi";

export default function ArtistsPage() {
    const [artists, setArtists] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        window.scrollTo(0, 0);
    }, []);

    useEffect(() => {
        let cancelled = false;

        (async () => {
            try {
                const res = await publicApi.get("/artists", {
                    params: { per_page: 100 },
                });
                if (cancelled) return;
                setArtists(res.data?.data || []);
            } catch {
                if (cancelled) return;
                setError("Unable to load artists. Please try again.");
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, []);

    return (
        <>
            <Navbar />

            <main>
                <section className="kfm-artists-page">
                    <div className="kfm-container kfm-artists-page__content">
                        <Reveal>
                            <h1
                                className="kfm-artists-page__headline"
                                aria-hidden="true"
                            >
                                ARTISTS
                            </h1>
                        </Reveal>

                        {loading && (
                            <div className="kfm-artists-page__state">
                                Loading…
                            </div>
                        )}

                        {!loading && error && (
                            <div className="kfm-artists-page__state">
                                {error}
                            </div>
                        )}

                        {!loading && !error && artists.length === 0 && (
                            <div className="kfm-artists-page__state">
                                No artists yet.
                            </div>
                        )}

                        {!loading && !error && artists.length > 0 && (
                            <div className="kfm-artists-page__grid">
                                {artists.map((artist, i) => (
                                    <Reveal
                                        key={artist.slug}
                                        delay={(i % 3) * 60}
                                    >
                                        <a
                                            href={`/artists/${artist.slug}`}
                                            className="kfm-artists-page__card"
                                        >
                                            {artist.avatar ? (
                                                <img
                                                    src={artist.avatar}
                                                    alt={artist.name}
                                                    loading="lazy"
                                                />
                                            ) : (
                                                <div className="kfm-artists-page__placeholder">
                                                    <i
                                                        className="bi bi-person"
                                                        aria-hidden="true"
                                                    />
                                                </div>
                                            )}

                                            <span className="kfm-artists-page__shade" />

                                            <span className="kfm-artists-page__name">
                                                {artist.name}
                                            </span>
                                        </a>
                                    </Reveal>
                                ))}
                            </div>
                        )}

                        {!loading && !error && artists.length > 0 && (
                            <div className="kfm-artists-page__footnote">
                                <span className="kfm-artists-page__footnote-line" />
                                <span>Kay Factory Music · Roster</span>
                                <span className="kfm-artists-page__footnote-line" />
                            </div>
                        )}
                    </div>
                </section>
            </main>

            <Footer />
        </>
    );
}