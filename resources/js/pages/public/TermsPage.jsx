import { useEffect } from "react";
import Navbar from "../../components/public/Navbar";
import Footer from "../../components/public/Footer";
import Reveal from "../../components/Reveal";

export default function TermsPage() {
    useEffect(() => {
        window.scrollTo(0, 0);
        document.title = "Terms & Conditions — Kay Factory Music";
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
                                TERMS
                            </h1>
                        </Reveal>

                        <Reveal delay={100}>
                            <div className="kfm-about-page__hero-copy">
                                <div className="kfm-about-page__hero-brand">
                                    Kay Factory Music
                                </div>

                                <div className="kfm-about-page__hero-tagline">
                                    Terms & Conditions
                                </div>
                            </div>
                        </Reveal>
                    </div>
                </section>

                {/* ================= TERMS ================= */}
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
                                    These Terms & Conditions explain the rules
                                    that apply when you use the Kay Factory
                                    Music website and related online services.
                                    By using the website, you agree to use it
                                    responsibly and in accordance with these
                                    Terms.
                                </p>
                            </Reveal>

                            <Reveal>
                                <h2>1. Using the website</h2>
                            </Reveal>

                            <p>
                                You may use the Kay Factory Music website for
                                lawful purposes and for the purposes for which
                                it is made available.
                            </p>

                            <p>
                                You must not use the website to interfere with
                                its operation, attempt to gain unauthorised
                                access, introduce malicious software, or use it
                                in a way that violates the rights of other
                                people or organisations.
                            </p>

                            <Reveal>
                                <h2>2. Our content</h2>
                            </Reveal>

                            <p>
                                The website may contain music, recordings,
                                artwork, photographs, videos, logos, written
                                content, graphics, and other creative material.
                            </p>

                            <p>
                                This content may belong to Kay Factory Music,
                                our artists, collaborators, or other rights
                                holders. You may not copy, reproduce,
                                distribute, modify, or commercially use
                                protected content without the appropriate
                                permission.
                            </p>

                            <Reveal>
                                <h2>3. Music and intellectual property</h2>
                            </Reveal>

                            <p>
                                Access to or playback of music through our
                                website does not transfer ownership or other
                                intellectual property rights to you.
                            </p>

                            <p>
                                Any use of music, recordings, artwork, or other
                                protected material beyond normal personal use
                                must have the permission or licence required
                                from the relevant rights holder.
                            </p>

                            <Reveal>
                                <h2>4. Music submissions</h2>
                            </Reveal>

                            <p>
                                If you submit music, recordings, photographs,
                                videos, artist information, or other materials
                                to Kay Factory Music, you should only submit
                                material that you have the right to share with
                                us.
                            </p>

                            <ul>
                                <li>
                                    You confirm that you have the necessary
                                    rights or permission to submit the material.
                                </li>

                                <li>
                                    We may review the material for the purpose
                                    for which it was submitted.
                                </li>

                                <li>
                                    A submission does not automatically create a
                                    record deal, management agreement,
                                    publishing agreement, distribution
                                    agreement, or other commercial relationship.
                                </li>

                                <li>
                                    We are not required to accept, release,
                                    distribute, or promote a submission.
                                </li>
                            </ul>

                            <Reveal>
                                <h2>5. Staff accounts</h2>
                            </Reveal>

                            <p>
                                Some areas of the Kay Factory Music platform are
                                restricted to authorised staff members.
                            </p>

                            <p>
                                Staff members are responsible for keeping their
                                login information secure and must not share
                                account credentials with unauthorised people.
                                Any suspected unauthorised access should be
                                reported as soon as possible.
                            </p>

                            <Reveal>
                                <h2>6. Third-party websites</h2>
                            </Reveal>

                            <p>
                                Our website may contain links to third-party
                                websites and services, including music streaming
                                platforms and social media platforms.
                            </p>

                            <p>
                                These websites are operated independently from
                                Kay Factory Music. Their own terms, privacy
                                policies, and other rules apply when you use
                                them.
                            </p>

                            <Reveal>
                                <h2>7. Website availability</h2>
                            </Reveal>

                            <p>
                                We work to keep the website available and
                                functioning properly, but we cannot guarantee
                                that it will always be available or free from
                                interruptions, errors, or technical issues.
                            </p>

                            <p>
                                We may update, change, suspend, or remove parts
                                of the website when necessary.
                            </p>

                            <Reveal>
                                <h2>8. Privacy</h2>
                            </Reveal>

                            <p>
                                Information collected through the website is
                                handled in accordance with our{" "}
                                <a href="/privacy-policy">Privacy Policy</a>.
                            </p>

                            <Reveal>
                                <h2>9. Limitation of liability</h2>
                            </Reveal>

                            <p>
                                To the extent permitted by applicable law, Kay
                                Factory Music will not be responsible for
                                indirect or consequential losses arising from
                                your use of the website.
                            </p>

                            <p>
                                Nothing in these Terms is intended to exclude or
                                limit a responsibility or liability that cannot
                                lawfully be excluded or limited.
                            </p>

                            <Reveal>
                                <h2>10. Changes to these Terms</h2>
                            </Reveal>

                            <p>
                                We may update these Terms from time to time to
                                reflect changes to the website, our services, or
                                applicable requirements.
                            </p>

                            <p>
                                The date at the beginning of this page shows
                                when the current version was published.
                            </p>

                            <Reveal>
                                <h2>11. Contact</h2>
                            </Reveal>

                            <p>
                                If you have questions about these Terms or
                                anything relating to the use of the website,
                                contact Kay Factory Music at:
                            </p>

                            <p>
                                <a href="mailto:Kayfactorymusic@gmail.com">
                                  terms@kayfactorymusic.com
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
