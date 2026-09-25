
import { useEffect } from "react";
import Navbar from "../../components/public/Navbar";
import Footer from "../../components/public/Footer";
import Reveal from "../../components/Reveal";

export default function PrivacyPolicyPage() {
    useEffect(() => {
        window.scrollTo(0, 0);
        document.title = "Privacy Policy — Kay Factory Music";
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
                                PRIVACY
                            </h1>
                        </Reveal>

                        <Reveal delay={100}>
                            <div className="kfm-about-page__hero-copy">
                                <div className="kfm-about-page__hero-brand">
                                    Kay Factory Music
                                </div>

                                <div className="kfm-about-page__hero-tagline">
                                    Privacy Policy
                                </div>
                            </div>
                        </Reveal>
                    </div>
                </section>

                {/* ================= PRIVACY POLICY ================= */}
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
                                    Kay Factory Music respects your privacy. This
                                    Privacy Policy explains what information we may
                                    collect when you use our website and services,
                                    how we use that information, and the choices
                                    available to you.
                                </p>
                            </Reveal>

                            <Reveal>
                                <h2>1. About this policy</h2>
                            </Reveal>

                            <p>
                                This policy applies to information collected through
                                the Kay Factory Music website, including when you
                                contact us, submit music or other materials, or use
                                areas of the platform that require an account.
                            </p>

                            <p>
                                Kay Factory Music may update this policy when our
                                website, services, or data practices change. The
                                date above shows when this version was last updated.
                            </p>

                            <Reveal>
                                <h2>2. Information we collect</h2>
                            </Reveal>

                            <p>
                                The information we collect depends on how you use
                                our website and services.
                            </p>

                            <ul>
                                <li>
                                    <strong>Information you provide.</strong>{" "}
                                    This may include your name, email address,
                                    phone number, artist information, music,
                                    messages, and other information you choose
                                    to provide when contacting us or submitting
                                    materials.
                                </li>

                                <li>
                                    <strong>Account information.</strong>{" "}
                                    If you use an account-based area of our
                                    platform, we may collect information such as
                                    your name, email address, account role, and
                                    authentication information.
                                </li>

                                <li>
                                    <strong>Technical information.</strong>{" "}
                                    We may receive basic technical information
                                    associated with requests to our website,
                                    such as IP address, browser type, device
                                    information, and request times.
                                </li>
                            </ul>

                            <Reveal>
                                <h2>3. How we use information</h2>
                            </Reveal>

                            <p>
                                We use information we collect for purposes such as:
                            </p>

                            <ul>
                                <li>Operating and maintaining our website and services</li>
                                <li>Responding to enquiries and messages</li>
                                <li>Reviewing music and other submissions</li>
                                <li>Managing user accounts and authentication</li>
                                <li>Providing requested services and communications</li>
                                <li>Protecting the security of our systems</li>
                                <li>Maintaining records where reasonably necessary</li>
                                <li>Meeting applicable legal or regulatory requirements</li>
                            </ul>

                            <Reveal>
                                <h2>4. How we use submitted music and materials</h2>
                            </Reveal>

                            <p>
                                If you submit music, recordings, artist information,
                                photographs, videos, or other materials to Kay Factory
                                Music, we use those materials for the purpose for
                                which you submitted them, including reviewing your
                                submission and communicating with you about it.
                            </p>

                            <p>
                                Submitting material through our website does not,
                                by itself, create a recording, management, publishing,
                                distribution, or other commercial agreement with
                                Kay Factory Music.
                            </p>

                            <Reveal>
                                <h2>5. Cookies and similar technologies</h2>
                            </Reveal>

                            <p>
                                Our website may use cookies or similar technologies
                                that are necessary for features such as authentication,
                                security, and maintaining an active session.
                            </p>

                            <p>
                                We do not use personal information for advertising
                                purposes simply because you visit our website. Where
                                additional technologies are introduced, this policy
                                may be updated to explain their purpose.
                            </p>

                            <Reveal>
                                <h2>6. When we share information</h2>
                            </Reveal>

                            <p>
                                We do not sell your personal information.
                            </p>

                            <p>
                                We may share information when it is reasonably
                                necessary to operate our services, including with
                                service providers that support functions such as
                                hosting, infrastructure, email delivery, security,
                                and authentication.
                            </p>

                            <p>
                                We may also disclose information where required by
                                applicable law, legal process, or where necessary
                                to protect the rights, security, or property of
                                Kay Factory Music, our users, artists, or others.
                            </p>

                            <Reveal>
                                <h2>7. Data security</h2>
                            </Reveal>

                            <p>
                                We take reasonable technical and organisational
                                measures to protect information against unauthorized
                                access, alteration, disclosure, or loss.
                            </p>

                            <p>
                                These measures may include access controls, encrypted
                                connections, secure authentication practices, and
                                restricted access to information where appropriate.
                                No online system can be guaranteed to be completely
                                secure.
                            </p>

                            <Reveal>
                                <h2>8. How long we keep information</h2>
                            </Reveal>

                            <p>
                                We keep personal information only for as long as
                                reasonably necessary for the purpose for which it
                                was collected, to maintain legitimate business
                                records, resolve disputes, protect our services,
                                or meet applicable legal requirements.
                            </p>

                            <Reveal>
                                <h2>9. Your privacy rights</h2>
                            </Reveal>

                            <p>
                                Depending on applicable law, you may have rights
                                relating to your personal information, including
                                rights to request access to, correction of, or
                                deletion of certain information, as well as rights
                                relating to how your information is processed.
                            </p>

                            <p>
                                To make a privacy request or ask a question about
                                how we handle your information, contact:
                            </p>

                            <p>
                                <a href="mailto:Kayfactorymusic@gmail.com">
                                    privacy@kayfactorymusic.com
                                </a>
                            </p>

                            <Reveal>
                                <h2>10. Children</h2>
                            </Reveal>

                            <p>
                                Our website is not directed at young children.
                                We do not knowingly request personal information
                                from children where doing so would be unlawful.
                            </p>

                            <Reveal>
                                <h2>11. Changes to this policy</h2>
                            </Reveal>

                            <p>
                                We may update this Privacy Policy from time to
                                time to reflect changes to our services, technology,
                                or applicable requirements. When we make changes,
                                we will update the date shown at the beginning of
                                the policy.
                            </p>

                            <Reveal>
                                <h2>12. Contact</h2>
                            </Reveal>

                            <p>
                                If you have questions about this Privacy Policy,
                                our data practices, or a privacy request, contact
                                Kay Factory Music at:
                            </p>

                            <p>
                                <a href="mailto:Kayfactorymusic@gmail.com">
                                    privacy@kayfactorymusic.com
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
