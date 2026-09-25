
import { useEffect } from "react";
import Navbar from "../../components/public/Navbar";
import Footer from "../../components/public/Footer";
import Reveal from "../../components/Reveal";

export default function CookiePolicyPage() {
    useEffect(() => {
        window.scrollTo(0, 0);
        document.title = "Cookie Policy — Kay Factory Music";
    }, []);

    return (
        <>
            <Navbar />

            <main>
                {/* ================= HERO ================= */}
                <section className="kfm-about-page__hero">
                    <div className="kfm-container">
                        <Reveal>
                            <h1
                                className="kfm-about-page__headline"
                                aria-hidden="true"
                            >
                                COOKIES
                            </h1>
                        </Reveal>

                        <Reveal delay={100}>
                            <div className="kfm-about-page__hero-copy">
                                <div className="kfm-about-page__hero-brand">
                                    Kay Factory Music
                                </div>

                                <div className="kfm-about-page__hero-tagline">
                                    Cookie Policy
                                </div>
                            </div>
                        </Reveal>
                    </div>
                </section>

                {/* ================= COOKIE POLICY ================= */}
                <section className="kfm-section">
                    <div className="kfm-container">
                        <div className="kfm-legal">

                            <Reveal>
                                <p className="kfm-legal__meta">
                                    Last updated: September 2026
                                </p>
                            </Reveal>

                            <Reveal delay={60}>
                                <p>
                                    This Cookie Policy explains how Kay Factory
                                    Music uses cookies and similar technologies
                                    on our website. We keep our use of these
                                    technologies limited to what is needed to
                                    operate and secure the website.
                                </p>
                            </Reveal>

                            <Reveal>
                                <h2>1. What are cookies?</h2>
                            </Reveal>

                            <p>
                                Cookies are small files that websites can store
                                on your device. They can help a website remember
                                information, maintain a session, or support
                                certain features.
                            </p>

                            <Reveal>
                                <h2>2. Cookies we use</h2>
                            </Reveal>

                            <p>
                                Kay Factory Music primarily uses cookies that are
                                necessary for the website and staff platform to
                                function properly.
                            </p>

                            <ul>
                                <li>
                                    <strong>Session cookies.</strong>{" "}
                                    These may be used to maintain an authenticated
                                    session when an authorised staff member signs
                                    in to the management platform.
                                </li>

                                <li>
                                    <strong>Security-related cookies.</strong>{" "}
                                    These may be used to help protect forms,
                                    authentication, and other parts of the
                                    platform against common security threats.
                                </li>
                            </ul>

                            <Reveal>
                                <h2>3. Local storage</h2>
                            </Reveal>

                            <p>
                                Some preferences may be stored locally in your
                                browser rather than through cookies. For example,
                                the website may store a preference relating to
                                cookie notices or other interface settings.
                            </p>

                            <Reveal>
                                <h2>4. Analytics and advertising</h2>
                            </Reveal>

                            <p>
                                We do not currently use advertising or remarketing
                                cookies on the website.
                            </p>

                            <p>
                                If we introduce analytics, advertising, or other
                                non-essential tracking technologies in the future,
                                we will update this policy and provide any choices
                                or consent mechanisms required by applicable law.
                            </p>

                            <Reveal>
                                <h2>5. Managing cookies</h2>
                            </Reveal>

                            <p>
                                Most web browsers allow you to view, delete, or
                                block cookies through their settings. You can
                                also clear locally stored website data through
                                your browser.
                            </p>

                            <p>
                                Blocking necessary cookies or local storage may
                                affect features that require them, including
                                authenticated areas of the platform.
                            </p>

                            <Reveal>
                                <h2>6. Third-party services</h2>
                            </Reveal>

                            <p>
                                Some features of our website may rely on services
                                provided by third parties. Those services may
                                process technical information or use their own
                                technologies according to their respective
                                policies.
                            </p>

                            <Reveal>
                                <h2>7. Changes to this policy</h2>
                            </Reveal>

                            <p>
                                We may update this Cookie Policy when our website,
                                services, or use of cookies changes. The date at
                                the beginning of this page shows when the current
                                version was published.
                            </p>

                            <Reveal>
                                <h2>8. Contact</h2>
                            </Reveal>

                            <p>
                                If you have a question about cookies or how
                                Kay Factory Music uses them, contact us at:
                            </p>

                            <p>
                                <a href="mailto:Kayfactorymusic@gmail.com">
                                    cookies@kayfactorymusic.com
                                </a>
                            </p>

                        </div>
                    </div>
                </section>
            </main>

            <Footer />
        </>
    );
}
