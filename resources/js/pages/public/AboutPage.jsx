import { useEffect } from "react";
import Navbar from "../../components/public/Navbar";
import Footer from "../../components/public/Footer";
import Reveal from "../../components/Reveal";

/* ============================================================
   ABOUT PAGE CONTENT
   ============================================================ */

const HERO = {
    brand: "Kay Factory Music",
    tagline: "Where Sound Becomes Legacy.",
};

const MISSION = {
    eyebrow: "Who We Are",
    text: "Kay Factory Music is a modern record label built around artist development, creative direction, production, and global distribution. We work with artists at every stage — from first studio session to global release.",
};

const STATS = [
    { value: "12", label: "Artists" },
    { value: "30", label: "Releases" },
    { value: "40", label: "Countries" },
];

const STORY = {
    eyebrow: "Our Story",
    title: "Built for the sound of now.",
    body: [
        "Kay Factory Music began with a single idea: build infrastructure that matches the ambition of African music.",
        "We sign artists for the long game — development, creative direction, and release strategy that respects the music.",
    ],
};

const VALUES = {
    eyebrow: "What We Do",
    title: "The Kay Factory Way.",
    items: [
        {
            title: "Artist Development",
            text: "Hands-on A&R, career strategy, and studio time — building artists, not just songs.",
        },
        {
            title: "Creative Direction",
            text: "Visuals, packaging, and campaigns built around the music.",
        },
        {
            title: "Music Production",
            text: "In-house production, mixing, and mastering across genres.",
        },
        {
            title: "Global Distribution",
            text: "Delivery to every major platform, worldwide, with clear reporting.",
        },
    ],
};

const CTA = {
    title: "Let's build something lasting.",
    lede: "If you're an artist, collaborator, or partner — we want to hear from you.",
    primary: {
        label: "Submit Your Music",
        href: "/submit-demo",
    },
    secondary: {
        label: "Get in Touch",
        href: "/contact",
    },
};

/* ============================================================
   PAGE
   ============================================================ */

export default function AboutPage() {
    useEffect(() => {
        window.scrollTo(0, 0);
        document.title = "About — Kay Factory Music";
    }, []);

    return (
        <>
            <Navbar />

            <main>

                {/* ==================================================
                    HERO
                   ================================================== */}

                <section className="kfm-about-page__hero">
                    <div className="kfm-container">

                        <Reveal>
                            <h1
                                className="kfm-about-page__headline"
                                aria-hidden="true"
                            >
                                ABOUT
                            </h1>
                        </Reveal>

                        <Reveal delay={100}>
                            <div className="kfm-about-page__hero-copy">

                                <div className="kfm-about-page__hero-brand">
                                    {HERO.brand}
                                </div>

                                <div className="kfm-about-page__hero-tagline">
                                    {HERO.tagline}
                                </div>

                            </div>
                        </Reveal>

                    </div>
                </section>


                {/* ==================================================
                    MISSION
                   ================================================== */}

                <section className="kfm-about-page__mission">
                    <div className="kfm-container">

                        <Reveal>
                            <div className="kfm-about-page__eyebrow">
                                {MISSION.eyebrow}
                            </div>
                        </Reveal>

                        <Reveal delay={80}>
                            <p className="kfm-about-page__mission-text">
                                {MISSION.text}
                            </p>
                        </Reveal>

                        <Reveal delay={160}>
                            <div className="kfm-about-page__stats">

                                {STATS.map((stat) => (
                                    <div
                                        key={stat.label}
                                        className="kfm-about-page__stat"
                                    >

                                        <div className="kfm-about-page__stat-value">
                                            <span className="kfm-about-page__stat-plus">
                                                +
                                            </span>

                                            {stat.value}
                                        </div>

                                        <div className="kfm-about-page__stat-label">
                                            {stat.label}
                                        </div>

                                    </div>
                                ))}

                            </div>
                        </Reveal>

                    </div>
                </section>


                {/* ==================================================
                    STORY + LOGO
                   ================================================== */}

                <section className="kfm-about-page__story">
                    <div className="kfm-container">

                        <div className="kfm-about-page__story-grid">

                            {/* LEFT — STORY TEXT */}

                            <Reveal>
                                <div className="kfm-about-page__story-copy">

                                    <div className="kfm-about-page__eyebrow">
                                        {STORY.eyebrow}
                                    </div>

                                    <h2 className="kfm-about-page__story-title">
                                        {STORY.title}
                                    </h2>

                                    <div className="kfm-about-page__story-body">

                                        {STORY.body.map((paragraph, index) => (
                                            <p key={index}>
                                                {paragraph}
                                            </p>
                                        ))}

                                    </div>

                                </div>
                            </Reveal>


                            {/* RIGHT — KAY FACTORY MUSIC LOGO */}

                            <Reveal delay={120}>
                                <div className="kfm-about-page__story-logo">

                                    <img
                                        src="/images/kfm-logo-white.png"
                                        alt="Kay Factory Music"
                                        loading="lazy"
                                    />

                                </div>
                            </Reveal>

                        </div>

                    </div>
                </section>


                {/* ==================================================
                    VALUES / WHAT WE DO
                   ================================================== */}

                <section className="kfm-about-page__values">
                    <div className="kfm-container">

                        <Reveal>
                            <div className="kfm-about-page__eyebrow">
                                {VALUES.eyebrow}
                            </div>
                        </Reveal>

                        <Reveal delay={80}>
                            <h2 className="kfm-about-page__values-title">
                                {VALUES.title}
                            </h2>
                        </Reveal>


                        <div className="kfm-about-page__values-grid">

                            {VALUES.items.map((item, index) => (
                                <Reveal
                                    key={item.title}
                                    delay={index * 60}
                                >

                                    <div className="kfm-about-page__value">

                                        <div className="kfm-about-page__value-num">
                                            {String(index + 1).padStart(2, "0")}
                                        </div>

                                        <h3 className="kfm-about-page__value-title">
                                            {item.title}
                                        </h3>

                                        <p className="kfm-about-page__value-text">
                                            {item.text}
                                        </p>

                                    </div>

                                </Reveal>
                            ))}

                        </div>

                    </div>
                </section>


                {/* ==================================================
                    CTA
                   ================================================== */}

                <section className="kfm-about-page__cta">
                    <div className="kfm-container">

                        <Reveal>
                            <h2 className="kfm-about-page__cta-title">
                                {CTA.title}
                            </h2>
                        </Reveal>

                        <Reveal delay={80}>
                            <p className="kfm-about-page__cta-lede">
                                {CTA.lede}
                            </p>
                        </Reveal>

                        <Reveal delay={160}>
                            <div className="kfm-about-page__cta-actions">

                                <a
                                    href={CTA.primary.href}
                                    className="kfm-btn kfm-btn--primary"
                                >
                                    {CTA.primary.label}
                                </a>

                                <a
                                    href={CTA.secondary.href}
                                    className="kfm-btn kfm-btn--ghost"
                                >
                                    {CTA.secondary.label}
                                </a>

                            </div>
                        </Reveal>

                    </div>
                </section>

            </main>

            <Footer />
        </>
    );
}