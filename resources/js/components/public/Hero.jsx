import { useEffect, useRef, useState } from "react";
import { HERO } from "../../data/landing";

export default function Hero() {
    const videoRef = useRef(null);
    const [videoReady, setVideoReady] = useState(false);

    useEffect(() => {
        const v = videoRef.current;
        if (!v) return;

        const tryPlay = () => {
            const p = v.play();
            if (p && typeof p.catch === "function") {
                p.catch(() => setVideoReady(false));
            }
        };

        if (v.readyState >= 2) {
            setVideoReady(true);
            tryPlay();
        }

        const onReady = () => {
            setVideoReady(true);
            tryPlay();
        };

        v.addEventListener("canplay", onReady);
        v.addEventListener("loadeddata", onReady);

        return () => {
            v.removeEventListener("canplay", onReady);
            v.removeEventListener("loadeddata", onReady);
        };
    }, []);

    return (
        <section className="kfm-hero">
            <div className="kfm-hero__bg" aria-hidden="true">
                <img
                    src={HERO.fallbackImage}
                    alt=""
                    className="kfm-hero__fallback"
                />
                <video
                    ref={videoRef}
                    className={`kfm-hero__video ${videoReady ? "is-ready" : ""}`}
                    autoPlay
                    muted
                    loop
                    playsInline
                    preload="auto"
                >
                    <source src={HERO.video} type="video/mp4" />
                </video>
                <div className="kfm-hero__tile" />
                <div className="kfm-hero__veil" />
            </div>

            <div className="kfm-hero__content">
                <div className="kfm-hero__eyebrow">{HERO.eyebrow}</div>

                <h1 className="kfm-hero__headline">{HERO.headline}</h1>

                <p className="kfm-hero__lede">{HERO.lede}</p>

                <a href={HERO.primaryCta.href} className="kfm-hero__cta">
                    {HERO.primaryCta.label}
                </a>

                <a href={HERO.secondaryCta.href} className="kfm-hero__secondary">
                    {HERO.secondaryCta.label}
                </a>
            </div>
        </section>
    );
}