import { useEffect, useMemo, useState } from "react";
import Navbar from "../../components/public/Navbar";
import Footer from "../../components/public/Footer";
import Reveal from "../../components/Reveal";
import publicApi from "../../publicApi";

const TYPES = [
    "All",
    "single",
    "ep",
    "album",
    "mixtape",
    "compilation",
    "other",
];

export default function MusicPage() {
    const [releases, setReleases] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [type, setType] = useState("All");

    useEffect(() => {
        window.scrollTo(0, 0);
    }, []);

    useEffect(() => {
        let cancelled = false;

        (async () => {
            try {
                const res = await publicApi.get("/releases", {
                    params: { per_page: 100 },
                });
                if (cancelled) return;
                setReleases(res.data?.data || []);
            } catch {
                if (cancelled) return;
                setError("Unable to load music. Please try again.");
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();

        return () => {
            cancelled = true;
        };
    }, []);

    const presentTypes = useMemo(() => {
        const available = new Set(
            releases.map((release) => release.type).filter(Boolean),
        );
        return TYPES.filter((item) => item === "All" || available.has(item));
    }, [releases]);

    const filtered = useMemo(() => {
        if (type === "All") return releases;
        return releases.filter((release) => release.type === type);
    }, [releases, type]);

    const featured = filtered[0] || null;
    const rest = filtered.slice(1);

    return (
        <>
            <Navbar />

            <main>
                <section className="kfm-music-page">
                    <div className="kfm-container kfm-music-page__content">
                        {/* ---------- Outlined heading ---------- */}
                        <Reveal>
                            <h1
                                className="kfm-music-page__headline"
                                aria-hidden="true"
                            >
                                MUSIC
                            </h1>
                        </Reveal>

                        {/* ---------- Filters ---------- */}
                        {!loading && !error && presentTypes.length > 1 && (
                            <Reveal delay={80}>
                                <div className="kfm-music-page__filters">
                                    {presentTypes.map((item) => (
                                        <button
                                            key={item}
                                            type="button"
                                            className={`kfm-music-page__filter ${
                                                type === item
                                                    ? "is-active"
                                                    : ""
                                            }`}
                                            onClick={() => setType(item)}
                                        >
                                            {item === "All"
                                                ? "All"
                                                : item.toUpperCase()}
                                        </button>
                                    ))}
                                </div>
                            </Reveal>
                        )}

                        {/* ---------- States ---------- */}
                        {loading && (
                            <div className="kfm-music-page__state">
                                Loading…
                            </div>
                        )}

                        {!loading && error && (
                            <div className="kfm-music-page__state">
                                {error}
                            </div>
                        )}

                        {!loading && !error && releases.length === 0 && (
                            <div className="kfm-music-page__state">
                                No releases yet.
                            </div>
                        )}

                        {!loading &&
                            !error &&
                            releases.length > 0 &&
                            filtered.length === 0 && (
                                <div className="kfm-music-page__state">
                                    No releases of this type yet.
                                </div>
                            )}

                        {/* ---------- Featured release ---------- */}
                        {!loading && !error && featured && (
                            <Reveal>
                                <a
                                    href={`/music/${featured.slug}`}
                                    className="kfm-music-page__featured"
                                >
                                    <div className="kfm-music-page__featured-cover">
                                        {featured.cover_art_path ? (
                                            <img
                                                src={
                                                    featured.cover_art_path
                                                }
                                                alt={featured.title}
                                            />
                                        ) : (
                                            <div className="kfm-music-page__featured-placeholder">
                                                <i
                                                    className="bi bi-vinyl"
                                                    aria-hidden="true"
                                                />
                                            </div>
                                        )}

                                        <span className="kfm-music-page__featured-shade" />

                                        <div className="kfm-music-page__featured-overlay">
                                            <div className="kfm-music-page__featured-title">
                                                {featured.title}
                                            </div>
                                            {featured.artist?.name && (
                                                <div className="kfm-music-page__featured-artist">
                                                    {featured.artist.name}
                                                </div>
                                            )}
                                            <span className="kfm-music-page__featured-cta">
                                                Listen
                                                <i
                                                    className="bi bi-arrow-right"
                                                    aria-hidden="true"
                                                />
                                            </span>
                                        </div>
                                    </div>

                                    <div className="kfm-music-page__featured-meta">
                                        <span>
                                            {(
                                                featured.type || "Release"
                                            ).toUpperCase()}
                                        </span>
                                        <span className="kfm-music-page__dot">
                                            ·
                                        </span>
                                        <span>
                                            {featured.release_date ||
                                                "Coming soon"}
                                        </span>
                                        <span className="kfm-music-page__dot">
                                            ·
                                        </span>
                                        <span>Latest Release</span>
                                    </div>
                                </a>
                            </Reveal>
                        )}

                        {/* ---------- Rest of releases ---------- */}
                        {!loading &&
                            !error &&
                            rest.length > 0 && (
                                <div className="kfm-music-page__list">
                                    {rest.map((release, i) => (
                                        <Reveal
                                            key={release.slug}
                                            delay={(i % 3) * 50}
                                        >
                                            <a
                                                href={`/music/${release.slug}`}
                                                className="kfm-music-page__card"
                                            >
                                                <div className="kfm-music-page__card-cover">
                                                    {release.cover_art_path ? (
                                                        <img
                                                            src={
                                                                release.cover_art_path
                                                            }
                                                            alt={release.title}
                                                            loading="lazy"
                                                        />
                                                    ) : (
                                                        <div className="kfm-music-page__card-placeholder">
                                                            <i
                                                                className="bi bi-vinyl"
                                                                aria-hidden="true"
                                                            />
                                                        </div>
                                                    )}
                                                </div>

                                                <div className="kfm-music-page__card-body">
                                                    <div className="kfm-music-page__card-meta">
                                                        <span className="kfm-music-page__card-type">
                                                            {(
                                                                release.type ||
                                                                "Release"
                                                            ).toUpperCase()}
                                                        </span>
                                                        {release.release_date && (
                                                            <>
                                                                <span className="kfm-music-page__dot">
                                                                    ·
                                                                </span>
                                                                <span>
                                                                    {
                                                                        release.release_date
                                                                    }
                                                                </span>
                                                            </>
                                                        )}
                                                    </div>

                                                    <div className="kfm-music-page__card-title">
                                                        {release.title}
                                                    </div>

                                                    {release.artist
                                                        ?.name && (
                                                        <div className="kfm-music-page__card-artist">
                                                            {
                                                                release.artist
                                                                    .name
                                                            }
                                                        </div>
                                                    )}
                                                </div>
                                            </a>
                                        </Reveal>
                                    ))}
                                </div>
                            )}

                        {/* ---------- Footnote ---------- */}
                        {!loading && !error && filtered.length > 0 && (
                            <div className="kfm-music-page__footnote">
                                <span className="kfm-music-page__footnote-line" />
                                <span>
                                    Kay Factory Music · Catalogue
                                </span>
                                <span className="kfm-music-page__footnote-line" />
                            </div>
                        )}
                    </div>
                </section>
            </main>

            <Footer />
        </>
    );
}