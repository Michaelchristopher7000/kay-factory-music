import { useEffect, useState } from "react";
import publicApi from "../../publicApi";
import Reveal from "../Reveal";

const SHOWN = 4;

export default function LatestMusic() {
    const [releases, setReleases] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let cancelled = false;

        (async () => {
            try {
                const res = await publicApi.get("/releases", {
                    params: { per_page: SHOWN },
                });
                if (cancelled) return;
                setReleases(res.data?.data || []);
            } catch {
                if (cancelled) return;
                setReleases([]);
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, []);

    return (
        <section className="kfm-latest26">
            <div className="kfm-container">
                <Reveal>
                    <div className="kfm-latest26__head">
                        <h2 className="kfm-latest26__headline" aria-hidden="true">MUSIC</h2>
                        <a href="/music" className="kfm-latest26__view-all">
                            Show All
                            <i
                                className="bi bi-arrow-right"
                                aria-hidden="true"
                            />
                        </a>
                    </div>
                </Reveal>

                {loading && (
                    <div className="kfm-latest26__state">Loading…</div>
                )}

                {!loading && releases.length === 0 && (
                    <div className="kfm-latest26__state">
                        No releases yet.
                    </div>
                )}

                {!loading && releases.length > 0 && (
                    <div className="kfm-latest26__grid">
                        {releases.slice(0, SHOWN).map((release, i) => (
                            <Reveal key={release.slug} delay={i * 50}>
                                <a
                                    href={`/music/${release.slug}`}
                                    className="kfm-latest26__card"
                                >
                                    <div className="kfm-latest26__cover">
                                        {release.cover_art_path ? (
                                            <img
                                                src={release.cover_art_path}
                                                alt={release.title}
                                                loading="lazy"
                                            />
                                        ) : (
                                            <div className="kfm-latest26__placeholder">
                                                <i
                                                    className="bi bi-vinyl"
                                                    aria-hidden="true"
                                                />
                                            </div>
                                        )}
                                    </div>

                                    <div className="kfm-latest26__info">
                                        {release.artist?.name && (
                                            <div className="kfm-latest26__artist">
                                                {release.artist.name}
                                            </div>
                                        )}
                                        <div className="kfm-latest26__name">
                                            {release.title}
                                        </div>
                                    </div>
                                </a>
                            </Reveal>
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}