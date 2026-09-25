import { useEffect, useState } from "react";
import publicApi from "../../publicApi";
import Reveal from "../Reveal";

const SHOWN = 7;

export default function RosterAndLatest() {
    const [artists, setArtists] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let cancelled = false;

        (async () => {
            try {
                const res = await publicApi.get("/artists", {
                    params: { per_page: SHOWN },
                });
                if (cancelled) return;
                setArtists(res.data?.data || []);
            } catch {
                if (cancelled) return;
                setArtists([]);
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, []);

    return (
        <section className="kfm-roster26">
            <div className="kfm-container">
                {/* ---------- Header ---------- */}
                <Reveal>
                    <div className="kfm-roster26__head">
                        <div className="kfm-roster26__label">
                            The Family
                        </div>
                        <h2 className="kfm-roster26__title">Roster 2026</h2>
                        <p className="kfm-roster26__lede">
                            Meet the artists defining the sound of a
                            generation. Raw talent, authentic stories,
                            global impact.
                        </p>
                    </div>
                </Reveal>

                {/* ---------- States ---------- */}
                {loading && (
                    <div className="kfm-roster26__state">Loading…</div>
                )}

                {!loading && artists.length === 0 && (
                    <div className="kfm-roster26__state">
                        No artists to display.
                    </div>
                )}

                {/* ---------- Grid ---------- */}
                {!loading && artists.length > 0 && (
                    <div className="kfm-roster26__grid">
                        {artists.map((artist, i) => (
                            <Reveal key={artist.slug} delay={i * 50}>
                                <a
                                    href={`/artists/${artist.slug}`}
                                    className="kfm-roster26__card"
                                >
                                    <div className="kfm-roster26__card-image">
                                        {artist.avatar ? (
                                            <img
                                                src={artist.avatar}
                                                alt={artist.name}
                                                loading="lazy"
                                            />
                                        ) : (
                                            <div className="kfm-roster26__placeholder">
                                                <i
                                                    className="bi bi-person"
                                                    aria-hidden="true"
                                                />
                                            </div>
                                        )}

                                        {artist.genre && (
                                            <span className="kfm-roster26__badge">
                                                {artist.genre}
                                            </span>
                                        )}

                                        <span className="kfm-roster26__shade" />

                                        <span className="kfm-roster26__name">
                                            {artist.name}
                                        </span>
                                    </div>
                                </a>
                            </Reveal>
                        ))}
                    </div>
                )}

                {/* ---------- Show All ---------- */}
                {!loading && artists.length > 0 && (
                    <div className="kfm-roster26__footer">
                        <a
                            href="/artists"
                            className="kfm-roster26__view-all"
                        >
                            Show All
                            <i
                                className="bi bi-arrow-right"
                                aria-hidden="true"
                            />
                        </a>
                    </div>
                )}
            </div>
        </section>
    );
}